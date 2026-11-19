<?php

namespace App\Exports\Director;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MediaHouseStatusExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect([]);
        
        // Add media houses exceeding thresholds
        foreach ($this->data['exceeding_thresholds'] as $house) {
            $rows->push([
                'Media House' => $house->applicant->name ?? 'Unknown',
                'Staff Count' => $house->staff_accreditations_count,
                'Status' => $house->status,
            ]);
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return ['Media House', 'Staff Count', 'Status'];
    }

    public function title(): string
    {
        return 'Media House Status Report';
    }
}
