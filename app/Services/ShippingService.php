<?php

namespace App\Services;

use App\Models\Order;
use App\Models\UserAddress;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ShippingService
{
    private string $apiKey;
    private string $deliveryKey;
    private string $baseUrlCheckRates;
    private string $baseUrlBooking;
    private int $originId;

    public function __construct()
    {
        // Initialize Configuration
        $this->apiKey = config('rajaongkir.api_key');
        $this->deliveryKey = config('rajaongkir.delivery_key');
        
        $this->baseUrlCheckRates = config('rajaongkir.base_url') . '/calculate/domestic-cost';
        // Sandbox Base URL for Komerce (Booking/Tracking/Pickup)
        $this->baseUrlBooking = 'https://api-sandbox.collaborator.komerce.id/order/api/v1';
        
        $this->originId = (int) config('rajaongkir.origin_id');
    }

    /**
     * Calculate Shipping Rates (Cek Ongkir)
     */
    public function calculateRates(UserAddress $address, array $items): array
    {
        // 1. Calculate Total Weight
        $totalWeight = 0;
        foreach ($items as $item) {
            $variant = ProductVariant::find($item['variant_id']);
            $weight  = $variant ? ($variant->weight ?? 100) : 100;
            $totalWeight += ($weight * $item['quantity']);
        }
        if ($totalWeight < 1) $totalWeight = 1000;

        // 2. Request to API
        $payload = [
            'origin'           => $this->originId,
            'originType'       => 'subdistrict',
            'destination'      => (int) $address->komerce_destination_id,
            'destinationType'  => 'subdistrict',
            'weight'           => (int) $totalWeight,
            'courier'          => 'jne:pos:tiki:sicepat:jnt'
        ];

        $response = Http::withHeaders(['key' => $this->apiKey])
            ->asForm()
            ->post($this->baseUrlCheckRates, $payload);

        if ($response->failed()) {
            throw new Exception("API Error: " . ($response->json()['meta']['message'] ?? $response->body()));
        }

        // 3. Format Response
        $formattedRates = [];
        $results = $response->json()['data'] ?? [];

        foreach ($results as $rate) {
            if (isset($rate['code'], $rate['service'], $rate['cost'])) {
                $formattedRates[] = [
                    'id'           => $rate['code'] . '-' . $rate['service'],
                    'courier_name' => strtoupper($rate['code']),
                    'service_type' => $rate['service'],
                    'duration'     => !empty($rate['etd']) ? $rate['etd'] . ' ' : '-',
                    'price'        => $rate['cost'],
                    'description'  => $rate['description'] ?? ''
                ];
            }
        }

        if (empty($formattedRates)) {
            throw new Exception("No shipping services found.");
        }

        return $formattedRates;
    }

    /**
     * Request Pickup / Booking Courier
     */
/**
     * Request Pickup / Booking Courier (Get Resi)
     */
    public function bookShipment(Order $order): array
    {
        if ($order->shipping_tracking_number) {
            throw new Exception("Order is already booked with tracking number: {$order->shipping_tracking_number}");
        }

        $payload = $this->prepareBookingPayload($order);
        
        $response = Http::withHeaders([
            'x-api-key'    => $this->deliveryKey,
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrlBooking}/orders/store", $payload);

        $result = $response->json();
        
        // Log::info('KOMERCE API RESPONSE:', $result); // Dapat diaktifkan untuk debugging di production

        // Memastikan API sukses dan AWB ada di dalam respon
        if ($response->successful() && ($result['meta']['code'] ?? 0) == 201) {
            
            $data = $result['data'];
            
            // Mengambil Resi (AWB) dengan Fallback key: order_no, awb, atau resi
            $noResi = $data['order_no'] ?? $data['awb'] ?? $data['resi'] ?? null;

            if (empty($noResi)) {
                // Terjadi jika API sukses tapi tidak mengembalikan data resi
                throw new Exception("Komerce sukses, tapi Nomor Resi (AWB) tidak ditemukan di respon.");
            }

            // Menyimpan AWB ke kolom yang benar
            $order->update([
                'shipping_tracking_number' => $noResi,
                'komerce_order_id'         => $data['order_id'],
                'order_status'             => 'shipped' // Ubah status setelah booking berhasil
            ]);

            return $data;
        }

        // Melempar error jika ada masalah di API (misal 404/400/API Key salah)
        throw new Exception("Booking Failed: " . ($result['meta']['message'] ?? 'Unknown API Error'));
    }

    /**
     * Generate Shipping Label (PDF)
     */
    public function generateLabel(Order $order): string
    {
        if (!$order->shipping_tracking_number) {
            throw new Exception("Cannot print label. Order is not booked yet.");
        }

        $queryParams = http_build_query([
            'page'     => 'page_6', 
            'order_no' => $order->shipping_tracking_number
        ]);

        $response = Http::withHeaders(['x-api-key' => $this->deliveryKey])
            ->post("{$this->baseUrlBooking}/orders/print-label?{$queryParams}");

        if ($response->successful()) {
            $result = $response->json();
            
            if (isset($result['data']['base_64'])) {
                $pdfContent = base64_decode($result['data']['base_64']);
                $fileName = 'shipping_label_' . $order->shipping_tracking_number . '.pdf';
                $path = 'labels/' . $fileName;

                Storage::disk('public')->put($path, $pdfContent);
                $publicUrl = asset('storage/' . $path);

                $order->update(['shipping_label_url' => $publicUrl]);
                return $publicUrl;
            }
        }

        throw new Exception("Failed to generate label.");
    }

    /**
     * Track Shipment Status
     */
    public function trackShipment(Order $order): array
    {
        if (!$order->shipping_tracking_number) {
            throw new Exception("Tracking number not found.");
        }

        $courierParts = explode(' - ', $order->shipping_courier ?? 'jne');
        $courierCode = strtolower(trim($courierParts[0])); 

        $response = Http::withHeaders(['x-api-key' => $this->deliveryKey])
            ->get("{$this->baseUrlBooking}/orders/history-airway-bill", [
                'shipping'    => $courierCode,                    
                'airway_bill' => $order->shipping_tracking_number 
            ]);

        if ($response->successful()) {
            $result = $response->json();
            return [
                'airway_bill' => $result['data']['airway_bill'] ?? $order->shipping_tracking_number,
                'last_status' => $result['data']['last_status'] ?? 'TRACKED',
                'history'     => $result['data']['history'] ?? []
            ];
        }

        // Graceful fallback if data not found (usually means just booked)
        if ($response->status() == 400 || $response->status() == 422) {
            return [
                'airway_bill' => $order->shipping_tracking_number,
                'last_status' => 'PENDING PICKUP',
                'history'     => []
            ];
        }

        throw new Exception("Tracking Failed: " . $response->body());
    }

    /**
     * Cancel Shipment Booking
     */
    public function cancelBooking(Order $order): void
    {
        if (!$order->shipping_tracking_number) {
            throw new Exception("No shipment to cancel.");
        }

        $payload = [
            'order_no' => $order->shipping_tracking_number,
            'reason'   => 'Canceled by Admin'
        ];

        $response = Http::withHeaders([
            'x-api-key'    => $this->deliveryKey,
            'Content-Type' => 'application/json'
        ])->put("{$this->baseUrlBooking}/orders/cancel", $payload);

        if ($response->successful()) {
            $order->update([
                'shipping_tracking_number' => null,
                'shipping_label_url'       => null,
                'komerce_order_id'         => null,
                'order_status'             => 'canceled'
            ]);
            return;
        }

        throw new Exception("Cancellation Failed: " . ($response->json()['meta']['message'] ?? 'Unknown'));
    }

    /**
     * Get Order Detail from Komerce
     */
    public function getOrderDetail(Order $order): array
    {
        if (!$order->shipping_tracking_number) {
            throw new Exception("Order hasn't been booked yet (No Tracking Number).");
        }

        $response = Http::withHeaders(['x-api-key' => $this->deliveryKey])
            ->get("{$this->baseUrlBooking}/orders/detail", [
                'order_no' => $order->shipping_tracking_number
            ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        throw new Exception("Failed to fetch details: " . $response->body());
    }

    /**
     * Schedule Pickup (Admin Feature)
     */
    public function schedulePickup(array $orderIds, string $date, string $time, string $vehicle): array
    {
        // 1. Filter Orders that have Tracking Number
        $orders = Order::whereIn('id', $orderIds)->get();
        $komerceOrderNumbers = [];

        foreach ($orders as $order) {
            if ($order->shipping_tracking_number) {
                $komerceOrderNumbers[] = ['order_no' => $order->shipping_tracking_number];
            }
        }

        if (empty($komerceOrderNumbers)) {
            throw new Exception("No valid orders found for pickup (Must be booked first).");
        }

        // 2. Payload
        $payload = [
            'pickup_date'    => $date,
            'pickup_time'    => $time,
            'pickup_vehicle' => $vehicle,
            'orders'         => $komerceOrderNumbers
        ];

        // 3. Request
        $response = Http::withHeaders([
            'x-api-key'    => $this->deliveryKey,
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrlBooking}/pickup/request", $payload);

        // 4. Handle Response
        $result = $response->json();

        if ($response->successful() && ($result['meta']['code'] ?? 0) == 201) {
            // Update local status (optional)
            Order::whereIn('id', $orderIds)->update(['order_status' => 'pickup_scheduled']);
            return $result['data'];
        }

        throw new Exception("Pickup Schedule Failed: " . ($result['meta']['message'] ?? 'Unknown Error'));
    }

    /**
     * Handle Webhook Logic
     */
    public function updateStatusFromWebhook(array $data): void
    {
        $resi = $data['cnote'] ?? $data['awb'] ?? null;
        $courierStatus = strtoupper($data['status'] ?? '');

        if ($resi) {
            $order = Order::where('shipping_tracking_number', $resi)->first();

            if ($order) {
                $newStatus = $order->order_status;

                // Map Courier Status to Local Status
                if (in_array($courierStatus, ['DELIVERED', 'POD'])) {
                    $newStatus = 'completed';
                } elseif (in_array($courierStatus, ['RETUR', 'RETURNED'])) {
                    $newStatus = 'canceled';
                } elseif (in_array($courierStatus, ['ON PROCESS', 'MANIFEST'])) {
                    $newStatus = 'shipped';
                }

                if ($newStatus !== $order->order_status) {
                    $order->update(['order_status' => $newStatus]);
                    Log::info("Order #{$order->id} status updated to {$newStatus} via Webhook");
                }
            }
        }
    }
    
    /**
     * Helper: Prepare Payload for Booking (Private)
     */
    public function prepareBookingPayload(Order $order): array
    {
        $courierParts = explode(' - ', $order->shipping_courier ?? 'JNE - REG');
        $shippingCode = strtoupper(trim($courierParts[0] ?? 'JNE')); 
        $shippingType = strtoupper(trim($courierParts[1] ?? 'REG')); 

        $itemsPayload = [];
        $goodsValue = 0; 
        $totalWeight = 0;

        foreach ($order->items as $item) {
            $weight = $item->productVariant->weight ?? 100;
            $totalWeight += ($weight * $item->quantity);
            
            $subtotalItem = (int) ($item->price * $item->quantity);
            $goodsValue += $subtotalItem;

            $itemsPayload[] = [
                'product_name'         => $item->product_name,
                'product_variant_name' => $item->productVariant->name ?? '-',
                'product_price'        => (int) $item->price,
                'product_weight'       => (int) $weight,
                'product_width' => 10, 'product_height' => 10, 'product_length' => 10,
                'qty'                  => $item->quantity,
                'subtotal'             => $subtotalItem
            ];
        }
        
        $shippingCost = (int) $order->shipping_cost;
        $codValue = $goodsValue + $shippingCost;
        
        // Calculate Service Fee (2.8% or min 500)
        $serviceFee = floor($codValue * 0.028);
        if ($serviceFee < 500) $serviceFee = 500;

        return [
            'order_date'       => now()->format('Y-m-d'),
            'brand_name'       => 'Skin Lab Beauty',
            'shipper_name'     => 'Admin Skin Lab',
            'shipper_phone'    => '08123456789', 
            'shipper_destination_id' => $this->originId,
            'shipper_address'  => 'Jl. Gudang Skin Lab No 1',
            'shipper_email'    => 'admin@skinlab.com',
            'receiver_name'    => $order->shippingAddress->receiver_name,
            'receiver_phone'   => $order->shippingAddress->phone_number,
            'receiver_destination_id' => (int) $order->shippingAddress->komerce_destination_id,
            'receiver_address' => $order->shippingAddress->full_address,
            'receiver_email'   => 'customer@email.com', 
            'shipping'         => $shippingCode, 
            'shipping_type'    => $shippingType, 
            'payment_method'   => 'COD', 
            'shipping_cost'    => $shippingCost,
            'service_fee'      => (int) $serviceFee,
            'grand_total'      => (int) $codValue,
            'cod_value'        => (int) $codValue,
            'order_details'    => $itemsPayload
        ];
    }
}