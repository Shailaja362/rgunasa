<?php

namespace App\Http\Controllers\student;

use App\Exports\StudentTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentImportExportController extends Controller
{
    public function downloadTemplate()
    {
        return Excel::download(new StudentTemplateExport, 'student_upload_template.xlsx');
    }

    public function uploadStudents(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx'
            ]);
            $import = new StudentsImport();
            try {
                Excel::import($import, $request->file('file'));
            } catch (ValidationException $e) {
                $failures = $e->failures();
                return back()->with('failures', $failures);
            }

            if ($import->failures()->isNotEmpty()) {
                $failures = $import->failures();
                return back()->with('failures', $failures);
            }
            return back()->with('success', 'Students Uploaded Successfully');
        } catch (ValidationException $e) {

        }
    }

}
