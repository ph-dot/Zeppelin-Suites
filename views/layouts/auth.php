<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites - Login', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="<?= htmlspecialchars($baseUrl ?? '') ?>/output.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Geist", "DM Sans", sans-serif;
        }
        html, body {
            touch-action: pan-x pan-y;
            overscroll-behavior: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white overflow-x-hidden text-slate-800">
    <?= $content ?>
</body>
</html>
