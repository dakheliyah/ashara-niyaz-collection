<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SummaryReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $summaryData;
    protected $currencyCodes;
    protected $headings;

    public function __construct($summaryData, $currencyCodes)
    {
        $this->summaryData = $summaryData;
        $this->currencyCodes = $currencyCodes;

        // Prepare headings dynamically
        $this->headings = [
            'Collector Name',
            'Collector ITS',
            'Total Sessions',
            'Total Donations',
        ];
        foreach ($this->currencyCodes as $code) {
            $this->headings[] = strtoupper($code);
        }
    }

    public function collection()
    {
        return collect($this->summaryData);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $mappedRow = [
            $row['collector_name'],
            $row['collector_its'],
            $row['total_sessions'],
            $row['total_donations'],
        ];

        foreach ($this->currencyCodes as $code) {
            // Append each currency total, defaulting to 0 if not present
            $mappedRow[] = $row[$code] ?? 0;
        }

        return $mappedRow;
    }
}
