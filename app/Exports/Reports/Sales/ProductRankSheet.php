<?php

namespace App\Exports\Reports\Sales;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductRankSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
    }

    public function title(): string
    {
        return 'Best Selling Products';
    }

    public function query()
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('orders.order_status', 'completed')
            ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
            ->select(
                'products.name',
                'product_variants.volume',
                'product_variants.sku',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('product_variants.id', 'products.name', 'product_variants.volume', 'product_variants.sku')
            ->orderByDesc('qty_sold');
    }

    public function map($row): array
    {
        return [
            $row->sku,
            $row->name . ' (' . $row->volume . ')',
            $row->qty_sold,
            $row->revenue,
        ];
    }

    public function headings(): array
    {
        return ['SKU', 'PRODUCT NAME', 'QTY SOLD', 'REVENUE (IDR)'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFBE185D']],
        ]);
        $sheet->getStyle('D2:D' . $sheet->getHighestRow())->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle('A1:D' . $sheet->getHighestRow())->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
}