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
        $existingEmbeddings = Face_Embedding::where('employee_id', $id)->get();
        $existingOrientations = $existingEmbeddings
            ->pluck('orientation')
            ->filter()
            ->map(fn ($o) => strtolower($o))
            ->unique()
            ->values();

        $allOrientations = collect(['front', 'left', 'right', 'up', 'down']);
        $hasAll = $allOrientations->every(fn ($ori) => $existingOrientations->contains($ori));

        if ($hasAll) {
            return redirect()
                ->route('admin.data')
                ->with('warning', 'Pegawai sudah merekam semua arah wajah. Perekaman ulang tidak diperlukan.');
        }
        return view('Admin.pegawai.faceid.face', [
            'employee' => $employee,
            'hasEmbedding' => $existingEmbeddings->isNotEmpty(),
            'existingEmbeddings' => $existingEmbeddings,
        ]);
    }

    public function saveEmbedding(Request $request)
    {
        Log::info('API /api/save-embedding dipanggil.', $request->all());
        $validate = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'descriptor'  => 'required|array|size:128',
            'orientation' => 'required|string|in:front,left,right,up,down',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validate->errors()
            ], 400);
        }

        $employeeId = (int) $request->input('employee_id');
        $orientation = strtolower((string) $request->input('orientation', 'front'));
        $descriptorArray = array_map('floatval', $request->descriptor);

        try {

            $embedding = Face_Embedding::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'orientation' => $orientation,
                ],
                [
                    'descriptor'  => $descriptorArray,
                ]
            );

            $action = $embedding->wasRecentlyCreated ? 'disimpan' : 'diperbarui';

            Log::info("Sukses: Wajah berhasil {$action} untuk ID {$employeeId} ({$orientation}).");
            return response()->json([
                'success' => true,
                'message' => "Data wajah berhasil {$action}.",
                'orientation' => $embedding->orientation,
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
