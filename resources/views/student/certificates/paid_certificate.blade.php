<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        /* EXACT A4 LANDSCAPE */
        .certificate {
            position: relative;
            width: 1123px;
            height: 790px;
            overflow: hidden;
            background: url('{{ public_path('images/logos/paid_template.jpeg') }}') no-repeat;
            background-size: 1123px 794px;
        }

        /* ================= CONTENT ================= */
        .content {
            position: absolute;
            top: 250px;
            left: 100px;
            right: 100px;
            text-align: center;
            line-height: 2.8;
            font-size: 13px;
        }

        /* TEXT */
        .line {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        /* SMALL TEXT */
        .small {
            font-size: 14px;
            font-weight: 500;
        }

        /* UNDERLINES */
        .underline {
            display: inline-block;
            border-bottom: 2px solid #000;
            min-width: 260px;
            padding: 0 8px;
        }

        .issued_underline {
            display: inline-block;
            border-bottom: 2px solid #000;
            min-width: 100px;
            padding: 0 8px;
        }

        /* EVENT FIELD */
        .event {
            min-width: 420px;
        }

        /* ================= TOP LOGOS ================= */
        .top-logos {
            position: absolute;
            top: 90px;
            right: 140px;
        }

        .top-logos img {
            height: 55px;
            margin-left: 4px;
        }

        /* ================= TAG ================= */
        .tag {
            position: absolute;
            top: 160px;
            right: 125px;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 10px;
            text-align: center;
        }

        /* ================= CLUB LOGOS ================= */
        .club-logos {
            position: absolute;
            bottom: 225px;
            left: 60px;
            right: 60px;
            text-align: center;
        }

        .club-logos-first {
            position: absolute;
            bottom: 300px;
            left: 60px;
            right: 60px;
            text-align: center;
        }

        .club-logos img {
            height: 35px;
            margin: 0 3px;
        }

        .club-logos-first img {
            height: 35px;
            margin: 0 3px;
        }

        /* ================= FOOTER TEXT ================= */
        .footer-text {
            position: absolute;
            bottom: 125px;
            left: 120px;
            right: 120px;
            text-align: center;
            font-size: 13px;
            line-height: 16px;
        }

        /* ================= SIGNATURES ================= */
        .sign-left {
            position: absolute;
            bottom: 60px;
            left: 340px;
            text-align: center;
        }

        .sign-left1 {
            position: absolute;
            bottom: 25px;
            left: 50px;
            text-align: center;
        }

        .sign-center {
            position: absolute;
            bottom: 60px;
            right: 400px;
            text-align: center;
        }

        .sign-right {
            position: absolute;
            bottom: 60px;
            right: 155px;
            text-align: center;
        }

        .sign-right img {
            height: 40px;
        }

        .sign-left img {
            height: 70px;
        }

        .sign-center img {
            height: 50px;
        }

        .sign-name {
            font-size: 13px;
            font-weight: bold;
        }

        .sign-title {
            font-size: 11px;
            color: #555;
        }

        .signatures {
            position: absolute;
            bottom: 40px;
            left: 200px;
            right: 200px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div class="top-logos">
            <img src="{{ public_path('images/logos/aic_raise_logo_vertical.png') }}">
            <img src="{{ public_path('images/logos/niti_logo.png') }}">
        </div>
        <div class="tag">
            <img style="height: 50px;" src="{{ public_path('images/rtc_logo.png') }}">
            <img style="height: 50px;" src="{{ public_path('images/logos/coe_logo_copy.png') }}">
        </div>
        <div class="content">
            <div class="line">
                This is to certify that
                <span class="underline"><b>{{ $student->name }}</b></span>
                of
                <span class="underline"><b>{{ $student->get_department->name }}</b></span>
            </div>
            <div class="line small">
                has successfully participated and demonstrated practical proficiency in
            </div>
            <div class="line">
                <span class="underline event"><b>{{ $event->title }}</b></span>
            </div>
            <div class="line small">
                as part of the Value-Added Learning Series by Rathinam Technical Campus in association with
            </div>
            <div class="line small">
                AIC RAISE supported by Atal Innovation Mission, NITI AAYOG, Govt. of India.
            </div>
        </div>

        <div class="sign-left1 sign">
            Issued on:
            <span class="issued_underline">
                {{ isset($event->event_date) ? \Carbon\Carbon::parse($event->event_date)->format('F d, Y') : '' }}
            </span>
        </div>
        <div class="sign-left sign">
            <img src="{{ public_path('images/logos/dr_c_krishnaraj_principal.png') }}">
            <div class="sign-name">Dr. C. Krishnaraj</div>
            <div class="sign-title">Principal - Academics</div>
        </div>
        <div class="sign-center sign">
            <img src="{{ public_path('images/logos/g_sign.png') }}">
            <div class="sign-name">Dr. K. Geetha</div>
            <div class="sign-title">Principal - Administration</div>
        </div>
        <div class="sign-right sign">
            <img src="{{ public_path('images/logos/dr_nagaraj_sign.png') }}">
            <div class="sign-name">Dr. B. Nagaraj</div>
            <div class="sign-title">Chief Executive Officer - AIC RAISE</div>
        </div>
</body>

</html>
