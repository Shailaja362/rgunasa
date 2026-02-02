<?php

namespace App\Imports;

use Throwable;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Programme;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\ValidationException;

class StudentsImportSheet implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, SkipsEmptyRows
{
    use Importable, SkipsFailures, SkipsErrors;

    public function model(array $row)
    {
        $department = Department::where('name', strtolower(trim($row['department'])))->first();
        $programme  = Programme::where('name', strtolower(trim($row['programme'])))->first();

        // Find the student by email or mobile_number (existing record to update)
        $student = Student::where('email', $row['email'])
            ->orWhere('mobile_number', $row['mobile_number'])
            ->first();

        // Check for conflicts in the database
        $conflict = Student::where(function ($q) use ($row) {
            $q->where('email', $row['email'])
                ->orWhere('mobile_number', $row['mobile_number'])
                ->orWhere('register_number', $row['register_number']);
        })
            ->when($student, function ($q) use ($student) {
                // Exclude the student we are updating
                $q->where('id', '!=', $student->id);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'email' => "Email {$row['email']} is already taken by another student.",
                'mobile_number' => "Mobile number {$row['mobile_number']} is already taken by another student.",
                'register_number' => "Register number {$row['register_number']} is already taken by another student.",
            ]);
        }

        //  Update existing student
        if ($student) {
            $student->update([
                'section' => $row['section'],
                'register_number' => $row['register_number'],
                'department_id' => $department?->id,
                'programme_id'  => $programme?->id,
            ]);
            return null; // skip creating new
        }

        //  Insert new student if not exists
        return new Student([
            'department_id' => $department?->id,
            'programme_id'  => $programme?->id,
            'name'          => $row['name'],
            'email'         => $row['email'],
            'mobile_number' => $row['mobile_number'],
            'password'      => Hash::make($row['mobile_number']),
            'date_of_birth' => $this->transformDate($row['date_of_birth'] ?? null),
            'gender'        => $row['gender'],
            'register_number' => $row['register_number'],
            'section' => $row['section'],
        ]);
    }


    private function transformDate($value)
    {
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(
                Date::excelToDateTimeObject($value)
            )->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function prepareForValidation(array $data, int $index)
    {

        return [
            'department' => strtolower(trim($data['department'])),
            'programme'  => strtolower(trim($data['programme'])),
            'name'          => trim($data['name']),
            'email'         => trim($data['email']),
            'mobile_number' => trim($data['mobile_number']),
            'date_of_birth' => trim($data['date_of_birth']),
            'gender'        => strtolower(trim($data['gender'])),
            'register_number' => trim($data['register_number']),
            'section'       => strtolower(trim($data['section'])),
        ];
    }


    public function rules(): array
    {
        return [
            '*.name' => ['bail', 'required', 'string'],
            '*.email' => ['bail', 'required', 'email', 'distinct'],
            '*.mobile_number' => ['bail', 'required', 'digits:10', 'distinct'],
            '*.gender' => ['bail', 'required', 'in:m,f,o,M,F,O'],
            '*.department' => ['bail', 'required', Rule::exists('departments', 'name')],
            '*.programme' => ['bail', 'required', Rule::exists('programmes', 'name')],
            '*.section' => ['bail', 'required', 'in:a,b,c,A,B,C,d,D,E,F,R,e,f,r'],
            '*.register_number' => ['bail', 'required', 'distinct'],
        ];
    }
}
