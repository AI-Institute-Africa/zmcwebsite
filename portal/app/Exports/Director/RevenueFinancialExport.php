<?php

namespace App\Exports\Director;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RevenueFinancialExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect([]);
        
        // Add revenue by service type
        foreach ($this->data['revenue_by_service'] as $service) {
            $rows->push([
                'Service Type' => $service->service_type,
                'Total Revenue' => $service->total_revenue,
                'Transaction Count' => $service->transaction_count,
            ]);
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return ['Service Type', 'Total Revenue', 'Transaction Count'];
    }

    public function title(): string
    {
        return 'Revenue Financial Report';
    }
}
