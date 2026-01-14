<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    protected $fillable = ['name', 'description', 'price', 'cuts_per_month', 'is_active'];
}