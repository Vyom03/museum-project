<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourRegistration extends Model
{
    protected $fillable = [
        'contact_name',
        'email',
        'phone',
        'country_code',
        'organisation',
        'group_type',
        'preferred_date',
        'preferred_slot',
        'adults_count',
        'students_count',
        'needs_guided_tour',
        'notes',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'needs_guided_tour' => 'boolean',
        'adults_count' => 'integer',
        'students_count' => 'integer',
    ];
}
