<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNotificationLog extends Model
{
    use HasFactory;

    protected $table = 'contract_notification_logs';

    protected $fillable = [
        'contract_id',
        'notification_type',
        'recipient_email',
        'recipient_name',
        'recipient_role',
        'recipient_user_id',
        'status',
        'message',
        'error_message',
        'sent_by',
        'trigger_source',
    ];

    /**
     * Contract relationship
     */
    public function contract()
    {
        return $this->belongsTo(CcbrtContract::class, 'contract_id');
    }

    /**
     * Recipient user relationship
     */
    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Sender user relationship
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Helper to log a notification
     */
    public static function logNotification(array $data): self
    {
        return self::create($data);
    }
}
