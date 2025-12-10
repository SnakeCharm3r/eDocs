<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ApprovedLocumRequestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        protected Collection $rows,
        protected ?string $department = null,
        protected ?int $year = null,
        protected ?int $month = null
    ) {}

    /** ===================== Sheet body (Details) ===================== */

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'CCBRT Code',          // NEW
            'Department',
            'Claim Month',
            'Submitted On',
            'HR Approved On',
            'Payment Month',
            'Days',
            'Hours',
            'Amount (TZS)',
        ];
    }

    public function map($req): array
    {
        // --- Employee (FULL NAME preferred) ---
        if ($req->user) {
            $full = trim(collect([
                $req->user->fname ?? null,
                $req->user->mname ?? null,
                $req->user->lname ?? null,
            ])->filter()->implode(' '));

            $employee = $full !== '' ? $full : ($req->user->username ?? 'N/A');
        } else {
            $employee = 'N/A';
        }

        // --- Staff CCBRT Code (safe fallback) ---
        $ccbrtCode = $req->user->ccbrt_code ?? '—';

        // Department
        $dept = $req->user?->department?->dept_name ?? 'N/A';

        // Submitted
        $submittedAt = $req->created_at ? Carbon::parse($req->created_at) : null;

        // HR Approved (look for HR Approval, status=1)
        $hrHist = optional($req->workflow)->histories
            ?->where('step_name', 'HR Approval')
            ?->where('status', 1)
            ?->sortByDesc('updated_at')
            ?->first();

        $hrApprovedAt = $hrHist
            ? ($hrHist->updated_at ? Carbon::parse($hrHist->updated_at)
                : ($hrHist->created_at ? Carbon::parse($hrHist->created_at) : null))
            : null;

        // Payment Month = month/year of HR approval
        $paymentMonth = $hrApprovedAt ? $hrApprovedAt->format('F Y') : '—';

        // Claim Month (robust parse)
        [$claimMonthNum, $claimYearNum] = self::parseClaimToMonthYear($req->locum_month, $req->created_at);
        $claimText = ($claimMonthNum && $claimYearNum)
            ? Carbon::create($claimYearNum, $claimMonthNum, 1)->format('F Y')
            : ($submittedAt ? $submittedAt->format('F Y') : '—');

        return [
            $employee,
            $ccbrtCode,                                 // NEW column value
            $dept,
            $claimText,
            $submittedAt ? $submittedAt->format('Y-m-d H:i') : '—',
            $hrApprovedAt ? $hrApprovedAt->format('Y-m-d H:i') : '—',
            $paymentMonth,
            (int)($req->number_of_days ?? 0),
            number_format(round((float)($req->total_hours ?? 0), 2), 2, '.', ','),
            number_format(round((float)($req->total_amount_payable ?? 0), 2), 2, '.', ','),
        ];
    }

    public function columnFormats(): array
    {
        // With the new "CCBRT Code" column, Hours is now column I and Amount is J.
        return [
            'I' => NumberFormat::FORMAT_NUMBER_00, // Hours
            'J' => NumberFormat::FORMAT_NUMBER_00, // Amount
        ];
    }

    /** ===================== Summary block appended after data ===================== */

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // Count data rows
                $dataCount = $this->rows->count();
                $startRow  = 2;                  // headings are on row 1
                $endRow    = $startRow + $dataCount - 1;

                // Build "By Claim Month (All)" summary from the same data set
                $items = $this->rows->map(function ($req) {
                    [$cm, $cy] = self::parseClaimToMonthYear($req->locum_month, $req->created_at);
                    if (!$cm || !$cy) {
                        $created = $req->created_at ? Carbon::parse($req->created_at) : null;
                        if ($created) {
                            $cm = (int)$created->month;
                            $cy = (int)$created->year;
                        }
                    }
                    $claimKey = ($cm && $cy) ? Carbon::create($cy, $cm, 1)->format('F Y') : '—';
                    return [
                        'claim_key' => $claimKey,
                        'hours'     => (float)($req->total_hours ?? 0),
                        'amount'    => (float)($req->total_amount_payable ?? 0),
                    ];
                });

                // Aggregate only months present (exclude '—')
                $byClaim = $items
                    ->where('claim_key', '!=', '—')
                    ->groupBy('claim_key')
                    ->sortKeys()
                    ->map(function (Collection $g) {
                        return [
                            'requests' => $g->count(),
                            'hours'    => round($g->sum('hours'), 2),
                            'amount'   => round($g->sum('amount'), 2),
                        ];
                    });

                // Where to start the summary (one blank row after the table)
                $summaryStart = $endRow + 2;

                $sheet = $event->sheet->getDelegate();

                // Style main header row (row 1)
                $headerRange = 'A1:J1'; // Adjust based on number of columns
                $headerStyle = $sheet->getStyle($headerRange);
                
                // Make header bold
                $headerStyle->getFont()->setBold(true);
                
                // Add background color (light grey)
                $headerStyle->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFD3D3D3'); // Light grey background
                
                // Add borders to header
                $headerStyle->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Center align header text
                $headerStyle->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                // Set header row height
                $sheet->getRowDimension(1)->setRowHeight(20);

                // Title row
                $sheet->setCellValue("A{$summaryStart}", 'By Claim Month (All)');
                // Make title bold
                $sheet->getStyle("A{$summaryStart}")->getFont()->setBold(true);

                // Header row for the mini-table
                $summaryHeaderRow = $summaryStart + 1;
                $sheet->setCellValue("A{$summaryHeaderRow}", 'Claim Month');
                $sheet->setCellValue("B{$summaryHeaderRow}", 'Submitted (Count)');
                $sheet->setCellValue("C{$summaryHeaderRow}", 'Hours');
                $sheet->setCellValue("D{$summaryHeaderRow}", 'Amount (TZS)');

                // Style summary header row
                $summaryHeaderStyle = $sheet->getStyle("A{$summaryHeaderRow}:D{$summaryHeaderRow}");
                $summaryHeaderStyle->getFont()->setBold(true);
                $summaryHeaderStyle->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE5E5E5'); // Slightly lighter grey
                $summaryHeaderStyle->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Data rows
                $r = $summaryHeaderRow + 1;
                foreach ($byClaim as $ckey => $agg) {
                    $sheet->setCellValue("A{$r}", $ckey);
                    $sheet->setCellValue("B{$r}", $agg['requests']);
                    $sheet->setCellValue("C{$r}", number_format($agg['hours'], 2, '.', ','));
                    $sheet->setCellValue("D{$r}", number_format($agg['amount'], 2, '.', ','));
                    $r++;
                }

                // Optional: light border for the summary table
                $lastSummaryRow = max($r - 1, $summaryHeaderRow); // handle empty safely
                $sheet->getStyle("A{$summaryHeaderRow}:D{$lastSummaryRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Optional: number formats
                $sheet->getStyle("C" . ($summaryHeaderRow + 1) . ":C{$lastSummaryRow}")
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("D" . ($summaryHeaderRow + 1) . ":D{$lastSummaryRow}")
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Add Total Amount Required to be Paid row
                $totalRow = $lastSummaryRow + 2;
                $totalAmount = $this->rows->sum('total_amount_payable');
                
                $sheet->setCellValue("A{$totalRow}", 'Total Amount Required to be Paid');
                $sheet->setCellValue("D{$totalRow}", number_format(round($totalAmount, 2), 2, '.', ','));
                
                // Make total row bold and highlighted
                $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$totalRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                
                // Optional: Add background color to total row
                $sheet->getStyle("A{$totalRow}:D{$totalRow}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE8F5E9'); // Light green background
            },
        ];
    }

    /** ===================== Helpers ===================== */

    /**
     * Parse "locum_month" into (month, year)
     * Accepts:
     *  - "September 2025"
     *  - "September" (assumes created_at year)
     *  - "2025-09", "2025/9", "9/2025"
     *  - Best-effort Carbon parse fallback
     */
    public static function parseClaimToMonthYear(?string $text, $createdAt): array
    {
        $created = $createdAt ? Carbon::parse($createdAt) : null;
        $fallbackYear = $created ? (int)$created->year : (int)date('Y');

        if (!$text || !is_string($text)) return [null, null];
        $t = trim($text);

        if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})$/i', $t, $m)) {
            return [Carbon::parse("1 {$m[1]}")->month, (int)$m[2]];
        }
        if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)$/i', $t, $m)) {
            return [Carbon::parse("1 {$m[1]}")->month, $fallbackYear];
        }
        if (preg_match('/^(\d{4})[-\/](\d{1,2})$/', $t, $m)) return [(int)$m[2], (int)$m[1]];
        if (preg_match('/^(\d{1,2})[-\/](\d{4})$/', $t, $m)) return [(int)$m[1], (int)$m[2]];

        try {
            $c = Carbon::parse($t);
            return [(int)$c->month, (int)$c->year];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }
}
