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
            background: url('{{ public_path('images/logos/free_template.jpeg') }}') no-repeat;
            background-size: 1123px 794px;
        }

        /* ================= CONTENT ================= */
        .content {
            position: absolute;
            top: 250px;
            left: 100px;
            right: 100px;
            text-align: center;
        }

        /* TEXT */
        .line {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* SMALL TEXT */
        .small {
            font-size: 19px;
            font-weight: 500;
        }

        /* UNDERLINES */
        .underline {
            display: inline-block;
            border-bottom: 2px solid #000;
            min-width: 260px;
            padding: 0 8px;
        }

        /* EVENT FIELD */
        .event {
            min-width: 420px;
        }

        /* ================= TOP LOGOS ================= */
        .top-logos {
            position: absolute;
            top: 92px;
            right: 20px;
        }

        .top-logos img {
            height: 75px;
            margin-left: 4px;
        }

        /* ================= TAG ================= */
        .tag {
            position: absolute;
            top: 160px;
            right: 33px;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 10px;
            text-align: center;
        }

        .tag img {
            height: 30px;
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
            bottom: 25px;
            left: 390px;
            text-align: center;
        }

        .sign-right {
            position: absolute;
            bottom: 25px;
            right: 325px;
            text-align: center;
        }

        .sign-right img {
            height: 40px;
        }

        .sign-left img {
            height: 70px;
        }

        .sign-name {
            font-size: 13px;
            font-weight: bold;
        }

        .sign-title {
            font-size: 11px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="top-logos">
            <img src="{{ public_path('images/rtc_logo.png') }}">
        </div>
        <div class="tag">
            <img src="{{ public_path('images/logos/non.png') }}">
        </div>
        <div class="content">
            <div class="line">
                This is to certify that
                <span class="underline">{{ $student->name }}</span>
                of
                <span class="underline">{{ $student->get_department->name }}</span>
            </div>
            <div class="line small">
                has actively participated and made valuable contributions to
            </div>
            <div class="line">
                <span class="underline event">{{ $event->title }}</span>
            </div>
            <div class="line small">
                and has secured
                <span class="underline">{{ strtoupper($registration->grade) }}</span>
                during the
                <span class="underline">{{ $registration?->student?->semester ?? '' }}</span>
                semester.
            </div>
            <div class="line small">
                Organized by Rathinam Technical Campus
            </div>
        </div>
        <div class="club-logos-first">
            <img src="{{ public_path('images/logos/nasa_1.png') }}" alt="">
            <img src="{{ public_path('images/logos/nasa_2.png') }}" alt="">
            <img src="{{ public_path('images/logos/nasa_4.png') }}" alt="">
            <img src="{{ public_path('images/logos/layer_4.png') }}" alt="">
            <img src="{{ public_path('images/logos/hue_saturation_1.png') }}" alt="">
            <img src="{{ public_path('images/logos/femme_fusion_2.png') }}" alt="">
            <img src="{{ public_path('images/logos/sky_watching_club.png') }}" alt="">
            <img src="{{ public_path('images/logos/rise_english_club.png') }}" alt="">
        </div>
        <div class="club-logos">
            <img src="{{ public_path('images/logos/cultural_club.png') }}">
            <img src="{{ public_path('images/logos/layer_2.png') }}">
            <img src="{{ public_path('images/logos/dcp_femme.png') }}">
            <img src="{{ public_path('images/logos/IMG_6855.png') }}">
            <img src="{{ public_path('images/logos/the_logic_hub.png') }}">
            <img src="{{ public_path('images/logos/tamil_mandram.png') }}">
            <img src="{{ public_path('images/logos/the_logic_hub.png') }}">
            <img src="{{ public_path('images/logos/the_vibe_club.png') }}">
            <img src="{{ public_path('images/logos/the_food_security.png') }}">
            <img src="{{ public_path('images/logos/origami.png') }}">
            <img src="{{ public_path('images/logos/layer_9.png') }}">
            <img src="{{ public_path('images/logos/layer_6.png') }}">
            <img src="{{ public_path('images/logos/layer_2_copy.png') }}">
            <img src="{{ public_path('images/logos/layer_7.png') }}">
            <img src="{{ public_path('images/logos/layer_8.png') }}">
        </div>
        <div class="footer-text">
            THE PRIMARY MOTIVE OF THE NAC SYSTEM AT RATHINAM TECHNICAL CAMPUS (AUTONOMOUS)<br>
            IS TO INVOLVE STUDENTS IN SOCIAL AND SOCIETAL ENHANCEMENT ACTIVITIES.<br>
            THE RECIPIENT'S PARTICIPATION IN THIS STUDENT CLUB ACTIVITY HAS MADE A SIGNIFICANT IMPACT,AND AS A
            RESULT,<br>
            THEY HAVE EARNED THIS CERTIFICATE VALUED EQUIVALENT TO ONE NON-ACADEMIC ACTIVITY.<br>
        </div>
        <div class="sign-left sign">
            <img src="{{ public_path('images/logos/dr_c_krishnaraj_principal.png') }}">
            <div class="sign-name">Dr. C. Krishnaraj</div>
            <div class="sign-title">Principal - Academics</div>
        </div>
        <div class="sign-right sign">
            <img src="{{ public_path('images/logos/g_sign.png') }}">
            <div class="sign-name">Dr. K. Geetha</div>
            <div class="sign-title">Principal - Administration</div>
        </div>
    </div>
</body>
</html>
