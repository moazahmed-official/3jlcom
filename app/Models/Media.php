<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['file_name','path','type','status','thumbnail_url','user_id','related_resource','related_id'];
}
