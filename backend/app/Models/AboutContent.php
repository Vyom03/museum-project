<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'paragraph_one',
        'paragraph_two',
        'paragraph_three',
        'image_url',
    ];
}
