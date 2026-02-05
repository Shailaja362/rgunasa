<?php

namespace App\Imports;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;


class AdminsImportSheet implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, SkipsEmptyRows
{
    use Importable, SkipsFailures, SkipsErrors;
    private $rows = [];

    public function model(array $row)
    {
        $role = Role::where('name', strtolower(trim($row['role'])))->first();

        $admin = new Admin();
        $admin->role_id = $role?->id;
        $admin->name  = $row['name'];
        $admin->email = $row['email'];
        $admin->mobile_number = $row['mobile_number'];
        $admin->password  = Hash::make($row['mobile_number']);
        $admin->emp_code = $row['employee_code'];
        $admin->security_code  = $row['security_code'] ?? null;
        $admin->save();

        return  $admin;
    }

    public function prepareForValidation(array $row, $index)
    {
        $this->rows[$index] = $row; // store row for later
        return [
            'role' => strtolower(trim($row['role'])),
            'name' => trim($row['name']),
            'email' => trim($row['email']),
            'mobile_number' => trim($row['mobile_number']),
            'employee_code' => trim($row['employee_code']),
            'security_code' => trim($row['security_code']) ?? null,
        ];
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string',
            '*.employee_code' => 'required',
            '*.email' => 'required|email|distinct|unique:admins,email',
            '*.mobile_number' => 'required|digits:10|distinct|unique:admins,mobile_number',
            '*.role' => 'required|exists:roles,name',
            '*.security_code' => function ($attribute, $value, $fail) {
                $index = explode('.', $attribute)[0];
                $row = $this->rows[$index];
                if (isset($row['role']) && strtolower($row['role']) === 'super_admin' && empty($value)) {
                    $fail('The security code is required for super admin.');
                }
            }
        ];
    }
}
