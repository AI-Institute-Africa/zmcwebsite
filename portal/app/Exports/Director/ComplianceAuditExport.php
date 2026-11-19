<?php

namespace App\Exports\Director;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComplianceAuditExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect([]);
        
        // Add category reassignments
        foreach ($this->data['category_reassignments']['by_staff'] as $staff) {
            $rows->push([
                'Staff Member' => $staff->user->name ?? 'Unknown',
                'Action Type' => 'Category Reassignment',
                'Count' => $staff->action_count,
            ]);
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return ['Staff Member', 'Action Type', 'Count'];
    }

    public function title(): string
    {
        return 'Compliance Audit Report';
    }
}
