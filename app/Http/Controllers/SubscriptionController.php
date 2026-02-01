<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // Retorna a assinatura ativa do usuário (se houver)
    public function index(Request $request)
    {
        // Assume que você tem o relacionamento 'activeSubscription' no Model User
        // Se der erro, me avise que ajustamos o Model User
        $subscription = $request->user()->activeSubscription;

        if (!$subscription) {
            return response()->json(null); // Retorna null se não tiver plano
        }

        // Carrega o plano junto para sabermos o nome dele
        $subscription->load('plan');

        return response()->json([
            'id' => $subscription->id,
            'planId' => $subscription->plan_id,
            'planName' => $subscription->plan->name,
            'status' => $subscription->status,
            'price' => $subscription->plan->price,
            'uses_this_month' => $subscription->uses_this_month,
            'monthly_limit' => $subscription->plan->monthly_limit,
            'next_billing' => $subscription->ends_at ?? $subscription->updated_at->addMonth(),
        ]);
    }
}