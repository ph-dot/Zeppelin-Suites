<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Privacy Policy View
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
  <title><?= htmlspecialchars($pageTitle ?? 'Privacy Policy — Zeppelin Suites') ?></title>
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
    <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 mb-4">Privacy Policy</h1>
    <p class="text-zinc-500 mb-12">Last updated: April 28, 2026</p>

    <div class="prose prose-zinc max-w-none text-zinc-600 leading-relaxed space-y-6">
      <p class="text-lg">At Zeppelin Suites, we respect your privacy and are committed to protecting your personal information.</p>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">1. Information We Collect</h2>
        <p>We may collect personal information such as your name, email address, phone number, and inquiry details when you contact us or submit a booking request.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">2. How We Use Your Information</h2>
        <ul class="list-disc pl-6 space-y-2 mt-2">
          <li>To respond to your inquiries and provide customer support</li>
          <li>To send relevant updates about Zeppelin Suites</li>
          <li>To improve our website and services</li>
          <li>To comply with legal obligations</li>
        </ul>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">3. Information Sharing</h2>
        <p>We do not sell your personal information. We may share your data with trusted service providers who assist us in operating our business, subject to strict confidentiality agreements.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">4. Cookies and Tracking</h2>
        <p>Our website uses cookies to enhance user experience and analyze traffic. You can manage your cookie preferences through your browser settings.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">5. Your Rights</h2>
        <p>You have the right to access, correct, or delete your personal information. Please contact us if you wish to exercise these rights.</p>
      </div>

      <div>
        <h2 class="text-2xl font-semibold text-zinc-900 mb-3">6. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
        <p class="font-medium text-zinc-900 mt-2">Email: info@zeppelinsuites.com</p>
        <p class="font-medium text-zinc-900">Phone: +63 917 123 4567</p>
      </div>
    </div>
  </main>

  <!-- ── FOOTER ────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_footer.php'; ?>

</body>
</html>
