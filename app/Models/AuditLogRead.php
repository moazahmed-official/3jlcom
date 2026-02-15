<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogRead extends Model
{
    protected $table = 'audit_log_reads';

    protected $fillable = [
        'audit_log_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function auditLog()
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
