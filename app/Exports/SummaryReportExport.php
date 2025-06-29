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
            'Session ID',
            'Session Start',
            'Total Donations',
            'Event ID',
            'Event Name',
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
            $row['session_id'],
            $row['session_start'],
            $row['total_donations'],
            $row['event_id'],
            $row['event_name'],
        ];

        foreach ($this->currencyCodes as $code) {
            $mappedRow[] = $row[$code] ?? 0;
        }

        return $mappedRow;
    }
}
