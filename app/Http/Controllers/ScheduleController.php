<?php

namespace App\Http\Controllers;

use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(\App\Models\PublicationSchedule::class, 'schedule');
    }

    /**
     * Muestra la lista de horarios de publicación del usuario.
     * Vista: schedules.index (Horarios de Publicación)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();

        $byDow = PublicationSchedule::query()
            ->where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get()
            ->groupBy('day_of_week');

        $columnOrder = [1, 2, 3, 4, 5, 6, 0];
        $headers = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $columns = [];
        $maxRows = 0;

        foreach ($columnOrder as $dow) {
            $items = ($byDow->get($dow) ?? collect())->values();
            $columns[] = $items;
            $maxRows = max($maxRows, $items->count());
        }

        $rows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            foreach ($columns as $items) {
                $row[] = $items[$i] ?? null;
            }
            $rows[] = $row;
        }

        return view('schedules.index', compact('headers', 'rows'));
    }

    /**
     * Muestra el formulario para crear un nuevo horario.
     * Vista: schedules.create (Crear Horario)
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('schedules.create');
    }

    /**
     * Almacena un nuevo horario de publicación.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
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
        ], [
            'day_of_week.required' => 'El día de la semana es obligatorio.',
            'day_of_week.integer' => 'El día de la semana debe ser un número entero.',
            'day_of_week.between' => 'El día de la semana debe estar entre 0 (Domingo) y 6 (Sábado).',
            'time.required' => 'La hora es obligatoria.',
            'time.date_format' => 'La hora debe estar en formato HH:MM.',
            'time.unique' => 'Ya existe un horario para ese día y hora.',
        ]);

        $validated['user_id'] = auth()->id();

        PublicationSchedule::create($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Horario creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un horario existente.
     * Vista: schedules.edit (Editar Horario)
     *
     * @param PublicationSchedule $schedule
     * @return \Illuminate\View\View
     */
    public function edit(PublicationSchedule $schedule)
    {
        return view('schedules.edit', compact('schedule'));
    }

    /**
     * Actualiza un horario de publicación existente.
     *
     * @param Request $request
     * @param PublicationSchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, PublicationSchedule $schedule)
    {
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
        ], [
            'day_of_week.required' => 'El día de la semana es obligatorio.',
            'day_of_week.integer' => 'El día de la semana debe ser un número entero.',
            'day_of_week.between' => 'El día de la semana debe estar entre 0 (Domingo) y 6 (Sábado).',
            'time.required' => 'La hora es obligatoria.',
            'time.date_format' => 'La hora debe estar en formato HH:MM.',
            'time.unique' => 'Ya existe un horario para ese día y hora.',
        ]);

        $schedule->update($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Horario actualizado correctamente.');
    }

    /**
     * Elimina un horario de publicación.
     *
     * @param PublicationSchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PublicationSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Horario eliminado correctamente.');
    }
}