<?php require_once __DIR__ . '/ActionsAP/getReplyData.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites - Rooms</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }

@keyframes indeterminateProgress {
  0% { transform: translateX(-100%); width: 30%; }
  50% { transform: translateX(50%); width: 50%; }
  100% { transform: translateX(200%); width: 30%; }
}

/* ── Sidebar ───────────────────────────────────────────── */
.sidebar {
  width: 256px;
  transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
}
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) {
  .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; }
  .sidebar.open { transform: translateX(0); }
}
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }

/* Overlay — zero dimming */
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

/* ── Sidebar links ─────────────────────────────────────── */
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,
.sidebar.collapsed .logo-text,.sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after {
  content: attr(data-tooltip);
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
  background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px;
  border-radius: 8px; white-space: nowrap; z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none;
}
.nav-label,.logo-text { transition: opacity 0.2s ease; }
.collapse-icon { transition: transform 0.3s ease; }

/* 1. Add transition for smooth disappearing */
.sidebar-logo { 
  transition: opacity 0.2s ease, width 0.2s ease; 
}

/* 2. Hide the logo completely when .collapsed class is present on the sidebar */
.sidebar.collapsed .sidebar-logo { 
  opacity: 0; 
  width: 0; 
  overflow: hidden; 
  pointer-events: none; 
}

/* ── Dropdowns ─────────────────────────────────────────── */
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition: transform 0.3s ease; }
.notice-chevron.rotated { transform: rotate(180deg); }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown:not(.hidden) { opacity:1; visibility:visible; transform:translateY(0); }

/* ── Stat card hover (reused) ──────────────────────────── */
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }

/* ── Room table rows ───────────────────────────────────── */
.room-row { transition: background 0.15s ease; }
.room-row:hover { background: #f8fafc; }

/* ── Scrollbar ─────────────────────────────────────────── */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

/* ── Buttons / inputs ──────────────────────────────────── */
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }

/* ── Glass header ──────────────────────────────────────── */
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }

/* ── Upload zone ───────────────────────────────────────── */
.upload-zone { border: 2px dashed #cbd5e1; transition: border-color 0.2s, background 0.2s; }
.upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }

