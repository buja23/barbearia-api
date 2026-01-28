<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index($slug)
    {
        $barbershop = Barbershop::where('slug', $slug)->firstOrFail();

        // Assumindo que você tem o relacionamento services() no model Barbershop
        // Se não tiver, crie: return $this->hasMany(Service::class); no model Barbershop
        $services = $barbershop->services()
            ->where('is_active', true) 
            ->get();

        return response()->json($services); // Idealmente, crie um ServiceResource também
    }
}