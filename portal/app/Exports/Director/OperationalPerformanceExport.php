<?php

namespace App\Exports\Director;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class OperationalPerformanceExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect([]);
        
        // Add staff performance data
        foreach ($this->data['applications_processed'] as $officer) {
            $rows->push([
                'Staff Member' => $officer->name,
                'Applications Processed' => $officer->processed_count,
                'Role' => 'Officer',
            ]);
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return ['Staff Member', 'Applications Processed', 'Role'];
    }

    public function title(): string
    {
        return 'Operational Performance Report';
    }
}
