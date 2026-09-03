<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibleDomain extends Model
{
    protected $fillable = ['domain', 'notes', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function isEligible(string $domain): bool
    {
        return static::active()->where('domain', strtolower($domain))->exists();
    }
}
