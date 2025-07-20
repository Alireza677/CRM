<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>CRM اخگر تابش</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Vazir', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }

        .container {
            text-align: center;
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            color: #1e3a8a;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .button-blue {
            background-color: #2563eb;
            color: white;
            border: none;
        }

        .button-blue:hover {
            background-color: #1d4ed8;
        }

        .button-outline {
            background-color: transparent;
            color: #2563eb;
            border: 2px solid #2563eb;
        }

        .button-outline:hover {
            background-color: #e0edff;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>به سیستم CRM اخگر تابش خوش آمدید</h1>

        <a href="{{ route('login') }}" class="button button-blue">ورود به سیستم</a>
        <!-- <a href="{{ route('register') }}" class="button button-outline">ثبت‌نام</a> -->
    </div>

</body>
</html>
