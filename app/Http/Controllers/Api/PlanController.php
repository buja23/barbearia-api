<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index($slug)
    {
        if (!preg_match('/^[a-z0-9\-]+$/', mb_strtolower($slug))) {
            return response()->json(['message' => 'Slug inválido'], 400);
        }

        $barbershop = Barbershop::where('slug', $slug)->first();

        if (!$barbershop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        return response()->json(
            Plan::where('is_active', true)->get()
        );
    }
}