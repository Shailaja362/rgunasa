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
            font-family: 'Axiforma', sans-serif;
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
            top: 210px;
            left: 100px;
            right: 100px;
            text-align: center;
            font-weight: 600;
            /* letter-spacing: -0.04em; */
            line-height: 1.2;
            font-size: 16.5px;
        }

        /* TEXT */
        .line {
            margin-bottom: 10px;
        }

        /* UNDERLINES */
        .underline {
            display: inline-block;
            border-bottom: 2px solid #000;
            padding: 0 8px 4px 8px;
            /* added bottom padding so text sits above the line */
            font-size: 15px;
            line-height: 1.2;
            vertical-align: bottom;
        }

        .underline_department {
            min-width: 570px;
        }

        .underline_event {
            min-width: 260px;
        }

        .underline_grade {
            min-width: 560px;
        }

        .underline_name {
            min-width: 320px;
        }

        .underline_sem {
            min-width: 150px;
        }

        .underline_reg {
            min-width: 240px;
        }

        .underline_aca {
            min-width: 230px;
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
            font-weight: bold;
            padding: 5px 10px;
            text-align: center;
        }

        .tag img {
            height: 30px;
        }

        /* ================= CLUB LOGOS ================= */
        .club-logos {
            display: flex;
            gap: 40px;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: 245px;
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
            margin: 0 10px;
        }

        .club-logos-first img {
            height: 35px;
            margin: 0 10px;
        }

        /* ================= FOOTER TEXT ================= */
        .footer-text {
            position: absolute;
            bottom: 140px;
            left: 120px;
            right: 120px;
            text-align: center;
            font-size: 13px;
            line-height: 11.5px;
        }

        /* ================= SIGNATURES ================= */
        .sign-left {
            position: absolute;
            bottom: 25px;
            left: 130px;
            text-align: center;
        }

        .sign-right {
            position: absolute;
            bottom: 25px;
            right: 80px;
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
            font-size: 10px;
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
            <div>
                This is to certify that Mr. / Ms.
                <span class="underline line underline_name"><b>{{ $student->name }}</b></span> Reg. No. <span
                    class="underline line underline_reg"><b>{{ $student->register_number }}</b></span><br>
                of
                <span class="underline line underline_department"><b>{{ $student->get_department->name }}</b></span>
            </div>
            <div class="line small">
                has actively participated and made valuable contributions to <span
                    class="underline event underline_event"><b>{{ $event->title }}</b></span>
            </div>

            <div class="line small">
                and has secured
                <span class="underline underline_grade">
                    @if ($registration->grade == 'a')
                        Winner <b>({{ strtoupper($registration->grade) }})</b>
                    @elseif ($registration->grade == 'b')
                        Runner <b>({{ strtoupper($registration->grade) }})</b>
                    @elseif ($registration->grade == 'c')
                        Completed <b>({{ strtoupper($registration->grade) }})</b>
                    @elseif ($registration->grade == 'd')
                        Disqualified <b>({{ strtoupper($registration->grade) }})</b>
                    @endif
                </span>
                Grade <br>
            </div>
            <div class="line small">
                during the <span class="underline underline_sem">
                    @if (
                        $registration?->student?->semester == 1 ||
                            $registration?->student?->semester == 3 ||
                            $registration?->student?->semester == 8 ||
                            $registration?->student?->semester == 7)
                        ODD
                    @elseif (
                        $registration?->student?->semester == 2 ||
                            $registration?->student?->semester == 4 ||
                            $registration?->student?->semester == 6 ||
                            $registration?->student?->semester == 8)
                        Even
                    @endif
                </span> semester of the Academic Year <span class="underline underline_aca">2025-2026</span>.
            </div>
        </div>
        <div class="club-logos-first">
            <img src="{{ public_path('images/logos/nasa_1.png') }}" alt="">
            <img src="{{ public_path('images/logos/nasa_2.png') }}" alt="">
            <img src="{{ public_path('images/logos/nasa_4.png') }}" alt="">
            <img src="{{ public_path('images/logos/layer_4.png') }}" alt="">
            <img src="{{ public_path('images/logos/hue_saturation_1.png') }}" alt="">
            <img style="height:25px;" src="{{ public_path('images/logos/femme_fution.png') }}" alt="">
            <img src="{{ public_path('images/logos/sky.png') }}" alt="">
            <img src="{{ public_path('images/logos/rise_english_club.png') }}" alt="">
        </div>
        <div class="club-logos">
            <img src="{{ public_path('images/logos/cultural_club.png') }}">
            <img src="{{ public_path('images/logos/layer_2.png') }}">
            <img src="{{ public_path('images/logos/tamil_mandram.png') }}">
            <img style="height:25px;" src="{{ public_path('images/logos/dpc.png') }}">
            <img src="{{ public_path('images/logos/club_media.png') }}">
            <img src="{{ public_path('images/logos/the_logic_hub.png') }}">
            <img src="{{ public_path('images/logos/the_vibe_club.png') }}">
            <img src="{{ public_path('images/logos/the_food_security.png') }}">
            <img style="height:40px;" src="{{ public_path('images/logos/origami.png') }}">
            <img src="{{ public_path('images/logos/layer_9.png') }}">
            <img src="{{ public_path('images/logos/layer_6.png') }}">
            <img src="{{ public_path('images/logos/layer_2_copy.png') }}">
            <img src="{{ public_path('images/logos/layer_7.png') }}">
            <img src="{{ public_path('images/logos/layer_8.png') }}">
        </div>
        <div class="footer-text">
            THE PRIMARY MOTIVE OF THE NON-ACADEMIC (NAC) SYSTEM AT RATHINAM TECHNICAL CAMPUS (AUTONOMOUS)<br>
            IS TO INVOLVE STUDENTS IN SOCIAL AND SOCIETAL ENHANCEMENT ACTIVITIES.<br>
            THE RECIPIENT'S PARTICIPATION IN THIS STUDENT CLUB ACTIVITY HAS MADE A SIGNIFICANT IMPACT,AND AS A
            RESULT,<br>
            THIS CERTIFICATE HAS BEEN ISSUED AND VALUED EQUIVALENT TO ONE NON-ACADEMIC ACTIVITY.<br>
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
