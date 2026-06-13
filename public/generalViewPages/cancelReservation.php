<?php
require_once __DIR__ . '/../php_files/db.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die("Invalid cancellation link.");
}

$sql = "
    SELECT 
        r.reservation_id,
        r.client_name,
        r.client_email,
        r.reservation_status,
        r.payment_status,
        r.cancellation_status,
        r.client_cancel_token_expires_at,
        u.unit_type,
        u.unit_number
    FROM reservation_table r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    WHERE r.client_cancel_token = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();
$stmt->close();

if (!$reservation) {
    die("Invalid or expired cancellation link.");
}

if (
    !empty($reservation['client_cancel_token_expires_at']) &&
    strtotime($reservation['client_cancel_token_expires_at']) < time()
) {
    die("This cancellation link has expired.");
}

if ($reservation['payment_status'] !== 'verified') {
    die("Cancellation request is only available after payment verification.");
}

if (in_array($reservation['reservation_status'], ['cancelled', 'rejected', 'reserved'])) {
    die("Cancellation request is no longer available for this reservation.");
}

if ($reservation['cancellation_status'] === 'requested') {
    die("A cancellation request has already been submitted for this reservation.");
}

if ($reservation['cancellation_status'] === 'approved') {
    die("This reservation cancellation has already been approved.");
}

