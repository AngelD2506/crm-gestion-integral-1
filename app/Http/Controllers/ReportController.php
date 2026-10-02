<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function clientesPorZona()
    {
 // 1. Consulta con Query Builder
        $zonas = DB::table('clientes')  // Hueco A
            ->select('zona_geografica', DB::raw('COUNT(*) as total')) //Hueco B y c
            ->groupBy('zona_geografica') // Hueco D
            ->orderByDesc('total') //Hueco E
            ->get(); // Hueco F

 // 2. Total general
$totalGeneral = $zonas->sum('total'); //hueco G

// 3. Agregar porcentaje
$zonasConPorcentaje = $zonas->map(function ($zona) use ($totalGeneral) { // Hueco H
    $zona->porcentaje = $totalGeneral > 0 ? round((($zona->total) / $totalGeneral) * 100, 2) : 0; // Hueco I
return $zona; // Hueco J

});
//filtrar solo zonas con porcentaje mayor a 15
if ($totalgeneral > 0) {
    $zonasconporcentaje = $zonasconporcentaje->filter(function ($zona) {
        return $zona->porcentaje > 15;
    });

// 4. Datos para gráfico
$labels = $zonasConPorcentaje->pluck('zona_geografica')->toArray(); // Hueco K
$data = $zonasConPorcentaje->pluck('total')->toArray(); // Hueco L

return view('reports.zonas', compact('zonasConPorcentaje', 'totalGeneral', 'labels', 'data'));
    }
}


public function interaccionesPorAsesor()
{
$asesores = DB::table('users') //HUECO A
->select('users.id', 'users.name',DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'llamada' THEN 1 END) AS llamadas"),   // ← HUECO B
DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'visita' THEN 1 END) AS visitas"),    // ← HUECO C
DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'whatsapp' THEN 1 END) AS whatsapp"),   // ← HUECO D
DB::raw('COUNT(interactions.id) AS total')      // ← HUECO E
)
->leftJoin('clients', 'clients.user_id', '=', 'users.id')          // HUECO F
->leftJoin('interactions', 'interactions.client_id', '=','clients.id')  // ← HUECO G
->groupBy('users.id', 'users.name')  // ← HUECO H,I
->orderByDesc('total') // ← HUECO j
->get();

//Consulta para obtener interacciones por día de la semana
$interaccionesPorDia = DB::table('interactions')
->select(
    DB::raw("DAYNAME(fecha_seguimiento) as dia_semana"),
    DB::raw("COUNT(*) as total")
            )
    ->groupBy(DB::raw("DAYNAME(fecha_seguimiento)"))
    ->orderBy(DB::raw("WEEKDAY(fecha_seguimiento)"))
    ->get();

//datos para el grafico original
$labels = $asesores->pluck('name')->toArray();  // ← HUECO K   
$llamadas = $asesores->pluck('llamadas')->toArray();   // ← HUECO L
$visitas  = $asesores->pluck('visitas')->toArray();    // ← HUECO M
$whatsapp = $asesores->pluck('whatsapp')->toArray();   // ← HUECO N

//Datos para el nuevo gráfico del reto (Días de la semana)
 $labelsDias = $interaccionesPorDia->pluck('dia_semana')->toArray();
 $dataDias = $interaccionesPorDia->pluck('total')->toArray();

 return view('reports.interacciones', compact('asesores', 'labels', 'llamadas', 'visitas', 'whatsapp', 'labelsDias', 'dataDias'));
}
}