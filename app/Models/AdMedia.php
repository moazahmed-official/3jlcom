<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdMedia extends Model
{
    protected $table = 'ad_media';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['ad_id','media_id','position','is_banner'];

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
