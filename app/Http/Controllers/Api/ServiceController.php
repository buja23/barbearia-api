<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        // Retorna apenas serviços ativos para o app do cliente
        return response()->json(
            Service::where('is_active', true)->get()
        );
    }
}