$unitName = trim(($reservation['unit_type'] ?? '') . ' Unit ' . ($reservation['unit_number'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Reservation Cancellation | Zeppelin Suites</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    * {
      font-family: 'Inter', sans-serif;
    }

    .mono {
      font-family: 'DM Mono', monospace;
    }

    .logo-bars span {
      display: block;
      width: 5px;
      border-radius: 999px;
      background: linear-gradient(to top, #facc15, #f8fafc);
      opacity: 0.95;
    }

    .zep-input:focus {
      outline: none;
      border-color: #0f172a;
      box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.08);
    }

    .btn-press:active {
      transform: scale(0.98);
    }
  </style>
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

  <!-- HERO HEADER -->
  <header class="relative overflow-hidden bg-slate-950 border-b border-slate-800">
    <div class="absolute inset-0 opacity-20">
      <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-red-500 blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-amber-400 blur-3xl"></div>
    </div>

    <div class="max-w-[1180px] mx-auto px-5 py-8 md:py-10 relative">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">

        <div class="flex items-center gap-5">
          <div class="w-[96px] h-[96px] rounded-3xl bg-white/10 border border-white/10 flex flex-col items-center justify-center">
            <div class="logo-bars flex items-end justify-center gap-1 h-11 mb-2">
              <span style="height:18px"></span>
              <span style="height:28px"></span>
              <span style="height:38px"></span>
              <span style="height:48px"></span>
              <span style="height:34px"></span>
              <span style="height:24px"></span>
              <span style="height:16px"></span>
            </div>

            <div class="text-[9px] tracking-[0.28em] leading-tight text-slate-200 text-center font-semibold">
              ZEPPELIN<br>SUITES
            </div>
          </div>

          <div>
            <p class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-red-300 mb-2">
              Cancellation Request
            </p>

            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white">
              Reservation Cancellation Form
            </h1>

            <p class="mt-3 text-sm md:text-base text-slate-300 max-w-2xl leading-relaxed">
              Submit a cancellation request for admin review. This does not automatically cancel your reservation.
            </p>
          </div>
        </div>

        <div class="bg-white/10 border border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-200 max-w-sm">
          <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-400/10 text-red-300 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>

            <div>
              <p class="font-bold text-white">Admin Approval Required</p>
              <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                Your request will be reviewed before any cancellation is applied.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="max-w-[1180px] mx-auto px-5 py-8 md:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-[1.5fr_0.8fr] gap-6">

      <!-- FORM CARD -->
      <section class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
        <div class="px-6 md:px-8 py-6 border-b border-slate-100">
          <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.25em]">Cancellation Details</p>
          <h2 class="text-2xl font-bold text-slate-900 mt-2">Request Cancellation</h2>
          <p class="text-sm text-slate-500 mt-2">
            Please review your reservation information and provide your reason for cancellation.
          </p>
        </div>

        <div class="p-6 md:p-8 space-y-6">

          <!-- RESERVATION SUMMARY -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Reservation Unit</p>
              <p class="text-lg font-bold text-slate-900 mt-1">
                <?php echo htmlspecialchars($unitName); ?>
              </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Client Name</p>
              <p class="text-lg font-bold text-slate-900 mt-1">
                <?php echo htmlspecialchars($reservation['client_name']); ?>
              </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Payment Status</p>
              <p class="text-sm font-bold text-emerald-700 mt-1">
                <?php echo htmlspecialchars(ucwords($reservation['payment_status'])); ?>
              </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Reservation Status</p>
              <p class="text-sm font-bold text-amber-700 mt-1">
                <?php echo htmlspecialchars(ucwords($reservation['reservation_status'])); ?>
              </p>
            </div>
          </div>

          <!-- POLICY NOTICE -->
          <div class="rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-amber-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>

              <p class="text-sm text-amber-800 leading-relaxed">
                Submitting this form does not automatically cancel your reservation. 
                This request will be reviewed by Zeppelin Suites administration. 
                Reservation fees are <span class="font-bold">non-refundable once verified and processed</span>, according to the reservation policy.
              </p>
            </div>
          </div>

          <!-- FORM -->
         <form id="cancelRequestForm" action="ActionsGV/submitCancellationRequest.php" method="POST" class="space-y-5">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div>
              <label class="block text-sm font-bold text-slate-700 mb-2">
                Reason for Cancellation <span class="text-red-500">*</span>
              </label>

              <textarea 
                name="reason" 
                rows="6" 
                required
                placeholder="Please explain why you are requesting cancellation..."
                class="zep-input w-full px-4 py-3 border border-slate-300 rounded-2xl text-sm text-slate-800 resize-none"></textarea>

              <p class="text-xs text-slate-400 mt-2">
                Be clear and specific. Admin will review this reason before approving or declining your request.
              </p>
            </div>

            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 cursor-pointer">
              <input 
                type="checkbox" 
                required
                class="mt-1 w-4 h-4 rounded border-slate-300 accent-slate-900">

              <span class="text-sm text-slate-700 leading-relaxed">
                I understand that this is only a cancellation request and is subject to admin approval. 
                I also understand that the reservation fee is 
                <span class="font-bold text-red-700">non-refundable once verified and processed</span>.
              </span>
            </label>

            <button 
            type="button"
            onclick="openConfirmCancelModal()"
            class="btn-press w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-4 rounded-2xl tracking-wide transition-all">
            SUBMIT CANCELLATION REQUEST
            </button>
          </form>

        </div>
      </section>

      <!-- SIDE PANEL -->
      <aside class="space-y-5">

        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
          <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5L19 9.5V19a2 2 0 01-2 2z"/>
            </svg>
          </div>

          <h3 class="text-lg font-bold text-slate-900">Cancellation Process</h3>

          <div class="mt-5 space-y-4">
            <div class="flex gap-3">
              <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">1</div>
              <p class="text-sm text-slate-600 leading-relaxed">
                Client submits cancellation request with reason.
              </p>
            </div>

            <div class="flex gap-3">
              <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">2</div>
              <p class="text-sm text-slate-600 leading-relaxed">
                Admin reviews the request and reservation status.
              </p>
            </div>

            <div class="flex gap-3">
              <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">3</div>
              <p class="text-sm text-slate-600 leading-relaxed">
                If approved, the reservation is cancelled and the unit is released.
              </p>
            </div>
          </div>
        </section>

        <section class="bg-red-50 border border-red-200 rounded-3xl p-6">
          <h3 class="text-base font-bold text-red-800">Important Policy</h3>
          <p class="text-sm text-red-700 leading-relaxed mt-3">
            Reservation fees are non-refundable once verified and processed. 
            Cancellation requests are reviewed by Zeppelin Suites administration and are not automatically approved.
          </p>
        </section>

      </aside>

    </div>
  </main>

  <!-- CONFIRM CANCELLATION REQUEST MODAL -->
<div id="confirmCancelModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-red-600 px-6 py-4">
      <h2 class="text-lg font-bold text-white">Submit Cancellation Request?</h2>
      <p class="text-sm text-red-50 mt-1">Please confirm before proceeding.</p>
    </div>

    <div class="p-6">
      <p class="text-sm text-slate-700 leading-relaxed">
        Are you sure you want to submit this cancellation request?
        This will be sent to Zeppelin Suites administration for review.
      </p>

      <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
        <p class="text-xs text-amber-800 leading-relaxed">
          Submitting this request does not automatically cancel your reservation.
          Reservation fees are non-refundable once verified and processed.
        </p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
      <button 
        type="button" 
        onclick="closeConfirmCancelModal()" 
        class="px-5 py-2 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100">
        Go Back
      </button>

      <button 
        type="button" 
        onclick="submitCancelRequestForm()" 
        class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl">
        Yes, Submit Request
      </button>
    </div>
  </div>
</div>

<script>
function openConfirmCancelModal() {
  const form = document.getElementById('cancelRequestForm');
  const reason = form.querySelector('textarea[name="reason"]');
  const checkbox = form.querySelector('input[type="checkbox"]');

  if (!reason.value.trim()) {
    reason.focus();
    alert('Please enter your reason for cancellation.');
    return;
  }

  if (!checkbox.checked) {
    alert('Please confirm that you understand the cancellation policy.');
    return;
  }

  const modal = document.getElementById('confirmCancelModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeConfirmCancelModal() {
  const modal = document.getElementById('confirmCancelModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function submitCancelRequestForm() {
  const btn = event.currentTarget;
  btn.disabled = true;
  btn.textContent = 'Submitting...';

  document.getElementById('cancelRequestForm').submit();
}
</script>

</body>
</html>