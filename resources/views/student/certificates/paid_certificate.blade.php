<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        @font-face {
            font-family: 'Axiforma';
            src: url('{{ public_path('fonts/Axiforma-Regular.ttf') }}') format('truetype');
            font-weight: normal;
        }

        @font-face {
            font-family: 'Axiforma';
            src: url('{{ public_path('fonts/Axiforma-SemiBold.ttf') }}') format('truetype');
            font-weight: bold;
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
            top: 230px;
            left: 100px;
            right: 100px;
            text-align: center;
            line-height: 2;
            font-size: 14px;
        }

        /* TEXT */
        .line {
            margin-bottom: 10px;
        }

        /* UNDERLINES */
        .underline {
            display: inline-block;
            border-bottom: 2px solid #000;
            min-width: 260px;
            padding: 0 8px;
        }

        .underline_event{
             min-width: 700px;
        }

        .underline_department{
            min-width: 390px;
        }

        /* EVENT FIELD */
        .event {
            min-width: 420px;
        }

        /* ================= TOP LOGOS ================= */
        .top-logos img {
            height: 95px;
            position: absolute;
            top: 90px;
            right: 50px;
            /* margin-left: 4px; */
        }

        /* ================= TAG ================= */
        .tag {
            position: absolute;
            top: 160px;
            right: 185px;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 10px;
            text-align: center;
        }

        /* ================= CLUB LOGOS ================= */
        .club-logos {
            /* position: absolute;
            bottom: 105px;
            left: 60px;
            right: 60px; */
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
            /* bottom: 125px; */
            left: 120px;
            right: 120px;
            text-align: center;
            font-size: 13px;
            line-height: 16px;
        }

        /* ================= SIGNATURES ================= */
        .sign-left {
            position: absolute;
            bottom: 25px;
            left: 80px;
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
            bottom: 25px;
            right: 80px;
            text-align: center;
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
            bottom: 25px;
            left: 200px;
            right: 200px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .divider {
            width: 1px;
            height: 30px;
            background-color: grey;
            display: inline-block;
            margin: 0 10px;
        }

        .club-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: 190px;
            left: 60px;
            right: 60px;
            text-align: center;
        }

        .club-logos-first {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 270px;
            left: 60px;
            right: 60px;
            text-align: center;
        }

        .club-logos img {
            height: 45px;
            margin: 0 20px; /* 50px left + 50px right = 100px gap */
        }

        .club-logos-first img {
            height: 35px;
            margin: 0 20px;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div class="top-logos">
            <img src="{{ public_path('images/coe_certificates/coe_logo.png') }}">
        </div>
        <div class="content">
            <div>
                This is to certify that Mr. / Ms.
                <span class="underline"><b>{{ $student->name }}</b></span> Reg. No.<span
                    class="underline"><b>{{ $student->register_number }}</b></span><br>
                of
                <span class="underline underline_department"><b>{{ $student->get_department->name }}</b></span> has successfully participated
                and demonstrated
            </div>
            <div class="line">
                practical proficiency in the titled
            </div>
            <div class="line small">
                <span class="underline underline_event"><b>{{ $event->title }}</b></span>
            </div>
            <div class="line small">
                as a part of the Value-Added Learning Series held on <span
                    class="underline"><b>{{ \Carbon\Carbon::parse($registration->get_event_schedule->event_date)->format('d-m-Y') }}</b></span>.
            </div>
        </div>
        <div class="club-logos-first">
            <img src="{{ public_path('images/coe_certificates/ai_skill_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/cisco_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/bio_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/web_3_0_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/cloud_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/chip_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/mac_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/meta_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/co_manufacturing_hub.png') }}" alt="">
        </div>
        <div class="club-logos">
            <img src="{{ public_path('images/coe_certificates/climate_action_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/data_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/m2m_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/cyber_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/design_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/drone_tech_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/ev_tech_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/quantum_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/robotics_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/rpa_hub.png') }}" alt="">
            <img src="{{ public_path('images/coe_certificates/startup_school_hub.png') }}" alt="">
        </div>
        <div class="sign-left sign">
            <img src="{{ public_path('images/logos/dr_c_krishnaraj_principal.png') }}">
            <div class="sign-name">Dr. C. Krishnaraj</div>
            <div class="sign-title">Principal - Academics</div>
        </div>
        <div class="sign-center">
            <img src="{{ public_path('images/logos/g_sign.png') }}">
            <div class="sign-name">Dr. K. Geetha</div>
            <div class="sign-title">Principal - Administration</div>
        </div>
</body>

</html>
