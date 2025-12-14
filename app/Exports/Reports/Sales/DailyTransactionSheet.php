<?php

namespace App\Exports\Reports\Sales;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DailyTransactionSheet implements 
    FromQuery, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithDrawings, 
    WithTitle, 
    ShouldAutoSize, 
    WithCustomStartCell,
    WithColumnWidths,
    WithEvents // <--- Tambahan Penting untuk Custom Layout
{
    protected $startDate;
    protected $endDate;
    protected $summary;

    public function __construct($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        
        // Hitung Summary dulu untuk ditampilkan di Excel
        $this->summary = Order::where('order_status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(total_amount) as gross_revenue,
                COUNT(id) as total_orders,
                AVG(total_amount) as aov,
                SUM(shipping_cost) as shipping
            ")->first();
    }

    public function title(): string
    {
        return 'Sales Report';
    }

    public function query()
    {
        return Order::query()
            ->with(['user'])
            ->where('order_status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc');
    }

    // Geser Tabel Data ke Baris 16 (Beri ruang untuk Header & Summary Cards)
    public function startCell(): string
    {
        return 'A16';
    }

    public function map($order): array
    {
        return [
            $order->created_at->format('d/m/Y H:i'),
            $order->order_number,
            $order->user ? $order->user->name : 'Guest',
            $order->shipping_cost,
            $order->total_amount,
            strtoupper($order->payment_status),
        ];
    }

    public function headings(): array
    {
        return [
            'DATE TIME',
            'INVOICE NO',
            'CUSTOMER',
            'SHIPPING (IDR)',
            'TOTAL PAID (IDR)',
            'STATUS',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 30,
            'D' => 20,
            'E' => 25,
            'F' => 15,
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $path = public_path('images/logo.png');
        
        if (file_exists($path)) {
            $drawing->setPath($path);
            $drawing->setHeight(80);
            $drawing->setCoordinates('E2'); // Posisi Logo di Kanan Atas
            $drawing->setOffsetX(50);
        }

        return $drawing;
    }

    /**
     * DISINI KITA "MELUKIS" MANUAL HEADER & SUMMARY CARDS
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $gross = $this->summary->gross_revenue ?? 0;
                $orders = $this->summary->total_orders ?? 0;
                $aov = $this->summary->aov ?? 0;
                $ship = $this->summary->shipping ?? 0;

                // 1. BACKGROUND HEADER PINK (Baris 1-7)
                $sheet->getDelegate()->getStyle('A1:F7')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF0F5'], // #FFF0F5 (Lavender Blush)
                    ],
                ]);

                // 2. JUDUL LAPORAN
                $sheet->setCellValue('A2', 'SALES REVENUE REPORT');
                $sheet->setCellValue('A3', 'Period: ' . $this->startDate . ' - ' . $this->endDate);
                $sheet->setCellValue('A4', 'Generated on: ' . now()->format('d M Y H:i'));

                // Styling Judul
                $sheet->getDelegate()->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 20, 'color' => ['argb' => 'FF881337']], // Rose-900
                ]);

                // 3. MEMBUAT "SUMMARY CARDS" (Baris 9-13)
                // Kita manual isi cell untuk Gross Revenue, Total Order, dll
                
                // --- CARD 1: GROSS REVENUE (Cell A10) ---
                $sheet->setCellValue('A10', "GROSS REVENUE\nRp " . number_format($gross, 0, ',', '.'));
                $sheet->getDelegate()->getStyle('A10')->applyFromArray($this->cardStyle());
                $sheet->getDelegate()->getRowDimension('10')->setRowHeight(50); // Tinggi Card

                // --- CARD 2: TOTAL ORDERS (Cell B10) ---
                $sheet->setCellValue('B10', "TOTAL ORDERS\n" . number_format($orders));
                $sheet->getDelegate()->getStyle('B10')->applyFromArray($this->cardStyle());

                // --- CARD 3: AVG VALUE (Cell C10) ---
                $sheet->setCellValue('C10', "AVG. ORDER VALUE\nRp " . number_format($aov, 0, ',', '.'));
                $sheet->getDelegate()->getStyle('C10')->applyFromArray($this->cardStyle());

                // --- CARD 4: SHIPPING (Cell D10) ---
                $sheet->setCellValue('D10', "SHIPPING INCOME\nRp " . number_format($ship, 0, ',', '.'));
                $sheet->getDelegate()->getStyle('D10')->applyFromArray($this->cardStyle());
            },
        ];
    }

    // Helper Style untuk Card
    private function cardStyle()
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF475569']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true, 'indent' => 1],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']], // Slate-50
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Header Tabel Data (Baris 16)
        $sheet->getStyle('A16:F16')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], // Putih
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE11D48']], // Rose-600
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Format Uang di Kolom Tabel
        $sheet->getStyle('D17:D' . $sheet->getHighestRow())->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle('E17:E' . $sheet->getHighestRow())->getNumberFormat()->setFormatCode('"Rp "#,##0');

        // Border Tipis untuk Data
        $sheet->getStyle('A16:F' . $sheet->getHighestRow())->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
    }
}