<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserAddress;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;

class ShippingController extends Controller
{
public function checkRates(Request $request)
    {
        try {
            // 1. Validasi
            $request->validate([
                'address_id' => 'required|exists:user_addresses,id',
                'items'      => 'required|array'
            ]);

            $address = UserAddress::find($request->address_id);

            if (!$address || empty($address->komerce_destination_id)) {
                return response()->json(['message' => 'Alamat belum terverifikasi. Edit & Simpan ulang alamat ini.'], 400);
            }

            $totalWeight = 0;
            foreach ($request->items as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                $weight  = $variant ? ($variant->weight ?? 100) : 100; 
                $totalWeight += ($weight * $item['quantity']);
            }
            if ($totalWeight < 1) $totalWeight = 1000;

            // 2. Config
            $apiKey   = config('rajaongkir.api_key');
            $baseUrl  = config('rajaongkir.base_url') . '/calculate/domestic-cost';
            $originId = (int) config('rajaongkir.origin_id'); 

            // 3. Payload (SAMA PERSIS DENGAN POSTMAN)
            $payload = [
                'origin'           => $originId,
                'originType'       => 'subdistrict', 
                'destination'      => (int) $address->komerce_destination_id,
                'destinationType'  => 'subdistrict',
                'weight'           => (int) $totalWeight,
                'courier'          => 'jne:pos:tiki:sicepat:jnt' // Request banyak kurir
            ];

            // 4. Request
            $response = Http::withHeaders([
                'key' => $apiKey
            ])->asForm()->post($baseUrl, $payload);

            if ($response->failed()) {
                throw new \Exception("API Error: " . ($response->json()['meta']['message'] ?? $response->body()));
            }

            $data = $response->json();

            // 5. Parsing Data (PERBAIKAN DISINI!)
            // Kita membaca struktur Flat sesuai hasil Postman Anda
            $formattedRates = [];
            
            $results = $data['data'] ?? [];
            
            foreach ($results as $rate) {
                // Validasi: Pastikan field penting ada
                if (isset($rate['code'], $rate['service'], $rate['cost'])) {
                    $formattedRates[] = [
                        'id'           => $rate['code'] . '-' . $rate['service'],
                        'courier_name' => strtoupper($rate['code']), // JNE, JNT, SICEPAT
                        'service_type' => $rate['service'], // REG, YES, EZ
                        'duration'     => !empty($rate['etd']) ? $rate['etd'] . ' HARI' : '-',
                        'price'        => $rate['cost'],
                        'description'  => $rate['description'] ?? ''
                    ];
                }
            }

            if (empty($formattedRates)) {
                throw new \Exception("Tidak ada layanan pengiriman yang ditemukan.");
            }

            return response()->json(['rates' => $formattedRates]);

        } catch (\Exception $e) {
            Log::error("Shipping Error: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

/**
     * FITUR ADMIN: REQUEST PICKUP (BOOKING KURIR)
     * URL: POST /api/orders/{id}/request-pickup
     * Menggunakan URL Sandbox untuk simulasi.
     */
    public function requestPickup($orderId)
    {
        try {
            // 1. Ambil Data Order
            $order = \App\Models\Order::with(['shippingAddress', 'items.productVariant'])
                ->where('id', $orderId)
                ->orWhere('order_number', $orderId)
                ->firstOrFail();

            if ($order->shipping_tracking_number) {
                return response()->json(['message' => 'Order ini sudah di-booking! Resi: ' . $order->shipping_tracking_number], 400);
            }
            
            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store'; 

            $courierParts = explode(' - ', $order->shipping_courier ?? 'JNE - REG');
            $shippingCode = strtoupper(trim($courierParts[0] ?? 'JNE')); 
            $shippingType = strtoupper(trim($courierParts[1] ?? 'REG')); 

            // 2. Hitung Barang
            $itemsPayload = [];
            $goodsValue = 0; 
            $totalWeight = 0;

            foreach ($order->items as $item) {
                $weight = $item->productVariant->weight ?? 100;
                $totalWeight += ($weight * $item->quantity);
                
                $subtotalItem = (int) ($item->price * $item->quantity);
                $goodsValue += $subtotalItem; // Total Harga Barang

                $itemsPayload[] = [
                    'product_name'         => $item->product_name,
                    'product_variant_name' => $item->productVariant->name ?? '-',
                    'product_price'        => (int) $item->price,
                    'product_weight'       => (int) $weight,
                    'product_width'        => 10,
                    'product_height'       => 10,
                    'product_length'       => 10,
                    'qty'                  => $item->quantity,
                    'subtotal'             => $subtotalItem
                ];
            }
            
            $totalWeight = max(1000, $totalWeight); 
            $shippingCost = (int) $order->shipping_cost;

            // 3. HITUNG COD & SERVICE FEE (CRITICAL FIX)
            
            // Total yang harus dibayar pembeli ke kurir (Barang + Ongkir)
            $codValue = $goodsValue + $shippingCost;

            // Hitung Biaya Layanan COD (2.8% dari Nilai COD)
            // Rumus: cod_value * 0.028
            $serviceFee = floor($codValue * 0.028); 

            // Pastikan tidak 0 (Komerce menolak jika 0 pada COD)
            if ($serviceFee < 500) $serviceFee = 500; // Minimum safety net

            // 4. Payload
            $payload = [
                'order_date'       => now()->format('Y-m-d'),
                'brand_name'       => 'Skin Lab Beauty',
                
                'shipper_name'           => 'Admin Skin Lab',
                'shipper_phone'          => '08123456789', 
                'shipper_destination_id' => (int) config('rajaongkir.origin_id'),
                'shipper_address'        => 'Jl. Gudang Skin Lab No 1',
                'shipper_email'          => 'admin@skinlab.com',
                'origin_pin_point'       => '', 

                'receiver_name'           => $order->shippingAddress->receiver_name,
                'receiver_phone'          => $order->shippingAddress->phone_number,
                'receiver_destination_id' => (int) $order->shippingAddress->komerce_destination_id,
                'receiver_address'        => $order->shippingAddress->full_address,
                'receiver_email'          => 'customer@email.com', 
                'destination_pin_point'   => '', 

                'shipping'          => $shippingCode, 
                'shipping_type'     => $shippingType, 
                'payment_method'    => 'COD', // Mode COD
                
                'shipping_cost'     => $shippingCost,
                'shipping_cashback' => 0,
                
                // MASUKKAN SERVICE FEE YANG SUDAH DIHITUNG
                'service_fee'       => (int) $serviceFee,
                
                'additional_cost'   => 0,
                'cod_value'         => (int) $codValue, 
                'insurance_value'   => 0,
                'grand_total'       => (int) $codValue, // Total sama dengan COD Value

                'order_details'     => $itemsPayload
            ];

            Log::info('Booking Payload:', $payload);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'    => $apiKey, 
                'Content-Type' => 'application/json'
            ])->post($baseUrl, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['meta']['code']) && $result['meta']['code'] == 201) {
                $data = $result['data'];
                
                $order->update([
                    'shipping_tracking_number' => $data['order_no'],
                    'komerce_order_id'         => $data['order_id'],
                    'order_status'             => 'shipped'
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Booking Berhasil! Resi: ' . $data['order_no'],
                    'data'    => $data
                ]);
            } else {
                Log::error("Booking Gagal:", ['response' => $result]);
                throw new \Exception("Gagal: " . ($result['meta']['message'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
/**
     * GET /api/admin/orders/{id}/label
     * Mencetak Label sesuai format cURL dokumentasi
     */
    public function getLabel($orderId)
    {
        try {
            $order = \App\Models\Order::findOrFail($orderId);

            if (!$order->shipping_tracking_number) {
                return response()->json(['message' => 'Order belum di-booking (Resi kosong)!'], 400);
            }

            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/print-label';

            // Parameter
            $queryParams = http_build_query([
                'page'     => 'page_6', 
                'order_no' => $order->shipping_tracking_number
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey
            ])->post($baseUrl . '?' . $queryParams);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['data']['base_64'])) {
                    $pdfContent = base64_decode($result['data']['base_64']);
                    
                    $fileName = 'shipping_label_' . $order->shipping_tracking_number . '.pdf';
                    $path = 'labels/' . $fileName;

                    Storage::disk('public')->put($path, $pdfContent);
                    
                    $publicUrl = asset('storage/' . $path);

                    $order->update(['shipping_label_url' => $publicUrl]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Label berhasil dibuat',
                        'url'    => $publicUrl // <--- KLIK INI DI POSTMAN
                    ]);
                }

                return response()->json($result);
            }

            throw new \Exception("Gagal Cetak: " . $response->body());

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * FITUR ADMIN: ATUR JADWAL PICKUP
     * Endpoint: POST /api/admin/pickup/schedule
     */
    public function schedulePickup(Request $request)
    {
        try {
            // 1. Validasi Input Admin
            $request->validate([
                'order_ids'      => 'required|array', // List ID Order lokal (misal: [1, 2])
                'pickup_date'    => 'required|date_format:Y-m-d', // Contoh: 2025-11-25
                'pickup_time'    => 'required', // Contoh: 10:00
                'pickup_vehicle' => 'required|in:Motor,Mobil,Truk'
            ]);

            // 2. Ambil Nomor Resi (KOM...) dari Database
            $orders = \App\Models\Order::whereIn('id', $request->order_ids)->get();
            
            $komerceOrderNumbers = [];
            foreach ($orders as $order) {
                // Hanya masukkan order yang sudah ada Resinya
                if ($order->shipping_tracking_number) {
                    $komerceOrderNumbers[] = [
                        'order_no' => $order->shipping_tracking_number
                    ];
                }
            }

            if (empty($komerceOrderNumbers)) {
                return response()->json(['message' => 'Tidak ada order yang valid untuk di-pickup (Pastikan sudah Booking dulu)'], 400);
            }

            // 3. Config API
            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/pickup/request';

            // 4. Payload (Sesuai Dokumentasi Pickup)
            $payload = [
                'pickup_date'    => $request->pickup_date,
                'pickup_time'    => $request->pickup_time,
                'pickup_vehicle' => $request->pickup_vehicle,
                'orders'         => $komerceOrderNumbers
            ];

            // 5. Kirim Request
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->post($baseUrl, $payload);

            // 6. Cek Hasil
            $result = $response->json();

            if ($response->successful() && isset($result['meta']['code']) && $result['meta']['code'] == 201) {
                // Update status di database lokal (Opsional)
                \App\Models\Order::whereIn('id', $request->order_ids)
                    ->update(['order_status' => 'pickup_scheduled']);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Kurir berhasil dijadwalkan datang!',
                    'data'    => $result['data']
                ]);
            } else {
                throw new \Exception("Gagal Pickup: " . ($result['meta']['message'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function trackShipment($orderId)
    {
        try {
            $order = \App\Models\Order::findOrFail($orderId);

            if (!$order->shipping_tracking_number) {
                return response()->json(['message' => 'Belum ada resi.'], 400);
            }

            // 1. Ambil Kurir (Lowercase)
            $courierString = $order->shipping_courier ?? 'jne';
            $courierParts = explode(' - ', $courierString);
            $courierCode = strtolower(trim($courierParts[0])); 

            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/history-airway-bill';

            // 2. Request
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey
            ])->get($baseUrl, [
                'shipping'    => $courierCode,                    
                'airway_bill' => $order->shipping_tracking_number 
            ]);

            // 3. SUKSES?
            if ($response->successful()) {
                $result = $response->json();
                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'airway_bill' => $result['data']['airway_bill'] ?? $order->shipping_tracking_number,
                        'last_status' => $result['data']['last_status'] ?? 'TERLACAK',
                        'history'     => $result['data']['history'] ?? []
                    ]
                ]);
            }

            // 4. ERROR HANDLING (MANIPULASI AGAR TIDAK CRASH)
            // Jika API bilang "Invalid", kita anggap saja "Belum Ada Data" (Pending)
            if ($response->status() == 400 || $response->status() == 422) {
                return response()->json([
                    'status' => 'success', // Kita bilang sukses ke frontend
                    'data'   => [
                        'airway_bill' => $order->shipping_tracking_number,
                        'last_status' => 'MENUNGGU PICKUP (Data Tracking Belum Tersedia)',
                        'history'     => [
                            [
                                'date' => now()->format('Y-m-d H:i:s'),
                                'desc' => 'Resi telah dibuat. Menunggu update dari ekspedisi.',
                                'status' => 'PENDING'
                            ]
                        ]
                    ]
                ]);
            }

            // Error lain (Server Error, dll) tetap dilempar
            throw new \Exception("Gagal Lacak: " . $response->body());

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

/**
     * GET /api/admin/orders/{id}/detail
     * Mengambil Detail Order
     */
    public function getOrderDetail($orderId)
    {
        try {
            // 1. Ambil Order Lokal
            $order = \App\Models\Order::findOrFail($orderId);

            // Validasi: Pastikan sudah ada Nomor Order Komerce (KOM...)
            // Di database kita, ini disimpan di 'shipping_tracking_number' saat booking
            if (!$order->shipping_tracking_number) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ini belum di-booking (Belum ada nomor Order KOM...).'
                ], 400);
            }

            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/detail';

            // 2. LOG REQUEST (Untuk Debugging)
            \Illuminate\Support\Facades\Log::info('Detail Request:', [
                'url' => $baseUrl,
                'order_no' => $order->shipping_tracking_number
            ]);

            // 3. KIRIM REQUEST (Parameter 'order_no')
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey
            ])->get($baseUrl, [
                'order_no' => $order->shipping_tracking_number // <--- PERBAIKAN DISINI (Pakai order_no)
            ]);

            // 4. Handle Response
            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'local_data' => [
                        'id' => $order->id,
                        'status_lokal' => $order->order_status
                    ],
                    'komerce_data' => $response->json()['data'] ?? $response->json()
                ]);
            }

            throw new \Exception("Gagal Ambil Detail: " . $response->body());

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/orders/{id}/cancel
     * Membatalkan Booking Pengiriman
     */
    public function cancelShipment($orderId)
    {
        try {
            $order = \App\Models\Order::findOrFail($orderId);

            // Validasi: Harus ada resi dulu baru bisa dicancel
            if (!$order->shipping_tracking_number) {
                return response()->json(['message' => 'Tidak ada pengiriman yang bisa dibatalkan.'], 400);
            }

            $apiKey = config('rajaongkir.delivery_key');
            $baseUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/cancel';

            // Payload Cancel
            $payload = [
                'order_no' => $order->shipping_tracking_number, // Resi (KOM...)
                'reason'   => 'Perubahan pesanan oleh Admin'    // Alasan (Wajib)
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->put($baseUrl, $payload); // Perhatikan: Method PUT

            if ($response->successful()) {
                // Update Database Lokal: Hapus Resi & Ubah Status
                $order->update([
                    'shipping_tracking_number' => null,
                    'shipping_label_url'       => null,
                    'komerce_order_id'         => null,
                    'order_status'             => 'canceled' // Atau 'pending'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pengiriman berhasil dibatalkan. Resi dihapus.'
                ]);
            }

            throw new \Exception("Gagal Cancel: " . ($response->json()['meta']['message'] ?? $response->body()));

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * WEBHOOK: Menerima Laporan Status dari Kurir
     * URL: POST /api/webhook/komerce
     */
    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();
            \Illuminate\Support\Facades\Log::info('🔔 Webhook Masuk:', $data);

            // Komerce mengirim: { "cnote": "...", "status": "DELIVERED" }
            $resi = $data['cnote'] ?? $data['awb'] ?? null;
            $statusKurir = strtoupper($data['status'] ?? '');

            if ($resi) {
                $order = \App\Models\Order::where('shipping_tracking_number', $resi)->first();

                if ($order) {
                    $newStatus = $order->order_status;

                    // Mapping Status
                    if (in_array($statusKurir, ['DELIVERED', 'POD'])) {
                        $newStatus = 'completed';
                    } elseif (in_array($statusKurir, ['RETUR', 'RETURNED'])) {
                        $newStatus = 'canceled';
                    } elseif (in_array($statusKurir, ['ON PROCESS', 'MANIFEST'])) {
                        $newStatus = 'shipped';
                    }

                    if ($newStatus !== $order->order_status) {
                        $order->update(['order_status' => $newStatus]);
                    }
                }
            }
            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Endpoint Khusus Testing Postman
     * URL: http://127.0.0.1:8000/api/test-manual
     */
    public function testManual(Request $request)
    {
        // Ambil input dari Postman, atau pakai default jika kosong
        $origin = $request->input('origin', 17473);      // Default: Jakarta (Grogol)
        $dest   = $request->input('destination', 2418);  // Default: Banjarmasin (Alalak)
        $weight = $request->input('weight', 1000);       // Default: 1kg
        $courier = $request->input('courier', 'jne');    // Default: jne

        $apiKey = config('rajaongkir.api_key');
        $baseUrl = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

        try {
            $payload = [
                'origin'           => $origin,
                'originType'       => 'subdistrict',
                'destination'      => $dest,
                'destinationType'  => 'subdistrict',
                'weight'           => $weight,
                'courier'          => $courier
            ];

            // Request Asli
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'key' => $apiKey
            ])->asForm()->post($baseUrl, $payload);

            // Tampilkan SEMUA respon apa adanya (Raw)
            return response()->json([
                'input_kita' => $payload,
                'status_api' => $response->status(),
                'hasil_api'  => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}