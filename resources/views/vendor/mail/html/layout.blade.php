<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="fa" xmlns="http://www.w3.org/1999/xhtml" dir="rtl">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        /* --- RTL baseline overrides for Laravel Mail --- */
        body, table, td, p, h1, h2, h3, h4, h5, h6 { direction: rtl !important; text-align: right !important; font-family: Tahoma, Arial, sans-serif !important; }
        .wrapper, .content, .body, .inner-body, .content-cell, .panel, .subcopy, .footer { direction: rtl !important; text-align: right !important; }
        .inner-body { width: 570px; }
        .content-cell { padding: 32px; }

        /* Lists */
        ul, ol { margin: 0 1.25em 1em 0 !important; }
        li { margin: 0.25em 0; }

        /* Buttons */
        .button, .button-primary, .button-secondary, .button-success, .button-danger {
            direction: rtl !important;
            text-align: center !important;
            display: inline-block !important;
        }
        a.button, a.button-primary, a.button-secondary, a.button-success, a.button-danger {
            text-decoration: none !important;
        }

        /* Panels & subcopy */
        .panel-content { direction: rtl !important; text-align: right !important; }
        .subcopy p { font-size: 12px !important; color: #6b7280 !important; }

        /* Divider alignment fix */
        .hr { height: 1px; background: #e5e7eb; border: 0; }

        /* LTR islands (URLs/کد) — استفاده با <span class="ltr">...</span> */
        .ltr { direction: ltr !important; text-align: left !important; unicode-bidi: plaintext; }

        /* Responsiveness */
        @media only screen and (max-width: 600px) {
            .inner-body { width: 100% !important; }
            .footer { width: 100% !important; }
        }
        @media only screen and (max-width: 500px) {
            .button { width: 100% !important; }
        }
    </style>
    {{ $head ?? '' }}
</head>
<body dir="rtl" style="margin:0; padding:0; width:100%; background-color:#f3f4f6; direction:rtl; text-align:right; font-family:Tahoma, Arial, sans-serif;">

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f3f4f6;">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                {{ $header ?? '' }}

                <!-- Email Body -->
                <tr>
                    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; border:0;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff; border-radius:8px; overflow:hidden; direction:rtl; text-align:right;">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell" style="direction:rtl; text-align:right;">
                                    {{ Illuminate\Mail\Markdown::parse($slot) }}

                                    {{ $subcopy ?? '' }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{ $footer ?? '' }}
            </table>
        </td>
    </tr>
</table>
</body>
</html>
