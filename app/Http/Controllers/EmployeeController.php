<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use App\Models\Position;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Barryvdh\DomPDF\Facade\PDF;


class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pageTitle = 'Employee List';
        confirmDelete();
        $positions = Position::all();
        return view('employee.index',[
               'pageTitle' => $pageTitle,
               'positions' => $positions
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $pageTitle = 'Create Employee';

        // ELOQUENT
        $positions = Position::all();

        return view('employee.create', compact('pageTitle', 'positions'));

        // // RAW SQL Query
        // $positions = DB::select('select * from positions');

        // // QUERY BUILDER
        // $positions = DB::table('positions')->get();

        // return view('employee.create', compact('pageTitle', 'positions'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $messages = [
        'required' => ':Attribute harus diisi.',
        'email' => 'Isi :attribute dengan format yang benar',
        'numeric' => 'Isi :attribute dengan angka'
    ];

    $validator = Validator::make($request->all(), [
        'firstName' => 'required',
        'lastName' => 'required',
        'email' => 'required|email',
        'age' => 'required|numeric',
        'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Tambahkan validasi untuk CV
    ], $messages);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Handle file upload
    $file = $request->file('cv');
    $originalFilename = null;
    $encryptedFilename = null;

    if ($file) {
        $originalFilename = $file->getClientOriginalName();
        $encryptedFilename = $file->hashName();

        // Simpan file ke direktori
        $file->store('public/files');
    }

    // ELOQUENT
    $employee = new Employee;
    $employee->firstname = $request->firstName;
    $employee->lastname = $request->lastName;
    $employee->email = $request->email;
    $employee->age = $request->age;
    $employee->position_id = $request->position;

    if ($file) {
        $employee->original_filename = $originalFilename;
        $employee->encrypted_filename = $encryptedFilename;
    }

    $employee->save();

    Alert::success('Added Successfully', 'Employee Data Added Successfully.');

    return redirect()->route('employees.index');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';

        // ELOQUENT
        $employee = Employee::find($id);

        return view('employee.show', compact('pageTitle', 'employee'));


        // // RAW SQL QUERY
        // $employee = collect(DB::select('
        //     select *, employees.id as employee_id, positions.name as position_name
        //     from employees
        //     left join positions on employees.position_id = positions.id
        //     where employees.id = ?
        // ', [$id]))->first();

        // // QUERY BUILDER
        // $employee = DB::table('employees')
        //     ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
        //     ->select('employees.*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->where('employees.id', $id)
        //     ->first();

        // return view('employee.show', compact('pageTitle', 'employee'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Edit Employee';
        // ELOQUENT
        $positions = Position::all();
        $employee = Employee::find($id);

        return view('employee.edit', compact('pageTitle', 'positions', 'employee'));

        // // QUERY BUILDER
        // $employee = DB::table('employees')
        //     ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
        //     ->select('employees.*', 'positions.name as position_name')
        //     ->where('employees.id', $id)
        //     ->first();

        // $positions = DB::table('positions')->get();

        // return view('employee.edit', compact('pageTitle', 'employee', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar.',
            'numeric' => 'Isi :attribute dengan angka.'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // ELOQUENT
        $employee = Employee::find($id);
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;

        // Proses File CV Baru
        $file = $request->file('cv');

        if ($file) {
            // Hapus file CV lama jika ada
            if ($employee->encrypted_filename) {
                $oldFile = 'public/files/' . $employee->encrypted_filename;
                if (Storage::exists($oldFile)) {
                    Storage::delete($oldFile);
                }
            }

            // Simpan file CV baru
            $originalFilename = $file->getClientOriginalName();
            $encryptedFilename = $file->hashName();

            $file->store('public/files');

            $employee->original_filename = $originalFilename;
            $employee->encrypted_filename = $encryptedFilename;
        }

        $employee->save();

        Alert::success('Changed Successfully', 'Employee Data Changed Successfully.');

        return redirect()->route('employees.index');

    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Temukan employee berdasarkan ID
        $employee = Employee::findOrFail($id);

        // Hapus file CV jika ada
        if ($employee->encrypted_filename) {
            $filePath = 'public/files/' . $employee->encrypted_filename;
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }

        // Hapus data employee dari database
        $employee->delete();

        Alert::success('Deleted Successfully', 'Employee Data Deleted Successfully.');

        return redirect()->route('employees.index');

    }

    public function downloadFile($id)
{
    // Cari data karyawan berdasarkan ID
    $employee = Employee::find($id);

    // Validasi jika karyawan atau file tidak ditemukan
    if (!$employee || !$employee->encrypted_filename) {
        abort(404, 'File not found.');
    }

    // Tentukan path file terenkripsi
    $encryptedFilename = 'public/files/' . $employee->encrypted_filename;

    // Nama file untuk diunduh
    $downloadFilename = $employee->original_filename ?: 'employee_cv.pdf';

    // Periksa apakah file ada di penyimpanan
    if (Storage::exists($encryptedFilename)) {
        return Storage::download($encryptedFilename, $downloadFilename);
    } else {
        abort(404, 'File not found in storage.');
    }
}
    public function getData(Request $request)
    {
        $employees = Employee::with('position');

        if ($request->ajax()) {
            return datatables()->of($employees)
                ->addIndexColumn()
                ->addColumn('actions', function($employee) {
                    return view('employee.actions', compact('employee'));
                })
                ->toJson();
        }
    }
    public function exportExcel()
    {
        return Excel::download(new EmployeesExport, 'employees.xlsx');
    }

    public function exportPdf()
    {
        $employees = Employee::all();
        $pdf = PDF::loadView('employee.export_pdf', compact('employees'));

        return $pdf->download('employees.pdf');
    }

}
