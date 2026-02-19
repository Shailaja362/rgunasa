<?php

namespace App\Imports;

use App\Models\Admin;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator as ValidatorFacade;


class AdminsImportSheet implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, SkipsEmptyRows
{
    use Importable, SkipsFailures, SkipsErrors;
    private $rows = [];
    private $excelEmails = [];
    private $excelMobiles = [];
    private $excelEmpCode = [];

    public function model(array $row)
    {
        $admin = Admin::where('emp_code', $row['employee_code'])
            ->first();

        if (!$admin && $row['email']) {
            $admin = Admin::where('email', $row['email'])->first();
        }


        $role = Role::where('name', strtolower(trim($row['role'])))->first();
        $department = Department::whereRaw('LOWER(name) = ?', [$row['department']])->first();
        $designation = Designation::whereRaw('LOWER(name) = ?', [$row['designation']])->first();
        $data = [
            'department_id' => $department?->id,
            'designation_id'  => $designation?->id,
        ];
        if ($admin) {
            $admin->update($data);
            return null;
        } else {
            $admin = new Admin();
            if ($role != 2) {
                $admin->role_id = $role?->id;
            }
            $admin->department_id = $department?->id;
            $admin->designation_id = $designation?->id;
            $admin->name  = $row['name'];
            $admin->email = $row['email'];
            $admin->mobile_number = $row['mobile_number'];
            $admin->password  = Hash::make($row['mobile_number']);
            $admin->emp_code = $row['employee_code'];
            $admin->security_code  = $row['security_code'] ?? null;
            $admin->save();
            return  $admin;
        }
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

        $employee_code = isset($data['employee_code'])
            ? strtoupper(trim($data['employee_code']))
            : null;

        $errors = [];

        if ($email && in_array($email, $this->excelEmails)) {
            $errors['email'] = "Duplicate email '$email' found in Excel at row " . ($index + 2);
        }

        if ($mobile && in_array($mobile, $this->excelMobiles)) {
            $errors['mobile_number'] = "Duplicate mobile '$mobile' found in Excel at row " . ($index + 2);
        }

        if ($employee_code && in_array($employee_code, $this->excelEmpCode)) {
            $errors['employee_code'] = "Duplicate employee code '$employee_code' found in Excel at row " . ($index + 2);
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
        $this->excelEmpCode[] = $employee_code;

        return [
            'department' => isset($data['department_name']) ? strtolower(trim($data['department_name'])) : null,
            'designation' => isset($data['designation_name']) ? strtolower(trim($data['designation_name'])) : null,
            'role' => isset($data['role']) ? strtolower(trim($data['role'])) : null,
            'name' => isset($data['name']) ? trim($data['name']) : null,
            'email' => $email,
            'mobile_number' => $mobile,
            'employee_code' => isset($data['employee_code']) ? trim($data['employee_code']) : null,
            'security_code' => isset($data['security_code']) ? trim($data['security_code']) : null
        ];
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string',
            '*.employee_code' => [
                'bail',
                'required',
                'distinct',
                'max:255'
            ],
            '*.email' => [
                'bail',
                'required',
                'email',
                'distinct',
                'max:255'
            ],
            '*.mobile_number' => 'required|digits:10|distinct',
            // '*.role' => 'required',
            '*.security_code' => function ($attribute, $value, $fail) {
                $parts = explode('.', $attribute);
                if (!isset($parts[0])) {
                    return;
                }
                $index = $parts[0];
                if (!isset($this->rows[$index])) {
                    return;
                }
                $row = $this->rows[$index];
                if (
                    isset($row['role']) &&
                    strtolower(trim($row['role'])) === 'super_admin' &&
                    empty($value)
                ) {
                    $fail("Security code is required for super admin at row " . ($index + 2));
                }
            },
            '*.department_name' => [
                'bail',
                // 'required',
                // Rule::exists('departments', 'name')
            ],
            '*.designation_name' => [
                'bail',
                // 'required',
                // Rule::exists('designations', 'name')
            ],
        ];
    }
}
