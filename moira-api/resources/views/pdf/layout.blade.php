<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2430; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        h2 { font-size: 13px; margin: 18px 0 6px 0; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e4e2f5; font-size: 11px; }
        th { background: #f4f3fb; color: #4b4f5c; }
        .text-right { text-align: right; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #12141c; padding-bottom: 12px; margin-bottom: 16px; }
        .totals td { border-bottom: none; padding: 3px 4px; }
        .totals .total-row td { font-weight: bold; font-size: 13px; border-top: 1px solid #12141c; padding-top: 6px; }
        .footer { margin-top: 24px; font-size: 9px; color: #9096a3; }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
