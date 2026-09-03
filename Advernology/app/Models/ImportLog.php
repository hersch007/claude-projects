<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = ['filename', 'rows_imported', 'rows_skipped', 'errors', 'admin_id'];
}
