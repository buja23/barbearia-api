<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Notifications\NewReportReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'        => ['required', 'in:bug,suggestion,other'],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $report = Report::create([
            'user_id'       => $user->id,
            'barbershop_id' => $user->barbershop_id,
            'type'          => $validated['type'],
            'title'         => $validated['title'],
            'description'   => $validated['description'],
            'source'        => 'mobile',
            'status'        => 'open',
        ]);

        $this->notifyAdmins($report);

        return response()->json([
            'message' => 'Reporte enviado com sucesso! Obrigado pelo feedback.',
            'report'  => [
                'id'     => $report->id,
                'type'   => $report->type,
                'title'  => $report->title,
                'status' => $report->status,
            ],
        ], 201);
    }

    private function notifyAdmins(Report $report): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new NewReportReceived($report));
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao notificar admins sobre reporte mobile: ' . $e->getMessage(), [
                'report_id' => $report->id,
            ]);
        }
    }
}
