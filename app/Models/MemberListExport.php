<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberListExport extends Model
{
    protected $table = 'member_list_export_2023_07_06';
    protected $primaryKey = 'Klaviyo ID';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];
}
