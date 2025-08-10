<?php

namespace App\Http\Controllers;

use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = auth()->user()
            ->publicationSchedules()
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('schedules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'time' => [
                'required',
                'date_format:H:i',
                Rule::unique('publication_schedules')->where(fn ($q) =>
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
        $this->authorizeAccess($schedule);
        return view('schedules.edit', compact('schedule'));
    }

    public function update(Request $request, PublicationSchedule $schedule)
    {
        $this->authorizeAccess($schedule);

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'time' => [
                'required',
                'date_format:H:i',
                Rule::unique('publication_schedules')->ignore($schedule->id)->where(fn ($q) =>
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
        $this->authorizeAccess($schedule);
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Horario eliminado.');
    }

    private function authorizeAccess(PublicationSchedule $schedule)
    {
        if ($schedule->user_id !== auth()->id()) {
            abort(403);
        }
    }
}