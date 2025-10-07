<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class LocationController extends Controller
{
    public function locindex()
    {

        $lokasi = Location::paginate(10);
        return view('Admin.lokasi.index', compact('lokasi'));
    }
    public function create()
    {
        return view('Admin.lokasi.create');
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
}
