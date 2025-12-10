<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow;
use App\Models\Clearance_work_flow;

class NotificationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $notifications = collect([]);
        $notificationCount = 0;

        if (Auth::check()) {
            $user = Auth::user();
            
            // Get pending workflow requests
            $pendingWorkflows = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
                ->where('work_flow_histories.attended_by', $user->id)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->whereNotNull('workflows.ict_request_resource_id')
                          ->orWhereNotNull('workflows.hr_form')
                          ->orWhereNotNull('workflows.bank_form')
                          ->orWhereNotNull('workflows.heslb_form')
                          ->orWhereNotNull('workflows.nhif_form')
                          ->orWhereNotNull('workflows.id_form')
                          ->orWhereNotNull('workflows.change_request_id');
                    })
                    ->whereIn('work_flow_histories.status', [0, 1]);
                })
                ->orWhere(function ($query) {
                    $query->whereNotNull('workflows.requisition_id')
                          ->whereIn('work_flow_histories.requisition_status', [0, 1]);
                })
                ->select('workflows.*', 'work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname')
                ->orderBy('workflows.created_at', 'desc')
                ->take(5)
                ->get();
            
            foreach ($pendingWorkflows as $workflow) {
                $formType = $this->getFormType($workflow);
                $url = $this->getFormUrl($workflow);
                
                $notifications->push([
                    'type' => $formType,
                    'name' => trim(($workflow->fname ?? '') . ' ' . ($workflow->lname ?? '')),
                    'url' => $url,
                    'created_at' => $workflow->created_at,
                ]);
            }
            
            // Get pending clearance forms
            $pendingClearance = Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
                ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                ->where('clearance_work_flow_histories.attended_by', $user->id)
                ->where('clearance_work_flow_histories.status', 0)
                ->where('clearance_forms.status', '!=', 'rejected')
                ->select('clearance_work_flows.*', 'requesters.fname', 'requesters.lname', 'clearance_work_flows.requested_resource_id', 'clearance_work_flow_histories.step_name')
                ->orderBy('clearance_work_flows.created_at', 'desc')
                ->take(5)
                ->get();
            
            foreach ($pendingClearance as $clearance) {
                $stepName = $clearance->step_name ?? 'Clearance';
                $notifications->push([
                    'type' => 'Clearance Form',
                    'name' => trim(($clearance->fname ?? '') . ' ' . ($clearance->lname ?? '')),
                    'url' => route('exit_forms.show', ['id' => $clearance->requested_resource_id]),
                    'created_at' => $clearance->created_at,
                    'step' => $stepName,
                ]);
            }
            
            $notifications = $notifications->sortByDesc('created_at')->take(5);
            $notificationCount = $notifications->count();
        }

        $view->with('headerNotifications', $notifications);
        $view->with('notificationCount', $notificationCount);
    }

    private function getFormType($workflow)
    {
        if ($workflow->ict_request_resource_id) return 'ICT Access';
        if ($workflow->hr_form) return 'HR Form';
        if ($workflow->bank_form) return 'Bank Details';
        if ($workflow->heslb_form) return 'Loan Board';
        if ($workflow->nhif_form) return 'NHIF';
        if ($workflow->id_form) return 'ID Form';
        if ($workflow->change_request_id) return 'Change Request';
        if ($workflow->requisition_id) return 'RRF Request';
        return 'Form';
    }

    private function getFormUrl($workflow)
    {
        if ($workflow->ict_request_resource_id) return route('show_form', ['id' => $workflow->ict_request_resource_id]);
        if ($workflow->hr_form) return route('hr_form', ['id' => $workflow->hr_form]);
        if ($workflow->bank_form) return route('bank_form', ['id' => $workflow->bank_form]);
        if ($workflow->heslb_form) return route('heslb_form', ['id' => $workflow->heslb_form]);
        if ($workflow->nhif_form) return route('nhif_form', ['id' => $workflow->nhif_form]);
        if ($workflow->id_form) return route('id_form', ['id' => $workflow->id_form]);
        if ($workflow->change_request_id) return route('change_request.show', ['id' => $workflow->change_request_id]);
        if ($workflow->requisition_id) return route('requisitions.show', ['id' => $workflow->requisition_id]);
        return '#';
    }
}

