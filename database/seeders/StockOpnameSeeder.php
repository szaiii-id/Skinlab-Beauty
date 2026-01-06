<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockHistory;
use Carbon\Carbon;

class StockOpnameSeeder extends Seeder
{
    public function run()
    {
        $variants = ProductVariant::all();
        $adminId = User::first()->id ?? 1;

        if ($variants->isEmpty()) return;

        $this->command->info("🚀 Seeding Stock Opname...");

        // ==========================================
        // SKENARIO 1: OPNAME SELESAI (BULAN LALU)
        // ==========================================
        $pastDate = Carbon::now()->subMonth();
        
        $completedOpname = StockOpname::create([
            'opname_number' => 'SO-' . $pastDate->timestamp,
            'opname_date'   => $pastDate,
            'status'        => 'completed',
            'notes'         => 'Opname Rutin Bulanan (Dummy)',
            'created_by'    => $adminId,
            'completed_at'  => $pastDate->copy()->addHours(5),
            'created_at'    => $pastDate,
            'updated_at'    => $pastDate,
        ]);

        foreach ($variants as $variant) {
            // Simulasi: System mencatat 50, tapi fisik...
            $systemQty = rand(20, 100); 
            
            // 80% Cocok, 10% Hilang, 10% Lebih
            $chance = rand(1, 100);
            if ($chance <= 80) {
                $physicalQty = $systemQty; // Klop
            } elseif ($chance <= 90) {
                $physicalQty = $systemQty - rand(1, 3); // Hilang/Rusak
            } else {
                $physicalQty = $systemQty + rand(1, 5); // Kelebihan (Bonus supplier/salah input)
            }

            // 1. Buat Item Opname
            StockOpnameItem::create([
                'stock_opname_id'    => $completedOpname->id,
                'product_variant_id' => $variant->id,
                'system_qty'         => $systemQty,
                'physical_qty'       => $physicalQty,
                'created_at'         => $pastDate,
                'updated_at'         => $pastDate,
            ]);

            // 2. Jika ada selisih, catat di History (PENTING)
            $diff = $physicalQty - $systemQty;
            if ($diff !== 0) {
                StockHistory::create([
                    'product_variant_id' => $variant->id,
                    'type'               => 'opname',
                    'qty_change'         => $diff,
                    'current_stock'      => $physicalQty, // Anggap stok akhir jadi ini
                    'note'               => 'Stock Opname Adjustment (Seeder)',
                    'reference_id'       => $completedOpname->id,
                    'user_id'            => $adminId,
                    'created_at'         => $pastDate->copy()->addHours(5),
                ]);
            }
        }

        // ==========================================
        // SKENARIO 2: OPNAME SEDANG BERJALAN (HARI INI)
        // ==========================================
        $processingOpname = StockOpname::create([
            'opname_number' => 'SO-' . time(),
            'opname_date'   => now(),
            'status'        => 'processing', // Masih jalan
            'notes'         => 'Opname Mendadak (Sedang dihitung)',
            'created_by'    => $adminId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        foreach ($variants as $variant) {
            // Snapshot stok sistem SAAT INI
            StockOpnameItem::create([
                'stock_opname_id'    => $processingOpname->id,
                'product_variant_id' => $variant->id,
                'system_qty'         => $variant->stock, // Ambil stok real dari DB
                'physical_qty'       => null, // Belum diisi user
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        $this->command->info("✅ Stock Opname (Completed & Processing) berhasil dibuat.");
    }
}