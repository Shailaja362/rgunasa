<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Department Wise Students Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
<img src="{{ public_path('images/rgu_logo.jpeg') }}" style="width:100%; margin-bottom:10px;">

@include('admin.department_student_report._tables')

</body>
</html>
