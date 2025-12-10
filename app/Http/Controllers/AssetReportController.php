<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\AssetMovement;
use App\Models\AssetRetirement;
use App\Models\Departments;
use App\Models\Division;
use App\Models\Location;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AssetReportController extends Controller
{
    public function dashboard()
    {
        $totalAssets = Asset::count();
        $availableAssets = Asset::where('status', 'Available')->count();
        $assignedAssets = Asset::where('status', 'Assigned')->count();
        $maintenanceAssets = Asset::where('status', 'Maintenance')->count();
        $retiredAssets = Asset::where('status', 'Retired')->count();

        $assetsByCategory = Asset::with('category')
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->category->name ?? 'Uncategorized' => $item->count];
            });

        $assetsByStatus = Asset::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $assetsByDepartment = Asset::with('department')
            ->selectRaw('department_id, count(*) as count')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->department->dept_name ?? 'N/A' => $item->count];
            })
            ->sortDesc()
            ->take(10);

        $recentMovements = AssetMovement::with(['asset.category', 'movedBy'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentMaintenance = AssetMaintenance::with(['asset.category'])
            ->orderBy('date_performed', 'desc')
            ->take(10)
            ->get();

        $upcomingWarrantyExpiry = Asset::whereNotNull('warranty_expiry')
            ->where('warranty_expiry', '>=', now())
            ->where('warranty_expiry', '<=', now()->addMonths(3))
            ->orderBy('warranty_expiry', 'asc')
            ->take(10)
            ->get();

        return view('asset-management.reports.dashboard', compact(
            'totalAssets',
            'availableAssets',
            'assignedAssets',
            'maintenanceAssets',
            'retiredAssets',
            'assetsByCategory',
            'assetsByStatus',
            'assetsByDepartment',
            'recentMovements',
            'recentMaintenance',
            'upcomingWarrantyExpiry'
        ));
    }

    public function exportReport(Request $request)
    {
        $type = $request->get('type', 'all');

        switch ($type) {
            case 'maintenance':
                $data = AssetMaintenance::with(['asset.category', 'performedByUser'])->get();
                $filename = 'maintenance_report_' . date('Y-m-d_His') . '.xlsx';
                break;
            case 'movements':
                $data = AssetMovement::with(['asset.category', 'movedBy'])->get();
                $filename = 'movements_report_' . date('Y-m-d_His') . '.xlsx';
                break;
            case 'retired':
                $data = AssetRetirement::with(['asset.category', 'retiredBy'])->get();
                $filename = 'retired_assets_report_' . date('Y-m-d_His') . '.xlsx';
                break;
            default:
                $data = Asset::with(['category', 'department', 'division', 'location'])->get();
                $filename = 'assets_report_' . date('Y-m-d_His') . '.xlsx';
        }

        $export = new class($data, $type) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {
            protected $data;
            protected $type;

            public function __construct($data, $type)
            {
                $this->data = $data;
                $this->type = $type;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                if ($this->type === 'maintenance') {
                    return ['Asset Code', 'Category', 'Type', 'Description', 'Date Performed', 'Performed By', 'Cost', 'Notes'];
                } elseif ($this->type === 'movements') {
                    return ['Asset Code', 'From Department', 'To Department', 'From Location', 'To Location', 'Movement Date', 'Moved By', 'Notes'];
                } elseif ($this->type === 'retired') {
                    return ['Asset Code', 'Category', 'Retirement Date', 'Reason', 'Retired By', 'Notes'];
                } else {
                    return ['Asset Code', 'Category', 'Brand', 'Model', 'Status', 'Department', 'Entity', 'Location', 'Assigned To'];
                }
            }

            public function map($item): array
            {
                if ($this->type === 'maintenance') {
                    return [
                        $item->asset->asset_code ?? 'N/A',
                        $item->asset->category->name ?? 'N/A',
                        $item->maintenance_type,
                        $item->description,
                        $item->date_performed->format('Y-m-d'),
                        $item->performed_by ?? ($item->performedByUser->username ?? 'N/A'),
                        $item->cost ?? '0.00',
                        $item->notes ?? 'N/A',
                    ];
                } elseif ($this->type === 'movements') {
                    return [
                        $item->asset->asset_code ?? 'N/A',
                        $item->fromDepartment->dept_name ?? 'N/A',
                        $item->toDepartment->dept_name ?? 'N/A',
                        $item->fromLocation->name ?? 'N/A',
                        $item->toLocation->name ?? 'N/A',
                        $item->movement_date->format('Y-m-d'),
                        $item->movedBy->username ?? 'N/A',
                        $item->notes ?? 'N/A',
                    ];
                } elseif ($this->type === 'retired') {
                    return [
                        $item->asset->asset_code ?? 'N/A',
                        $item->asset->category->name ?? 'N/A',
                        $item->retirement_date->format('Y-m-d'),
                        $item->reason,
                        $item->retiredBy->username ?? 'N/A',
                        $item->notes ?? 'N/A',
                    ];
                } else {
                    return [
                        $item->asset_code,
                        $item->category->name ?? 'N/A',
                        $item->brand ?? 'N/A',
                        $item->model ?? 'N/A',
                        $item->status,
                        $item->department->dept_name ?? 'N/A',
                        $item->division->name ?? 'N/A',
                        $item->location->name ?? 'N/A',
                        $item->assignedTo->username ?? 'N/A',
                    ];
                }
            }
        };

        return Excel::download($export, $filename);
    }
}
