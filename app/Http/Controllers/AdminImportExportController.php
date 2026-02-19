<?php

namespace App\Http\Controllers;

use App\Exports\AdminTemplateExport;
use App\Imports\AdminImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Maatwebsite\Excel\Exceptions\SheetNotFoundException;

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
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                return back()->with('failures', $e->failures());
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->with('failures', $e->errors());
            } catch (SheetNotFoundException $e) {
                return back()->with(
                    'sheet_error',
                    'Invalid Excel format. Please upload the correct sheet named "Admin Upload Sheet".'
                );
            }
            if ($import->failures()->isNotEmpty()) {
                $failures = $import->failures();
                return back()->with('failures', $failures);
            }
            return back()->with('success', 'Admin Uploaded Successfully');
        } catch (ValidationException $e) {
        }
    }
}
