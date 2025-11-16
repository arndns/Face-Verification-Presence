<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Face_Embedding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FaceApiController extends Controller
{
    public function addfaceid($id)
    {
        $hasEmbedding = Face_Embedding::where('employee_id', $id)->exists();
        if ($hasEmbedding) {
            return redirect()->route('admin.data')
                ->with('warning', 'Pegawai sudah melakukan pengenalan wajah');
        }
        $employee = Employee::findOrFail($id);
        return view('Admin.pegawai.faceid.face', [
            'employee' => $employee,
            'hasEmbedding' => $hasEmbedding
        ]);
    }

    public function saveEmbedding(Request $request)
    {
        Log::info('API /api/save-embedding dipanggil.', $request->all());
        $validate = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'descriptor'  => 'required|array|size:128',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validate->errors()
            ], 400);
        }

        $employeeId = $request->input('employee_id');
        $existing = Face_Embedding::where('employee_id', $employeeId)->exists();
        if ($existing) {
            Log::warning('Gagal: Karyawan ID ' . $employeeId . ' sudah punya wajah.');
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Karyawan ini sudah memiliki data wajah yang tersimpan.',
            ], 409); // 409 Conflict
        }
        try {

            $descriptorString = json_encode($request->descriptor);

            $embedding = Face_Embedding::create([
                'employee_id' => $request->employee_id,
                'descriptor'  => $descriptorString,
            ]);

            Log::info('Sukses: Wajah berhasil disimpan untuk ID ' . $request->employee_id);
            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil didaftarkan'
            ]);
        } catch (\Exception $e) {
            Log::error('Error 500:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}
