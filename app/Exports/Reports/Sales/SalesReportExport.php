<?php

namespace App\Exports\Reports\Sales;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            // Sheet 1: Detail Transaksi
            new DailyTransactionSheet($this->startDate, $this->endDate),
            
            // Sheet 2: Ranking Produk
            new ProductRankSheet($this->startDate, $this->endDate),
        ];
    }
}