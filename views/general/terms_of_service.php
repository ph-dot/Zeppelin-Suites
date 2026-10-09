<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Terms of Service View
 *
 * @var string $baseUrl
 * @var string $pageTitle
 * @var string $activePage
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Terms of Service — Zeppelin Suites') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
  </style>
</head>
<body class="bg-white text-zinc-900 flex flex-col min-h-screen">

  <!-- ── NAV ──────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_navbar.php'; ?>

  <main class="flex-grow max-w-4xl mx-auto px-6 md:px-8 py-16 md:py-24">
    <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 mb-4">Terms of Service</h1>
    <p class="text-zinc-500 mb-12">Last updated: April 28, 2026</p>

    <div class="prose prose-zinc max-w-none text-zinc-600 leading-relaxed space-y-6">
      <p class="text-lg">Welcome to Zeppelin Suites. By accessing or using our website, you agree to be bound by these Terms of Service.</p>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">1. Use of Website</h2>
        <p>This website is provided for informational purposes. You may not use it for any unlawful purpose or in any way that could damage, disable, or impair our site.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">2. Intellectual Property</h2>
        <p>All content, logos, images, and materials on this website are the property of Zeppelin Suites or its licensors and are protected by copyright and trademark laws.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">3. Booking and Reservations</h2>
        <p>All bookings and reservations are subject to availability and our booking terms. Deposits and payments are non-refundable unless otherwise stated.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">4. Limitation of Liability</h2>
        <p>Zeppelin Suites shall not be liable for any indirect, incidental, or consequential damages arising from your use of this website.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">5. Changes to Terms</h2>
        <p>We reserve the right to modify these Terms of Service at any time. Continued use of the website after changes constitutes acceptance of the new terms.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">6. Contact Us</h2>
        <p>If you have any questions about these Terms, please contact us at:</p>
        <p class="font-medium text-zinc-900 mt-2">Email: info@zeppelinsuites.com</p>
        <p class="font-medium text-zinc-900">Phone: +63 917 123 4567</p>
      </div>
    </div>
  </main>

  <!-- ── FOOTER ────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_footer.php'; ?>

</body>
</html>
