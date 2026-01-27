<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTypeChange extends Model
{
    use HasFactory;

    protected $table = 'account_type_changes';

    protected $fillable = [
        'user_id',
        'old_account_type',
        'new_account_type',
        'changed_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
