<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 20px 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            margin: 8px 0 6px 0;
            font-size: 14px;
        }

        h3 {
            font-size: 11px;
            margin: 14px 0 6px 0;
        }

        .subtitle {
            text-align: center;
            margin: 0 0 8px 0;
        }

        .logo {
            width: 100%;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #f0f0f0;
            text-align: center;
            font-size: 9px;
        }

        td {
            font-size: 8px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

<img src="{{ public_path('images/rgu_logo.jpeg') }}" class="logo">

@include('admin.department_student_report._tables')

</body>
</html>
