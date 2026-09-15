<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <style>
        @media only screen and (max-width: 640px) {
            .email-shell,
            .email-body,
            .email-footer {
                width: 100% !important;
            }

            .email-side-padding {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .email-content-cell {
                padding: 28px 22px !important;
            }
        }
    </style>
    {!! $head ?? '' !!}
</head>
<body style="margin: 0; padding: 0; width: 100% !important; background-color: #f3f6fa; color: #344054;">
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 0; padding: 0; background-color: #f3f6fa;">
        <tr>
            <td class="email-side-padding" align="center" style="padding: 32px 16px;">
                <table class="email-shell" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width: 600px; max-width: 600px; margin: 0 auto;">
                    {!! $header ?? '' !!}

                    <tr>
                        <td class="email-body" width="100%" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #ffffff; border: 1px solid #e4eaf1; border-top: 0;">
                            <table class="email-body" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width: 600px; max-width: 100%;">
                                <tr>
                                    <td class="email-content-cell" style="padding: 38px 42px;">
                                        {!! Illuminate\Mail\Markdown::parse($slot) !!}

                                        {!! $subcopy ?? '' !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {!! $footer ?? '' !!}
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
