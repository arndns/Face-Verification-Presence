<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpParser\Node\Stmt\TryCatch;

class LocationController extends Controller
{
    public function locindex()
    {

        $locations = Location::paginate(10);
        return view('Admin.lokasi.index', compact('locations'));
    }
    public function create()
    {
        return view('Admin.lokasi.CRUD.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kota' => 'required|string|max:255',
            'unique:locations,kota',
            'alamat' => 'required|string|max:255',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius' => 'required|numeric',
        ]);

        try {
            // 2) Simpan (hindari mass assignment error dengan pastikan $fillable di model)
            Location::create($data);

            return redirect()
                ->route('location.index')
                ->with('success', 'Data Lokasi Kantor berhasil disimpan');
        } catch (QueryException $e) {
            // error dari database (duplikat, kolom tidak ada, dll)
            Log::error('DB error saat simpan lokasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()
                ->with('error', 'Data Lokasi Kantor gagal disimpan (DB).')
                ->withInput();
        } catch (\Throwable $e) {
            // error lain (type error, dll)
            Log::error('General error saat simpan lokasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()
                ->with('error', 'Data Lokasi Kantor gagal disimpan.')
                ->withInput();
        }
    }
    public function edit($id)
    {
        $location = Location::findOrFail($id);
        return view('Admin.lokasi.CRUD.update', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        // Hanya validasi field yang dikirim & tidak kosong
        $rules = [
            'kota'      => ['sometimes', 'filled', 'string', 'max:255', Rule::unique('locations', 'kota')->ignore($location->id)],
            'alamat'    => ['sometimes', 'filled', 'string', 'max:255'],
            'latitude'  => ['sometimes', 'filled', 'string',],
            'longitude' => ['sometimes', 'filled', 'string',],
            'radius'    => ['sometimes', 'filled', 'numeric', 'min:0'],
        ];

        $data = $request->validate($rules);


        try {
            $location->update($data);

            return redirect()
                ->route('location.index')
                ->with('success', 'Data Lokasi Kantor berhasil diperbarui');
        } catch (QueryException $e) {
            Log::error('DB error saat update lokasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()
                ->with('error', 'Data Lokasi Kantor gagal diperbarui (DB).')
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('General error saat update lokasi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()
                ->with('error', 'Data Lokasi Kantor gagal diperbarui.')
                ->withInput();
        }
    }
    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('location.index')
            ->with('success', 'Data Lokasi Berhasil Dihapus!');
    }
}
