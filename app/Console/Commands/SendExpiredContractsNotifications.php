<?php

namespace App\Console\Commands;

use App\Http\Controllers\VendorContractController;
use Illuminate\Console\Command;
use App\Models\CcbrtContract;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\ContractsReport;

class SendExpiredContractsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:notify-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for expired contracts by department';

    /**
     * Execute the console command.
     * Alert flow: Line Manager → HEC (if no action) → Procurement (for followup)
     */
    public function handle(): int
    {
        $today = Carbon::now();

        // Get expired contracts that haven't been renewed
        $expiredContracts = CcbrtContract::with(['department','vendor','creator'])
            ->where('end_date', '<', $today)
            ->where('status', 'expired')
            ->where(function($query) {
                $query->whereNull('renewal_status')
                      ->orWhere('renewal_status', 'not_renewed');
            })
            ->get();

        if ($expiredContracts->isEmpty()) {
            $this->info('No expired contracts requiring alerts.');
            return Command::SUCCESS;
        }

        $contractsByDepartment = $expiredContracts->groupBy('department_id');

        foreach ($contractsByDepartment as $departmentId => $contracts) {
            $department = \App\Models\Departments::find($departmentId);
            
            // Step 1: Alert Line Managers first
            $lineManagers = User::role('line-manager')
                ->where('deptId', $departmentId)
                ->get();

            if ($lineManagers->isNotEmpty()) {
                foreach ($lineManagers as $lineManager) {
                    try {
                        Mail::to($lineManager->email)->queue(new ContractsReport($contracts, 'Line Manager Alert: Contract(s) have expired and require your attention.'));
                        $this->info("Expired contract alert sent to Line Manager: {$lineManager->email}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send alert to Line Manager {$lineManager->email}: " . $e->getMessage());
                    }
                }
            }

            // Step 2: If department has HEC, also alert HEC (they monitor if Line Manager doesn't act)
            if ($department && $department->hec_id) {
                $hec = \App\Models\Hec::find($department->hec_id);
                if ($hec) {
                    $hecLevelName = strtoupper(trim($hec->hec_level_name));
                    $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                    $roleSlug = $roleMap[$hecLevelName] ?? 'cms';
                    
                    $hecMembers = User::role($roleSlug)->get();
                    foreach ($hecMembers as $hecMember) {
                        try {
                            Mail::to($hecMember->email)->queue(new ContractsReport($contracts, 'HEC Alert: Contract(s) have expired. Please monitor if Line Manager takes action.'));
                            $this->info("Expired contract alert sent to HEC Member: {$hecMember->email}");
                        } catch (\Exception $e) {
                            $this->error("Failed to send alert to HEC Member {$hecMember->email}: " . $e->getMessage());
                        }
                    }
                }
            }

            // Step 3: Alert Procurement Officers (they can initiate renewal if told to follow up)
            $procurementOfficers = User::role('procurement officer')->get();
            foreach ($procurementOfficers as $procurementOfficer) {
                try {
                    Mail::to($procurementOfficer->email)->queue(new ContractsReport($contracts, 'Procurement Alert: Contract(s) have expired. You may be asked to initiate renewal process.'));
                    $this->info("Expired contract alert sent to Procurement Officer: {$procurementOfficer->email}");
                } catch (\Exception $e) {
                    $this->error("Failed to send alert to Procurement Officer {$procurementOfficer->email}: " . $e->getMessage());
                }
            }
        }

        $this->info('Expired contract notifications sent successfully.');

        return Command::SUCCESS;
    }
}
