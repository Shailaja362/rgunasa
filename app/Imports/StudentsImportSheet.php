<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;


class StudentsImportSheet implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, SkipsEmptyRows
{
    use Importable, SkipsFailures, SkipsErrors;
    private $excelEmails = [];
    private $excelMobiles = [];
    private $excelRegisters = [];

    public function model(array $row)
    {
        $student = Student::where('register_number', $row['register_number'])
            ->first();
        if (!$student && $row['email']) {
            $student = Student::where('email', $row['email'])->first();
        }
        if (!$student && $row['mobile_number']) {
            $student = Student::where('mobile_number', $row['mobile_number'])->first();
        }
        $department = Department::whereRaw('LOWER(name) = ?', [$row['department']])->first();
        $programme = Programme::whereRaw('LOWER(name) = ?', [$row['programme']])->first();
        $data = [
            'department_id' => $department?->id,
            'programme_id'  => $programme?->id,
            'date_of_birth' => $this->transformDate($row['date_of_birth'] ?? null),
            'gender'        => $row['gender'],
            'register_number' => $row['register_number'],
            'section'       => $row['section'],
            'batch'         => $row['batch'],
            'semester'      => $row['semester'],
        ];

        if ($student) {
            $student->update($data);
            return null;
        } else {
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
                'batch' => $row['batch'],
                'semester' => $row['semester'],
            ]);
        }
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

    private function extractEmail($value)
    {
        if (!$value) return null;
        $value = trim(strtolower($value));
        if (str_starts_with($value, 'mailto:')) {
            $value = substr($value, 7);
        }
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/', $value, $matches)) {
            return $matches[0];
        }
        return $value;
    }

    public function prepareForValidation(array $data, int $index)
    {
        $email = isset($data['email'])
            ? strtolower(trim($this->extractEmail($data['email'])))
            : null;

        $mobile = isset($data['mobile_number'])
            ? trim($data['mobile_number'])
            : null;

        $register = isset($data['register_number'])
            ? strtoupper(trim($data['register_number']))
            : null;

        $errors = [];

        if ($email && in_array($email, $this->excelEmails)) {
            $errors['email'] = "Duplicate email '$email' found in Excel at row " . ($index + 2);
        }

        if ($mobile && in_array($mobile, $this->excelMobiles)) {
            $errors['mobile_number'] = "Duplicate mobile '$mobile' found in Excel at row " . ($index + 2);
        }

        if ($register && in_array($register, $this->excelRegisters)) {
            $errors['register_number'] = "Duplicate register number '$register' found in Excel at row " . ($index + 2);
        }

        if (!empty($errors)) {
            $validator = ValidatorFacade::make([], []);
            foreach ($errors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
            throw new ValidationException($validator);
        }

        // Store for next rows
        $this->excelEmails[] = $email;
        $this->excelMobiles[] = $mobile;
        $this->excelRegisters[] = $register;

        return [
            'department' => isset($data['department']) ? strtolower(trim($data['department'])) : null,
            'programme' => isset($data['programme']) ? strtolower(trim($data['programme'])) : null,
            'name' => isset($data['name']) ? trim($data['name']) : null,
            'email' => $email,
            'mobile_number' => $mobile,
            'date_of_birth' => isset($data['date_of_birth']) ? trim($data['date_of_birth']) : null,
            'gender' => isset($data['gender']) ? strtolower(trim($data['gender'])) : null,
            'register_number' => $register,
            'section' => isset($data['section']) ? strtoupper(trim($data['section'])) : null,
            'batch' => isset($data['batch']) ? trim($data['batch']) : null,
            'semester' => isset($data['semester']) ? trim($data['semester']) : null,
        ];
    }

    public function rules(): array
    {
        return [
            '*.department' => [
                'bail',
                'required',
                Rule::exists('departments', 'name')
            ],
            '*.programme' => [
                'bail',
                'required',
                Rule::exists('programmes', 'name')
            ],
            '*.name' => [
                'bail',
                'required',
                'string',
                'max:255'
            ],
            '*.email' => [
                'bail',
                'required',
                'email',
                'distinct',
                'max:255'
            ],
            '*.mobile_number' => [
                'bail',
                'required',
                'digits:10',
                'distinct'
            ],
            '*.date_of_birth' => [
                'required'
            ],
            '*.gender' => [
                'bail',
                'required',
                'in:m,f,o'
            ],
            '*.register_number' => [
                'bail',
                'required',
                'max:50',
                'distinct'
            ],
            '*.section' => [
                'bail',
                'required',
                'in:A,B,C,D,E,F,R'
            ],
            '*.batch' => [
                'bail',
                'required',
                'regex:/^\d{4}-\d{4}$/'
            ],
            '*.semester' => [
                'bail',
                'required',
                'in:1,2,3,4,5,6,7,8'
            ],
        ];
    }
}
