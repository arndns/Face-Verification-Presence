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
        $employee = Employee::findOrFail($id);
        $hasEmbedding = Face_Embedding::where('employee_id', $id)->exists();

        if ($hasEmbedding) {
            return redirect()
                ->route('admin.data')
                ->with('warning', 'Pegawai sudah memiliki data wajah. Perekaman ulang tidak diperlukan.');
        }

        return view('Admin.pegawai.faceid.face', [
            'employee' => $employee,
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

        $employeeId = (int) $request->input('employee_id');
        $descriptorArray = array_map('floatval', $request->descriptor);

        try {

            $embedding = Face_Embedding::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                ],
                [
                    'descriptor'  => $descriptorArray,
                ]
            );

            $action = $embedding->wasRecentlyCreated ? 'disimpan' : 'diperbarui';

            Log::info("Sukses: Wajah berhasil {$action} untuk ID {$employeeId}.");
            return response()->json([
                'success' => true,
                'message' => "Data wajah berhasil {$action}.",
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
