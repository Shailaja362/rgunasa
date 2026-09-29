<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepartmentStudentReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $summary,
        protected Collection $students
    ) {}

    public function sheets(): array
    {
        return [
            new class($this->summary) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected Collection $summary) {}

                public function collection(): Collection
                {
                    return $this->summary->values()->map(fn($row, $index) => [
                        $index + 1,
                        $row->name,
                        $row->total,
                        $row->registered,
                        $row->unregistered,
                    ]);
                }

                public function headings(): array
                {
                    return ['S.No', 'Department', 'Total Students', 'Registered', 'Unregistered'];
                }

                public function title(): string
                {
                    return 'Department Summary';
                }
            },
            new class($this->students) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected Collection $students) {}

                public function collection(): Collection
                {
                    return $this->students->values()->map(fn($student, $index) => [
                        $index + 1,
                        $student->register_number ?? '-',
                        $student->name,
                        optional($student->get_department)->name ?? '-',
                        optional($student->get_programme)->name ?? '-',
                        $student->batch ?? '-',
                        $student->semester ?? '-',
                        strtoupper($student->section ?? '-'),
                        $student->email,
                        $student->is_registered ? 'Registered' : 'Unregistered',
                    ]);
                }

                public function headings(): array
                {
                    return ['S.No', 'Register Number', 'Student Name', 'Department', 'Programme', 'Batch', 'Semester', 'Section', 'Email', 'Status'];
                }

                public function title(): string
                {
                    return 'Students';
                }
            },
        ];
    }
}
