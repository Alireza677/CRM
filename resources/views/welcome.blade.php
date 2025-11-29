<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سامانه یکپارچه مدیریت سازمان اخگر تابش</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Vazir', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            /* بکگراند جدید */
            background-image: url('/images/backgrrr.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            /* فیلتر تاریک برای خوانایی بهتر */
            position: relative;
        }

        /* لایه تیره ملایم */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.55); /* می‌تونی تیره یا روشن‌تر کنی */
            backdrop-filter: blur(3px);
        }

        .container {
            position: relative; /* برای اینکه از زیر لایه بیرون بیاید */
            text-align: center;
            background: rgba(255, 255, 255, 0.85);
            padding: 50px 35px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #1e3a8a;
            margin-bottom: 12px;
            font-size: 26px;
            line-height: 1.8;
        }

        h2 {
            color: #475569;
            font-size: 16px;
            margin-bottom: 30px;
            font-weight: normal;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .button-blue {
            background-color: #2563eb;
            color: white;
        }

        .button-blue:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>سامانه یکپارچه مدیریت سازمان اخگرتابش</h1>
        <h2>پلتفرمی برای مدیریت مشتریان، فرآیندهای سازمانی و تحلیل هوشمند داده</h2>

        <a href="{{ route('login') }}" class="button button-blue">
            ورود به سیستم
        </a>
    </div>

</body>
</html>
