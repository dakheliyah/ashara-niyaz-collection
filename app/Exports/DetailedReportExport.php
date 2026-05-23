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
        $donorIts = $donation->donor_its_id ?? 'N/A';
        $donorName = optional($donation->donor)->fullname ?? 'N/A';

        return [
            $donation->id,
            optional(optional($donation->collectorSession)->collector)->fullname ?? 'N/A',
            optional($donation->collectorSession)->id,
            optional($donation->collectorSession->event)->id,
            optional($donation->collectorSession->event)->name,
            $donation->donated_at,
            $donorIts,
            $donorName,
            optional($donation->donationType)->name,
            $donation->amount,
            optional($donation->currency)->code,
        ];
    }
}