/* ── Facilities panel ──────────────────────────────────── */
.facilities-panel { max-height:0; overflow:hidden; opacity:0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.facilities-panel.open { max-height:200px; opacity:1; }
.fac-chevron { transition: transform 0.3s ease; }
.fac-chevron.rotated { transform: rotate(180deg); }

/* ── INDEPENDENT COLUMN SCROLL ─────────────────────────── */
.content-area {
  height: calc(100vh - 65px);
  display: flex;
  overflow: hidden;
}
.col-scroll {
  overflow-y: auto;
  height: 100%;
}
@media (max-width: 1023px) {
  .content-area { display: block; height: auto; overflow: visible; }
  .col-scroll { overflow-y: visible; height: auto; }
  .mobile-scroll-wrap { overflow-y: auto; height: calc(100vh - 65px); }
}
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay and Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- ── MAIN WRAPPER ─────────────────────────────────────── -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- TOP BAR / NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- MAIN CONTENT AREA - Single column with scroll -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6">
    
        <!-- Inquiry Details Section -->
    <div class="w-full max-w-4xl mx-auto p-6 space-y-4 bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
    <p class="section-header text-lg font-bold text-slate-900 mb-0.5"> <?php echo replyClean($inquiry_type); ?> </p>

    <!-- Sender Info + Unit Info -->
    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
  <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white text-sm font-bold shrink-0" id="modalAvatar"> <?php echo replyClean($avatar); ?> </div>
        <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-800 truncate" id="modalName"> <?php echo replyClean($sender_name); ?> </p>
        <p class="text-xs text-slate-500 truncate" id="modalEmail"> <?php echo replyClean($sender_email); ?> </p>
        <p class="text-xs text-slate-500 truncate" id="modalContact"> <?php echo replyClean($sender_contact); ?> </p>
        </div>
        <div id="unitSection" class="text-right shrink-0" <?php echo $is_general ? 'style="display:none;"' : ''; ?>>
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Selected Unit</p>
        <p class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full" id="modalUnitPref"> <?php echo replyClean($preferred_unit); ?> </p>
        </div>
    </div>

    <!-- Preferred Move-In Time -->
    <div
      id="moveInTimeSection"
      class="flex items-center gap-3 pb-4 border-b border-slate-100"
      <?php echo !$is_lease_flow ? 'style="display:none;"' : ''; ?>>

      <div class="flex-1 min-w-0">
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">
          Preferred Move-In Time
        </p>

        <p
          class="text-sm font-semibold text-slate-800 truncate"
          id="modalMoveInTime">
          <?php echo replyClean(
              $preferred_move_in_time ?: '—'
          ); ?>
        </p>
      </div>
    </div>

    <!-- Lease Duration -->
    <div id="leaseDurationSection" class="flex items-center gap-3 pb-4 border-b border-slate-100"<?php echo !$is_lease_flow ? 'style="display:none;"' : ''; ?>>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Lease Duration</p>
        <p class="text-sm font-semibold text-slate-800 truncate" id="modalLeaseDuration"> <?php echo replyClean($lease_duration ?: '—'); ?> </p>
      </div>
    </div>
    <!-- Message Box Section -->
    <div class="pb-4 border-b border-slate-100">
        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Message</p>
        <div class="p-4 bg-gray-100 rounded-lg border border-slate-200">
       <p class="text-sm text-slate-800" id="modalMessage"><?php echo replyClean($message); ?> </p>
    </div>
    </div>
  </div>

    

    <!-- Email Reply Section -->
    <form 
      id="replyForm"
      action="ActionsAP/sendInquiryReply.php" 
      method="POST"
      onsubmit="handleReplySubmit(event)"
      class="w-full max-w-4xl mx-auto p-6 space-y-6 bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm"
    >

  <input type="hidden" name="inq_id" value="<?php echo (int)$inq_id; ?>">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">Auto-Generated Email Reply</h2>
          <p class="text-sm text-slate-500 mt-1">
            Review and edit the prepared response before sending it to the inquirer.
          </p>
        </div>
       <span class="text-xs font-semibold <?php echo replyClean($status_badge_class); ?> border px-3 py-1 rounded-full">
        <?php echo replyClean($status_badge_text); ?>
      </span>
      </div>

      <!-- Email Details - Single column -->
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
            To
          </label>
        <input 
            type="email" 
            id="replyToEmail"
            name="reply_to"
            value="<?php echo replyClean($sender_email); ?>"
            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
            Subject
          </label>
          <input 
            type="text" 
            id="replySubject"
            name="reply_subject"
            value="<?php echo replyClean($reply_subject); ?>"
            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 bg-slate-50 focus:outline-none focus:border-slate-900 focus:bg-white"
          >
        </div>
      </div>

      <!-- Selected Unit Summary - Single row wrap -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
          <p class="text-xs text-blue-500 uppercase font-semibold mb-1">
            <?php echo $approval_status === 'approved' ? 'Approved Unit' : 'Preferred Unit'; ?>
          </p>
          <p class="text-sm font-bold text-blue-900">
            <?php echo replyClean($unit_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Owner</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($owner_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Rate</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($rate_display); ?>
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
          <p class="text-xs text-slate-400 uppercase font-semibold mb-1">Lease Preference</p>
          <p class="text-sm font-bold text-slate-800">
            <?php echo replyClean($lease_display); ?>
          </p>
        </div>
      </div>

      <!-- Big Email Body -->
      <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
          Email Message
        </label>
        <textarea 
            id="emailBody"
            name="email_body"
            rows="18"
            class="w-full min-h-[420px] border border-slate-200 rounded-2xl px-5 py-4 text-sm text-slate-700 leading-7 bg-slate-50 resize-vertical focus:outline-none focus:border-slate-900 focus:bg-white"
          ><?php echo replyClean($email_body); ?>
        </textarea>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4">
        <button 
          type="button"
          onclick="history.back()"
          class="flex-1 sm:flex-none px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all">
          Cancel
        </button>

        <div class="flex items-center gap-3">
          <button 
            type="button"
            onclick="copyEmailReply()"
            class="px-5 py-2.5 text-sm font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-100 transition-all">
            Copy Reply
          </button>
         <button 
            type="submit"
            id="sendReplyBtn"
            class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            <span>Send Reply</span>
          </button>
        </div>
      </div>
    </form>
  </main>
</div>

<!-- Sending Loading Screen Overlay -->
<div id="sendingLoadingOverlay" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-md px-4">
  <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-100 p-8 text-center animate-in fade-in zoom-in-95 duration-200">
    
    <!-- Animated Sending Icon -->
    <div class="relative w-16 h-16 mx-auto mb-5 flex items-center justify-center">
      <div class="absolute inset-0 rounded-2xl bg-blue-500/20 animate-ping"></div>
      <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-tr from-slate-900 to-slate-800 flex items-center justify-center text-white shadow-lg shadow-slate-900/20">
        <svg class="w-8 h-8 text-blue-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
      </div>
    </div>

    <h3 class="text-lg font-bold text-slate-900 mb-1.5">Sending Response...</h3>
    <p class="text-xs text-slate-500 leading-relaxed mb-5">
      Please wait while your email is being delivered to <span class="font-semibold text-slate-800" id="loadingRecipientEmail"><?php echo replyClean($sender_email); ?></span>. Do not refresh or close this window.
    </p>

    <!-- Animated Progress bar -->
    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden relative mb-4">
      <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full w-2/3" style="animation: indeterminateProgress 1.6s infinite ease-in-out;"></div>
    </div>

    <div class="flex items-center justify-center gap-2 text-[11px] font-medium text-slate-400">
      <svg class="w-3.5 h-3.5 animate-spin text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span>Connecting to mail server & delivering...</span>
    </div>

  </div>
</div>

<!-- Email Sent Confirmation Modal -->
<?php if (!empty($_SESSION['success_message'])): ?>
<div id="emailSentModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
    
    <div class="p-6 text-center">
      <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <h2 class="text-lg font-bold text-slate-900 mb-1">
        Email Sent
      </h2>

      <p class="text-sm text-slate-500">
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
      </p>
    </div>

    <div class="px-6 pb-6">
      <button 
        type="button"
        onclick="closeEmailSentModal()"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-all active:scale-95">
        Okay
      </button>
    </div>

  </div>
</div>

<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<!-- error modal -->
<?php if (!empty($_SESSION['error_message'])): ?>
<div id="emailErrorModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
  <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
    
    <div class="p-6 text-center">
      <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>

      <h2 class="text-lg font-bold text-slate-900 mb-1">
        Email Not Sent
      </h2>

      <p class="text-sm text-slate-500">
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
      </p>
    </div>

    <div class="px-6 pb-6">
      <button 
        type="button"
        onclick="closeEmailErrorModal()"
        class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-all active:scale-95">
        Okay
      </button>
    </div>

  </div>
</div>

<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<script>
function copyEmailReply() {
  const body = document.getElementById("emailBody");

  body.select();
  body.setSelectionRange(0, 99999);

  document.execCommand("copy");
  alert("Reply copied.");
}

function closeEmailSentModal() {
  const modal = document.getElementById("emailSentModal");
  if (modal) {
    modal.remove();
  }
}
function closeEmailErrorModal() {
  const modal = document.getElementById("emailErrorModal");
  if (modal) {
    modal.remove();
  }
}

function handleReplySubmit(e) {
  const form = document.getElementById('replyForm');
  if (!form) return;

  if (!form.checkValidity()) {
    return;
  }

  const emailBody = document.getElementById('emailBody');
  if (emailBody && !emailBody.value.trim()) {
    e.preventDefault();
    alert('Please enter an email message before sending.');
    emailBody.focus();
    return;
  }

  const toInput = document.getElementById('replyToEmail');
  const loadingEmail = document.getElementById('loadingRecipientEmail');
  if (toInput && loadingEmail && toInput.value.trim()) {
    loadingEmail.textContent = toInput.value.trim();
  }

  const btn = document.getElementById('sendReplyBtn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `
      <svg class="animate-spin h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span>Sending...</span>
    `;
  }

  const overlay = document.getElementById('sendingLoadingOverlay');
  if (overlay) {
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
  }
}

window.addEventListener('pageshow', function(event) {
  const overlay = document.getElementById('sendingLoadingOverlay');
  if (overlay) {
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
  }
  const btn = document.getElementById('sendReplyBtn');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = `
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
      </svg>
      <span>Send Reply</span>
    `;
  }
});
</script>
</body>
</html>