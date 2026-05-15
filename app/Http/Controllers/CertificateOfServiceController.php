<?php

namespace App\Http\Controllers;

use App\Mail\CertificateOfServiceInitialized;
use App\Models\CertificateOfService;
use App\Models\ClearanceForm;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CertificateOfServiceController extends Controller
{
    private const DEFAULT_HR_DELIVERY_EMAIL = 'HRTeam@ccbrt.org';
    private const CERTIFICATE_LOGO_SETTING_KEY        = 'certificate_service_logo_path';
    private const CERTIFICATE_STAMP_SETTING_KEY       = 'certificate_service_stamp_path';
    private const CERTIFICATE_STAMP_POSITION_SETTING  = 'certificate_service_stamp_position';
    private const TEMPLATE_SETTINGS = [
        'title' => 'certificate_service_template_title',
        'subtitle' => 'certificate_service_template_subtitle',
        'intro_text' => 'certificate_service_template_intro_text',
        'start_date_label' => 'certificate_service_template_start_date_label',
        'position_label' => 'certificate_service_template_position_label',
        'end_date_label' => 'certificate_service_template_end_date_label',
        'closing_text' => 'certificate_service_template_closing_text',
        'signer_name' => 'certificate_service_template_signer_name',
        'signer_title' => 'certificate_service_template_signer_title',
    ];

    private function getCooSignatory(): ?User
    {
        return User::role('coo')
            ->where('status', 'active')
            ->whereNotNull('signature')
            ->orderBy('id')
            ->first();
    }

    private function getCertificateLogoSetting(): ?string
    {
        return $this->getSystemSetting(self::CERTIFICATE_LOGO_SETTING_KEY);
    }

    private function getCertificateStampSetting(): ?string
    {
        return $this->getSystemSetting(self::CERTIFICATE_STAMP_SETTING_KEY);
    }

    private function resolveCertificateStampWebSrc(): ?string
    {
        $storedPath = $this->getCertificateStampSetting();

        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            return asset('storage/' . $storedPath);
        }

        return null;
    }

    private function resolveCertificateStampPdfSrc(): ?string
    {
        $storedPath = $this->getCertificateStampSetting();

        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            return $this->toPdfImageSource(storage_path('app/public/' . $storedPath));
        }

        return null;
    }

    private function resolveSignaturePdfSrc(?string $signaturePath): ?string
    {
        if (!$signaturePath) {
            return null;
        }

        // Already a full data URI
        if (str_starts_with($signaturePath, 'data:image')) {
            return $signaturePath;
        }

        // File stored on disk (e.g. uploaded image path)
        $absolutePath = storage_path('app/public/' . $signaturePath);
        if (file_exists($absolutePath)) {
            return $this->toPdfImageSource($absolutePath);
        }

        // Raw base64 — the system strips the data URI prefix before saving to users.signature
        return 'data:image/png;base64,' . $signaturePath;
    }

    private function getStampPosition(): string
    {
        $pos = $this->getSystemSetting(self::CERTIFICATE_STAMP_POSITION_SETTING, 'right');
        return in_array($pos, ['center', 'right']) ? $pos : 'right';
    }

    private function getSystemSetting(string $key, ?string $default = null): ?string
    {
        $value = DB::table('system_settings')
            ->whereRaw('`key` = ?', [$key])
            ->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    private function resolveCertificateLogoWebSrc(): string
    {
        $storedPath = $this->getCertificateLogoSetting();

        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            return asset('storage/' . $storedPath);
        }

        if (file_exists(public_path('assets/img/ccbrt.jpg'))) {
            return asset('assets/img/ccbrt.jpg');
        }

        return asset('images/logo.png');
    }

    private function resolveCertificateLogoPdfSrc(): ?string
    {
        $storedPath = $this->getCertificateLogoSetting();

        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            return $this->toPdfImageSource(storage_path('app/public/' . $storedPath));
        }

        if (file_exists(public_path('assets/img/ccbrt.jpg'))) {
            return $this->toPdfImageSource(public_path('assets/img/ccbrt.jpg'));
        }

        $fallback = public_path('images/logo.png');

        return file_exists($fallback) ? $this->toPdfImageSource($fallback) : null;
    }

    private function toPdfImageSource(string $absolutePath): ?string
    {
        if (!file_exists($absolutePath)) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

        if ($mimeType === 'image/webp') {
            if (!function_exists('imagecreatefromwebp') || !function_exists('imagepng')) {
                return null;
            }

            $image = @imagecreatefromwebp($absolutePath);
            if (!$image) {
                return null;
            }

            ob_start();
            imagepng($image);
            $pngBinary = ob_get_clean();
            imagedestroy($image);

            if (!$pngBinary) {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode($pngBinary);
        }

        $binary = @file_get_contents($absolutePath);

        if ($binary === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
    }

    private function storeCertificateLogo(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'webp' && function_exists('imagecreatefromwebp') && function_exists('imagepng')) {
            $image = @imagecreatefromwebp($file->getRealPath());

            if ($image) {
                $directory = storage_path('app/public/certificate-settings');
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $filename = 'certificate-logo-' . now()->format('YmdHis') . '.png';
                $targetPath = $directory . DIRECTORY_SEPARATOR . $filename;
                imagepng($image, $targetPath);
                imagedestroy($image);

                return 'certificate-settings/' . $filename;
            }
        }

        return $file->store('certificate-settings', 'public');
    }

    private function setSystemSetting(string $key, string $value): void
    {
        $exists = DB::table('system_settings')
            ->whereRaw('`key` = ?', [$key])
            ->exists();

        if ($exists) {
            DB::table('system_settings')
                ->whereRaw('`key` = ?', [$key])
                ->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function getTemplateSettings(): array
    {
        return [
            'title' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['title'], 'Certificate of Service'),
            'subtitle' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['subtitle'], 'To Whom It May Concern'),
            'intro_text' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['intro_text'], 'This is to certify that {name} worked at CCBRT as detailed below:'),
            'start_date_label' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['start_date_label'], 'Starting date'),
            'position_label' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['position_label'], 'Position held'),
            'end_date_label' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['end_date_label'], 'End Date'),
            'closing_text' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['closing_text'], 'Any further information may be obtained from HR department through the undersigned.'),
            'signer_name' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['signer_name'], ''),
            'signer_title' => $this->getSystemSetting(self::TEMPLATE_SETTINGS['signer_title'], 'Chief Operating Officer'),
        ];
    }

    private function renderTemplateText(string $text, CertificateOfService $certificate): string
    {
        $staffName = trim(($certificate->staff?->fname ?? '') . ' ' . ($certificate->staff?->lname ?? ''));

        return strtr($text, [
            '{name}' => '<strong>' . e($staffName) . '</strong>',
            '{staff_code}' => (string) ($certificate->staff?->ccbrt_code ?? ''),
            '{position}' => (string) ($certificate->position_held ?? ''),
            '{department}' => (string) ($certificate->department ?? ''),
            '{start_date}' => (string) ($certificate->date_of_joining?->format('jS F Y') ?? ''),
            '{end_date}' => (string) ($certificate->last_working_day?->format('jS F Y') ?? ''),
            '{issue_date}' => (string) ($certificate->issue_date?->format('jS F Y') ?? ''),
        ]);
    }

    private function canManage(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super-admin') || $user->hasRole('coo');
    }

    private function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super-admin') || $user->hasRole('coo') || $user->hasRole('hr');
    }

    private function isHR(): bool
    {
        return auth()->user()->hasRole('hr') && !auth()->user()->hasRole('coo') && !auth()->user()->hasRole('super-admin');
    }

    public function index()
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        $certificates = CertificateOfService::with(['staff', 'creator', 'approver', 'initializer'])
            ->latest()->get();

        // Clearances notified by HR (cos_notified_at set) but no certificate created yet
        $existingCertUserIds = CertificateOfService::pluck('user_id')->all();
        $pendingCosRequests = \App\Models\ClearanceForm::with(['user.department', 'user.jobTitle', 'workflow'])
            ->whereNotNull('cos_notified_at')
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->whereNotIn('userId', $existingCertUserIds)
            ->orderByDesc('cos_notified_at')
            ->get();

        return view('certificate-of-service.index', compact('certificates', 'pendingCosRequests'));
    }

    public function create(Request $request)
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        // Only show staff whose clearance has been initialized by HR (cos_notified_at set)
        $clearances = \App\Models\ClearanceForm::whereNotNull('cos_notified_at')
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->get(['userId', 'personal_email', 'date_of_hire', 'last_working_day']);

        $initializedUserIds = $clearances->pluck('userId')->unique()->all();

        // Maps keyed by userId for auto-filling form fields from clearance data
        $personalEmails    = $clearances->mapWithKeys(fn($c) => [$c->userId => $c->personal_email])->all();
        $clearanceJoinDates = $clearances->mapWithKeys(fn($c) => [$c->userId => $c->date_of_hire ? \Carbon\Carbon::parse($c->date_of_hire)->format('Y-m-d') : null])->all();
        $clearanceEndDates  = $clearances->mapWithKeys(fn($c) => [$c->userId => $c->last_working_day ? \Carbon\Carbon::parse($c->last_working_day)->format('Y-m-d') : null])->all();

        $eligibleStaff = User::whereIn('id', $initializedUserIds)
            ->whereDoesntHave('certificateOfService')
            ->with(['department', 'jobTitle', 'employmentType'])
            ->orderBy('fname')
            ->get();

        $latestContracts = \App\Models\Contract::whereIn('user_id', $initializedUserIds)
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $coo = $this->getCooSignatory();
        $preselectedUserId = (int) $request->query('user_id', 0) ?: null;
        $defaultRecipientEmail = self::DEFAULT_HR_DELIVERY_EMAIL;

        return view('certificate-of-service.create', compact('eligibleStaff', 'latestContracts', 'coo', 'preselectedUserId', 'personalEmails', 'clearanceJoinDates', 'clearanceEndDates', 'defaultRecipientEmail'));
    }

    public function templateSettings()
    {
        if (!$this->canManage()) {
            abort(403);
        }

        return view('certificate-of-service.template-settings', [
            'templateSettings'    => $this->getTemplateSettings(),
            'certificateLogoSrc'  => $this->resolveCertificateLogoWebSrc(),
            'certificateStampSrc' => $this->resolveCertificateStampWebSrc(),
            'stampPosition'       => $this->getStampPosition(),
        ]);
    }

    public function updateTemplateSettings(Request $request)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:255'],
            'intro_text' => ['required', 'string', 'max:2000'],
            'start_date_label' => ['required', 'string', 'max:100'],
            'position_label' => ['required', 'string', 'max:100'],
            'end_date_label' => ['required', 'string', 'max:100'],
            'closing_text' => ['required', 'string', 'max:2000'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['required', 'string', 'max:255'],
            'certificate_logo'     => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'certificate_stamp'    => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'stamp_position'       => ['nullable', 'string', 'in:center,right'],
        ]);

        foreach (self::TEMPLATE_SETTINGS as $field => $settingKey) {
            $this->setSystemSetting($settingKey, (string) ($data[$field] ?? ''));
        }

        if ($request->hasFile('certificate_logo')) {
            $currentLogo = $this->getCertificateLogoSetting();
            $storedPath = $this->storeCertificateLogo($request->file('certificate_logo'));

            if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }

            $this->setSystemSetting(self::CERTIFICATE_LOGO_SETTING_KEY, $storedPath);
        }

        $this->setSystemSetting(self::CERTIFICATE_STAMP_POSITION_SETTING, $data['stamp_position'] ?? 'right');

        if ($request->hasFile('certificate_stamp')) {
            $currentStamp = $this->getCertificateStampSetting();
            $storedPath = $this->storeCertificateLogo($request->file('certificate_stamp'));

            if ($currentStamp && Storage::disk('public')->exists($currentStamp)) {
                Storage::disk('public')->delete($currentStamp);
            }

            $this->setSystemSetting(self::CERTIFICATE_STAMP_SETTING_KEY, $storedPath);
        }

        return back()->with('success', 'Certificate template updated successfully.');
    }

    public function store(Request $request)
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        $data = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'issue_date'        => 'required|date',
            'date_of_joining'   => 'required|date',
            'last_working_day'  => 'required|date',
            'position_held'     => 'required|string|max:255',
            'department'        => 'required|string|max:255',
            'duties_description'=> 'nullable|string',
            'remarks'           => 'nullable|string',
            'send_email_to'     => 'nullable|email|max:255',
        ]);

        $sendEmailTo = $data['send_email_to'] ?? null;
        unset($data['send_email_to']);

        $data['created_by']         = auth()->id();
        $data['certificate_number'] = CertificateOfService::generateCertificateNumber();
        $data['status']             = 'draft';

        // Store COO signature path if available
        $coo = $this->getCooSignatory();
        if ($coo && $coo->signature) {
            $data['coo_signature_path'] = $coo->signature;
        }

        // Creator's signature as approver signature
        $creator = auth()->user();
        if ($creator->signature) {
            $data['approver_signature_path'] = $creator->signature;
            $data['approved_by']             = $creator->id;
        }

        $certificate = CertificateOfService::create($data);
        $certificate->load(['staff.department', 'staff.jobTitle', 'creator', 'approver']);

        // Send certificate PDF to staff email if provided
        if ($sendEmailTo) {
            try {
                $cooForPdf = $coo?->load('jobTitle');
                $pdf = Pdf::loadView('certificate-of-service.pdf', [
                    'certificate'          => $certificate,
                    'certificateLogoSrc'   => $this->resolveCertificateLogoPdfSrc(),
                    'certificateStampSrc'  => $this->resolveCertificateStampPdfSrc(),
                    'cooSignatureSrc'      => $this->resolveSignaturePdfSrc($certificate->coo_signature_path),
                    'approverSignatureSrc' => $this->resolveSignaturePdfSrc($certificate->approver_signature_path),
                    'cooName'              => $cooForPdf ? trim($cooForPdf->fname . ' ' . $cooForPdf->lname) : null,
                    'cooTitle'             => $cooForPdf?->jobTitle?->job_title ?? null,
                    'stampPosition'        => $this->getStampPosition(),
                    'templateSettings'     => $this->getTemplateSettings(),
                    'renderTemplateText'   => fn (string $text) => $this->renderTemplateText($text, $certificate),
                ]);
                $pdf->setPaper('A4', 'landscape');
                $pdf->setOptions([
                    'defaultFont'          => 'dejavu sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'dpi'                  => 96,
                ]);

                $pdfPath = 'certificate-deliveries/' . $certificate->certificate_number . '.pdf';
                Storage::disk('local')->put($pdfPath, $pdf->output());

                Mail::to($sendEmailTo)->queue(new \App\Mail\CertificateOfServiceDelivery($certificate, $pdfPath, $sendEmailTo));

                return redirect()->route('certificate-of-service.index')
                    ->with('success', 'Certificate of Service created and sent to ' . $sendEmailTo . ' successfully.');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('certificate-of-service.index')
            ->with('success', 'Certificate of Service created successfully.');
    }

    public function show(CertificateOfService $certificateOfService)
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        $certificateOfService->load(['staff.department', 'staff.jobTitle', 'creator', 'approver', 'initializer']);
        $coo = $this->getCooSignatory()?->load('jobTitle');

        return view('certificate-of-service.show', [
            'certificate'         => $certificateOfService,
            'coo'                 => $coo,
            'certificateLogoSrc'  => $this->resolveCertificateLogoWebSrc(),
            'certificateStampSrc' => $this->resolveCertificateStampWebSrc(),
            'stampPosition'       => $this->getStampPosition(),
            'templateSettings'    => $this->getTemplateSettings(),
            'isHR'                => $this->isHR(),
            'canManage'           => $this->canManage(),
        ]);
    }

    public function initialize(CertificateOfService $certificateOfService)
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        if ($certificateOfService->isInitialized()) {
            return back()->with('info', 'This certificate has already been initialized and sent to the COO.');
        }

        $certificateOfService->update([
            'initialized_at' => now(),
            'initialized_by' => auth()->id(),
        ]);

        $certificateOfService->load(['staff', 'initializer']);

        $viewUrl = route('certificate-of-service.show', $certificateOfService);
        $initializer = auth()->user();

        $cooUsers = User::role('coo')->where('status', 'active')->whereNotNull('email')->get();
        foreach ($cooUsers as $coo) {
            try {
                Mail::to($coo->email)
                    ->queue(new CertificateOfServiceInitialized($certificateOfService, $initializer, $viewUrl));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $staffName = trim(($certificateOfService->staff?->fname ?? '') . ' ' . ($certificateOfService->staff?->lname ?? ''));
        return back()->with('success', "Certificate of Service for {$staffName} has been initialized. The COO has been notified via email.");
    }

    public function updateLogo(Request $request)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $request->validate([
            'certificate_logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $currentLogo = $this->getCertificateLogoSetting();
        $storedPath = $this->storeCertificateLogo($request->file('certificate_logo'));

        if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
            Storage::disk('public')->delete($currentLogo);
        }

        $this->setSystemSetting(self::CERTIFICATE_LOGO_SETTING_KEY, $storedPath);
        Cache::forget(self::CERTIFICATE_LOGO_SETTING_KEY);

        return back()->with('success', 'Certificate logo updated successfully.');
    }

    public function download(CertificateOfService $certificateOfService)
    {
        if (!$this->canAccess()) {
            abort(403);
        }

        $certificateOfService->load(['staff.department', 'staff.jobTitle', 'creator', 'approver']);
        $coo = $this->getCooSignatory()?->load('jobTitle');

        $pdf = Pdf::loadView('certificate-of-service.pdf', [
            'certificate'          => $certificateOfService,
            'certificateLogoSrc'   => $this->resolveCertificateLogoPdfSrc(),
            'certificateStampSrc'  => $this->resolveCertificateStampPdfSrc(),
            'cooSignatureSrc'      => $this->resolveSignaturePdfSrc($certificateOfService->coo_signature_path),
            'approverSignatureSrc' => $this->resolveSignaturePdfSrc($certificateOfService->approver_signature_path),
            'cooName'              => $coo ? trim($coo->fname . ' ' . $coo->lname) : null,
            'cooTitle'             => $coo?->jobTitle?->job_title ?? null,
            'stampPosition'        => $this->getStampPosition(),
            'templateSettings'     => $this->getTemplateSettings(),
            'renderTemplateText'   => fn (string $text) => $this->renderTemplateText($text, $certificateOfService),
        ]);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont'          => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'Certificate-of-Service-' . $certificateOfService->staff->ccbrt_code . '-' . $certificateOfService->issue_date->format('Y') . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(CertificateOfService $certificateOfService)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $certificateOfService->delete();

        return redirect()->route('certificate-of-service.index')
            ->with('success', 'Certificate deleted.');
    }
}
