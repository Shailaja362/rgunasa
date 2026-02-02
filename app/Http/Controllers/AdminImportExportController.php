<?php

namespace App\Http\Controllers;

use App\Exports\AdminTemplateExport;
use App\Imports\AdminImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;


class AdminImportExportController extends Controller
{
    public function downloadTemplate()
    {
        return Excel::download(new AdminTemplateExport, 'admin_upload_template.xlsx');
    }

    public function uploadAdmin(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx'
            ]);

            $import = new AdminImport();

            try {
                Excel::import($import, $request->file('file'));
            } catch (ExcelValidationException $e) {
                $failures = $e->failures(); // This contains all row-level errors
                return back()->with('failures', $failures);
            }

            // If you use SkipsOnFailure in your import
            if ($import->failures()->isNotEmpty()) {
                $failures = $import->failures();
                return back()->with('failures', $failures);
            }

            return back()->with('success', 'Students Uploaded Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // This will catch file upload errors
            return back()->withErrors($e->errors());
        }
    }
}
