<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkLocation extends Model
{

    use HasFactory;

        protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'allowed_radius_meters',
    ];

    /**
     * Relasi ke User
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
