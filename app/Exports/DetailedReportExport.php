<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DetailedReportExport implements FromCollection, WithHeadings, WithMapping
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
            'Donation ID',
            'Collector Name',
            'Session ID',
            'Event ID',
            'Event Name',
            'Date',
            'Donor ITS',
            'Donor Name',
            'Donation Type',
            'Amount',
            'Currency',
        ];
    }

    /**
     * @param mixed $donation
     *
     * @return array
     */
    public function map($donation): array
    {
        return [
            $donation->id,
            $donation->collectorSession->collector->fullname ?? 'N/A',
            $donation->collectorSession->id,
            optional($donation->collectorSession->event)->id,
            optional($donation->collectorSession->event)->name,
            $donation->donated_at,
            $donation->donor_its,
            $donation->donor->name ?? 'N/A',
            $donation->donationType->name,
            $donation->amount,
            $donation->currency->code,
        ];
    }
}
