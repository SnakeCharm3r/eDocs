<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocumRequestsExport implements FromCollection, WithHeadings, WithStyles
{
    protected $requests;
    protected $department;

    public function __construct($requests, $department = null)
    {
        $this->requests = $requests;
        $this->department = $department;
    }

    public function collection()
    {
        $data = collect();

        if ($this->department) {
            // Single department: Filter requests and add total
            $departmentRequests = $this->requests->filter(function ($request) {
                return $request->user && $request->user->department && $request->user->department->dept_name === $this->department;
            });

            // Add individual request rows
            foreach ($departmentRequests as $request) {
                $data->push($this->mapRequest($request));
            }

            // Add total row
            $totalPayable = $departmentRequests->sum('total_amount_payable');
            $data->push([
                '', // Emp_Code
                'Total for ' . $this->department,
                '', // Department
                '', // Days
                '', // Education Level
                '', // LOCUM RATE
                number_format($totalPayable, 2),
            ]);
        } else {
            // All departments: Group by department
            $groupedRequests = $this->requests->groupBy(function ($request) {
                return $request->user && $request->user->department ? $request->user->department->dept_name ?? 'Unknown' : 'Unknown';
            })->sortKeys();

            foreach ($groupedRequests as $deptName => $deptRequests) {
                // Add individual request rows
                foreach ($deptRequests as $request) {
                    $data->push($this->mapRequest($request));
                }

                // Add subtotal row
                $totalPayable = $deptRequests->sum('total_amount_payable');
                $data->push([
                    '', // Emp_Code
                    'Subtotal for ' . $deptName,
                    '', // Department
                    '', // Days
                    '', // Education Level
                    '', // LOCUM RATE
                    number_format($totalPayable, 2),
                ]);

                // Add empty row for separation
                $data->push(array_fill(0, 7, ''));
            }

            // Add grand total
            $grandTotal = $this->requests->sum('total_amount_payable');
            $data->push([
                '', // Emp_Code
                'Grand Total',
                '', // Department
                '', // Days
                '', // Education Level
                '', // LOCUM RATE
                number_format($grandTotal, 2),
            ]);
        }

        return $data;
    }

    protected function mapRequest($request): array
    {
        $latestHistory = $request->workflow ? $request->workflow->histories->first() : null;
        $lineManagerName = $latestHistory && $latestHistory->user ? $latestHistory->user->username : 'N/A';
        $approveDate = $latestHistory && $latestHistory->locum_request_status == 3 ? $latestHistory->created_at->format('Y-m-d') : 'N/A';

        return [
            $request->user ? $request->user->ccbrt_code ?? 'N/A' : 'N/A', // Emp_Code
            $request->user ? trim(($request->user->fname ?? '') . ' ' . ($request->user->mname ?? '') . ' ' . ($request->user->lname ?? '')) : 'N/A', // Employee Names
            $request->user && $request->user->department ? $request->user->department->dept_name ?? 'N/A' : 'N/A', // Department
            $request->number_of_days ?? 0, // Days
            $request->locumAgreement ? $request->locumAgreement->education_level ?? 'N/A' : 'N/A', // Education Level
            number_format($request->locumAgreement->locum_rate ?? 0, 2), // LOCUM RATE
            number_format($request->total_amount_payable ?? 0, 2), // Total Amount Payable
        ];
    }

    public function headings(): array
    {
        return [
            'Emp_Code',
            'Employee Names',
            'Department',
            'Days',
            'Education Level',
            'LOCUM RATE',
            'Total Amount Payable',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $styles = [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE5E5E5']
                ]
            ]
        ];

        // Style subtotal and grand total rows
        for ($row = 2; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('B' . $row)->getValue();
            if (strpos($cellValue, 'Subtotal for') === 0 || $cellValue === 'Grand Total' || strpos($cellValue, 'Total for') === 0) {
                $styles[$row] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF0F0F0']
                    ]
                ];
            }
        }

        return $styles;
    }
}
