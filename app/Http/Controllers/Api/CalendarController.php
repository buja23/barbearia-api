<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        $events = Appointment::all()->map(function($a) {
            return [
                'id' => $a->id,
                'title' => $a->client_name . ' - ' . $a->service->name,
                'start' => $a->scheduled_at,
                'end' => $a->end_at,
            ];
        });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        // validação e criação
    }

}
