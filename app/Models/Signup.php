<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Signup extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $hidden = [
        'token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'number_of_employees' => 'integer',
        'is_generate_revenue' => 'boolean',
        'is_profitable' => 'boolean',
        'country_id' => 'integer',
    ];

    public function industry()
    {
        return $this->belongsTo(Category::class, 'industry_id');
    }
}
