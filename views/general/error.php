<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Public Error View
 *
 * @var string $baseUrl
 * @var string $pageTitle
 * @var string $errorTitle
 * @var string $errorMessage
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Notice — Zeppelin Suites') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
  </style>
</head>
<body class="bg-zinc-50 text-zinc-900 min-h-screen flex flex-col justify-between">

  <?php include __DIR__ . '/../components/public_navbar.php'; ?>

  <main class="max-w-xl mx-auto px-6 py-20 text-center">
    <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mx-auto mb-6 shadow-sm">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
    </div>

    <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 mb-3"><?= htmlspecialchars($errorTitle ?? 'Notice') ?></h1>
    <p class="text-zinc-600 text-sm md:text-base leading-relaxed mb-8"><?= htmlspecialchars($errorMessage ?? 'The requested resource could not be found or processed.') ?></p>

    <div class="flex items-center justify-center gap-4">
      <a href="<?= htmlspecialchars($baseUrl) ?>/"
        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-zinc-900 text-white font-semibold text-xs tracking-wider uppercase hover:bg-zinc-800 transition-all shadow-sm">
        Return to Home
      </a>
      <a href="<?= htmlspecialchars($baseUrl) ?>/contact"
        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white border border-zinc-200 text-zinc-700 font-semibold text-xs tracking-wider uppercase hover:bg-zinc-50 transition-all shadow-sm">
        Contact Support
      </a>
    </div>
  </main>

  <?php include __DIR__ . '/../components/public_footer.php'; ?>

</body>
</html>
