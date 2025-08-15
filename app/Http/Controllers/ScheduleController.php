<?php

namespace App\Http\Controllers;

use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function __construct()
    {
        // Aplica PublicationSchedulePolicy a las acciones del resource
        $this->authorizeResource(\App\Models\PublicationSchedule::class, 'schedule');
    }

    public function index()
    {
        $user = auth()->user();

        // Horarios del usuario, ordenados
        $byDow = \App\Models\PublicationSchedule::query()
            ->where('user_id', $user->id)
            ->orderBy('day_of_week') // 0=Dom, 1=Lun, ..., 6=Sáb (Carbon)
            ->orderBy('time')
            ->get()
            ->groupBy('day_of_week');

        // Orden visual: L K M J V S D  => Carbon: 1,2,3,4,5,6,0
        $columnOrder = [1, 2, 3, 4, 5, 6, 0];
        $headers = ['L', 'K', 'M', 'J', 'V', 'S', 'D'];

        // Columnas: cada día es una lista de modelos (para tener id + time)
        $columns = [];
        $maxRows = 0;

        foreach ($columnOrder as $dow) {
            $items = ($byDow->get($dow) ?? collect())->values();
            $columns[] = $items;
            $maxRows = max($maxRows, $items->count());
        }

        // Filas: i-ésimo elemento de cada día (o null)
        $rows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            foreach ($columns as $items) {
                $row[] = $items[$i] ?? null; // modelo PublicationSchedule o null
            }
            $rows[] = $row;
        }

        return view('schedules.index', compact('headers', 'rows'));
    }

    public function create()
    {
        return view('schedules.create');
    }

    public function store(Request $request)
    {
        // Policy: create() se valida automáticamente via authorizeResource

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'time' => [
                'required',
                'date_format:H:i',
                Rule::unique('publication_schedules')->where(
                    fn($q) =>
                    $q->where('user_id', auth()->id())
                        ->where('day_of_week', $request->day_of_week)
                ),
            ],
        ]);

        $validated['user_id'] = auth()->id();

        PublicationSchedule::create($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Horario creado correctamente.');
    }

    public function edit(PublicationSchedule $schedule)
    {
        // Policy: 'update' ya es resuelta por authorizeResource en rutas de edición
        return view('schedules.edit', compact('schedule'));
    }

    public function update(Request $request, PublicationSchedule $schedule)
    {
        // Policy: 'update' ya se valida automáticamente via authorizeResource

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'time' => [
                'required',
                'date_format:H:i',
                Rule::unique('publication_schedules')->ignore($schedule->id)->where(
                    fn($q) =>
                    $q->where('user_id', auth()->id())
                        ->where('day_of_week', $request->day_of_week)
                ),
            ],
        ]);

        $schedule->update($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Horario actualizado.');
    }

    public function destroy(PublicationSchedule $schedule)
    {
        // Policy: 'delete' ya se valida automáticamente via authorizeResource
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Horario eliminado.');
    }
}