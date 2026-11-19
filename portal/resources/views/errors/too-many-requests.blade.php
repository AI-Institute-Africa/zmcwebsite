<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Too many requests</title>
    <style>
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #f5f7fa; color: #172033; font: 16px/1.55 system-ui, sans-serif; }
        main { width: min(100%, 440px); padding: 32px; border: 1px solid #e3e8ef; border-radius: 16px; background: #fff; box-shadow: 0 12px 36px #17203312; text-align: center; }
        h1 { margin: 0 0 10px; font-size: 24px; }
        p { margin: 0; color: #667085; }
    </style>
</head>
<body>
    <main>
        <h1>Too many requests</h1>
        <p>Please wait {{ max(1, (int) ceil($retryAfter / 60)) }} minute(s), then try again.</p>
    </main>
</body>
</html>
