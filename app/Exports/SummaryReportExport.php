<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SummaryReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Session ID',
            'Session Start',
            'Session End',
            'Total Donations',
            'Total Amount',
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        $totalAmountStrings = [];
        foreach ($row->total_amount as $amount) {
            $totalAmountStrings[] = number_format($amount->total, 2) . ' ' . $amount->currency_code;
        }

        return [
            $row->session_id,
            $row->started_at,
            $row->ended_at,
            $row->total_donations,
            implode(', ', $totalAmountStrings),
        ];
    }
}
