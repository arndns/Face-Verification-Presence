<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('nama_shift')->paginate(5);

        return view('Admin.shift.index', compact('shifts'));
    }

    public function create()
    {
        return view('Admin.shift.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_shift' => ['required', 'string', 'max:255'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i'],
        ]);

        $data = $this->normalizeShiftTimes($data);

        Shift::create($data);

        return redirect()->route('shifts.index')->with('success', 'Shift baru berhasil ditambahkan.');
    }

    public function edit(Shift $shift)
    {
        return view('Admin.shift.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'nama_shift' => ['required', 'string', 'max:255'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i'],
        ]);

        $data = $this->normalizeShiftTimes($data);

        $shift->update($data);

        return redirect()->route('shifts.index')->with('success', 'Data shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return redirect()->route('shifts.index')->with('success', 'Shift berhasil dihapus.');
    }

    protected function normalizeShiftTimes(array $data): array
    {
        $data['jam_masuk'] = $this->formatClockTime($data['jam_masuk']);
        $data['jam_pulang'] = $this->formatClockTime($data['jam_pulang']);

        return $data;
    }

    protected function formatClockTime(string $value): string
    {
        $value = str_replace('.', ':', $value);
        $formats = ['H:i', 'H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value, config('app.timezone'))
                    ->setTimezone(config('app.timezone'))
                    ->format('H:i:s');
            } catch (\Throwable $th) {
                continue;
            }
        }

        return $value;
    }
}
