<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificacionController extends Controller
{
    /**
     * Mismo contenido que la campana de la web:
     *  - Todos: sus notificaciones (pedido aceptado) y cuántas están sin leer.
     *  - Administrador/vendedor: además "staff" con pedidos pendientes y cuántos son nuevos
     *    desde la última vez que abrieron la campana.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $respuesta = [
            'no_leidas' => $user->unreadNotifications()->count(),
            'notificaciones' => $user->notifications()->latest()->take(30)->get()->map(function ($n) {
                $d = $n->data;

                return [
                    'id' => $n->id,
                    'tipo' => 'pedido_aceptado',
                    'leida' => $n->read_at !== null,
                    'fecha' => $n->created_at?->toIso8601String(),
                    'pedido_id' => $d['pedido_id'] ?? null,
                    'venta_id' => $d['venta_id'] ?? null,
                    'total' => isset($d['total']) ? (float) $d['total'] : null,
                    'fecha_pedido' => $d['fecha_pedido'] ?? null,
                    'fecha_aceptacion' => $d['fecha_aceptacion'] ?? null,
                ];
            })->values(),
        ];

        if ($user->hasAnyRole(['administrador', 'vendedor'])) {
            $vistasAt = $user->notificaciones_vistas_at;

            $respuesta['staff'] = [
                'pedidos_nuevos' => Pedido::where('situacion', 'pendiente')
                    ->when($vistasAt, fn ($q) => $q->where('fecha', '>', $vistasAt))
                    ->count(),
                'pedidos_pendientes_total' => Pedido::where('situacion', 'pendiente')->count(),
                'pedidos_pendientes' => Pedido::with('cliente')
                    ->where('situacion', 'pendiente')
                    ->latest('fecha')
                    ->take(5)
                    ->get()
                    ->map(fn (Pedido $p) => [
                        'id' => $p->id,
                        'cliente' => $p->cliente->name ?? '—',
                        'total' => (float) $p->total,
                        'fecha' => $p->fecha->format('Y-m-d H:i:s'),
                    ])->values(),
            ];
        }

        return response()->json($respuesta);
    }

    /** Marca todas como leídas (al abrir la campana del cliente). */
    public function leidas(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function leida(Request $request, string $id)
    {
        $notificacion = $request->user()->notifications()->whereKey($id)->first();

        if (! $notificacion) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        $notificacion->markAsRead();

        return response()->json(['ok' => true]);
    }

    /** Admin/vendedor: marca como vistos los pedidos nuevos (resetea el contador de la campana). */
    public function vistas(Request $request)
    {
        $data = $request->validate([
            'hasta' => ['nullable', 'date'],
        ]);

        $hasta = isset($data['hasta']) ? Carbon::parse($data['hasta']) : now();

        if ($hasta->isFuture()) {
            $hasta = now();
        }

        User::whereKey($request->user()->id)->update(['notificaciones_vistas_at' => $hasta]);

        return response()->json(['ok' => true]);
    }
}
