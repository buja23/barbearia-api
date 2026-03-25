<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    use HasFactory;
    protected $fillable = ['name', 'description', 'price', 'cuts_per_month', 'is_active'];

    protected $casts = [
        'price'          => 'decimal:2',
        'cuts_per_month' => 'integer',
        'is_active'      => 'boolean',
    ];
}