<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Cancellation Confirmation View
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
  <title><?= htmlspecialchars($pageTitle ?? 'Cancellation Confirmed — Zeppelin Suites') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }

    body {
      background-color: #f8fafc;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .bg-shape {
      position: fixed;
      pointer-events: none;
      z-index: 0;
    }

    .dot-grid {
      width: 200px; height: 200px;
      background-image: radial-gradient(circle, #cbd5e1 1.5px, transparent 1.5px);
      background-size: 16px 16px;
      opacity: 0.6;
    }

    .geo-circle-lg {
      width: 400px; height: 400px;
      border: 1px solid #e2e8f0;
      border-radius: 50%;
    }

    .geo-circle-md {
      width: 250px; height: 250px;
      border: 1px solid #e2e8f0;
      border-radius: 50%;
    }

    .ambient-shade-left {
      width: 500px; height: 500px;
      background: radial-gradient(circle, #f1f5f9 0%, transparent 70%);
      opacity: 0.8;
    }

    .ambient-shade-right {
      width: 400px; height: 400px;
      background: radial-gradient(circle, #e2e8f0 0%, transparent 70%);
      opacity: 0.5;
    }

    .top-bar, .bottom-bar {
      position: fixed; left: 0; right: 0;
      height: 8px;
      background: #0f172a;
      z-index: 100;
    }
    .top-bar { top: 0; }
    .bottom-bar { bottom: 0; }

    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .icon-circle {
      width: 64px; height: 64px;
      background: #fef2f2;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
    }

    .divider {
      border: none;
      border-top: 1px solid #e2e8f0;
      margin: 24px 0;
    }
  </style>
</head>
<body>

  <div class="top-bar"></div>
  <div class="bottom-bar"></div>

  <div class="bg-shape ambient-shade-left" style="top: -10%; left: -10%;"></div>
  <div class="bg-shape ambient-shade-right" style="bottom: -5%; right: -5%;"></div>

  <div class="bg-shape geo-circle-lg" style="top: -100px; right: -50px;"></div>
  <div class="bg-shape geo-circle-md" style="bottom: 10%; left: -80px;"></div>

  <div class="bg-shape dot-grid" style="top: 80px; left: 60px;"></div>
  <div class="bg-shape dot-grid" style="bottom: 80px; right: 60px;"></div>

  <!-- Main content -->
  <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-24">
    <div class="card w-full max-w-lg p-10 text-center">

      <!-- Logo -->
      <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl) ?>/">
          <img
            src="<?= htmlspecialchars($baseUrl) ?>/images/condo_photos/zeppelin-logo.png"
            alt="Zeppelin Suites"
            style="height:70px; margin:0 auto;"
            onerror="this.outerHTML='<div style=\'font-size:22px;font-weight:800;letter-spacing:0.04em;color:#111;\'>ZEPPELIN<br><span style=\'font-size:10px;font-weight:400;letter-spacing:0.25em;color:#6b7280;\'>SUITES</span></div>'"
          >
        </a>
      </div>

      <hr class="divider">

      <!-- Icon -->
      <div class="icon-circle">
        <svg width="32" height="32" fill="none" stroke="#dc2626" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="9"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl font-bold text-zinc-900 mb-3">
        Your Cancellation Request Has Been Submitted
      </h1>

      <!-- Body text -->
      <p class="text-zinc-500 text-sm leading-relaxed mb-6">
        Your cancellation request has been successfully submitted and is now pending review by Zeppelin Suites administration. You will be notified once a decision has been made.
      </p>

      <!-- Detail summary -->
      <div class="bg-slate-50 rounded-xl p-5 text-left mb-6 space-y-3">
        <p class="text-xs font-semibold tracking-widest text-zinc-400 uppercase mb-2">Cancellation Summary</p>
        <div class="flex items-center justify-between text-sm">
          <span class="text-zinc-500">Cancellation Ref.</span>
          <span class="font-mono font-bold text-zinc-900" id="cancel-num">Pending Review</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-zinc-500">Status</span>
          <span class="text-xs font-semibold text-red-800 bg-red-100 px-3 py-1 rounded-full">Cancellation Requested</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-zinc-500">Refund Policy</span>
          <span class="text-xs text-zinc-700">Subject to booking terms</span>
        </div>
      </div>

      <p class="text-zinc-500 text-sm leading-relaxed mb-4">
        A cancellation acknowledgement will be coordinated by our office team.
      </p>

      <p class="text-amber-600 text-xs font-medium mb-6">
        If you have questions regarding this request, please contact our support at info@zeppelinsuites.com.
      </p>

      <div class="pt-4 border-t border-slate-100 flex justify-center">
        <a href="<?= htmlspecialchars($baseUrl) ?>/" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-all">
          Return to Homepage
        </a>
      </div>

    </div>
  </div>

  <script>
    document.getElementById('cancel-num').textContent =
      '#CAN-' + String(Math.floor(100000 + Math.random() * 900000));
  </script>

</body>
</html>
