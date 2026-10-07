<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - FAQ View
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
  <title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — FAQ') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease; }
    .faq-answer.open { max-height: 500px; }
    .faq-chevron { transition: transform 0.3s ease; }
    .faq-chevron.rotated { transform: rotate(180deg); }
    .tab-btn { transition: all 0.2s ease; }
    .tab-btn.active { border-bottom: 2px solid #18181b; color: #18181b; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
  </style>
</head>
<body class="bg-white text-zinc-900">

<!-- ── NAV ──────────────────────────────────────────────── -->
<?php include __DIR__ . '/../components/public_navbar.php'; ?>

<!-- ── PAGE HEADER ───────────────────────────────────────── -->
<section class="px-6 md:px-16 lg:px-24 xl:px-32 py-16 text-center border-b border-zinc-100">
  <h1 class="text-4xl md:text-5xl font-black uppercase tracking-wide text-zinc-900">Frequently Asked Questions</h1>
  <p class="text-zinc-500 mt-4 max-w-xl mx-auto">Find answers to common questions about reserving a unit, payment methods, and association dues.</p>
</section>

<!-- ── TABS + ACCORDIONS ─────────────────────────────────── -->
<section class="px-6 md:px-16 lg:px-24 xl:px-32 py-16 max-w-5xl mx-auto w-full">

  <!-- Category tabs with arrows -->
  <div class="flex items-center gap-3 border-b border-zinc-200 mb-10">
    <button type="button" onclick="slideFaqTabs(-1)" class="shrink-0 pb-3 text-zinc-400 hover:text-zinc-900 transition-colors">
      🠘
    </button>

    <div class="overflow-hidden flex-1">
      <div id="faqTabsTrack" class="flex gap-8 transition-transform duration-300">
        <button onclick="switchTab('reserving')" id="tab-reserving" class="tab-btn active pb-3 text-sm font-bold uppercase tracking-wide text-zinc-900 whitespace-nowrap border-b-2 border-zinc-900">Reserving a Unit</button>
        <button onclick="switchTab('payment')" id="tab-payment" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Payment</button>
        <button onclick="switchTab('attorney')" id="tab-attorney" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Power of Attorney</button>
        <button onclick="switchTab('association')" id="tab-association" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Association Due</button>
        <button onclick="switchTab('homeowner')" id="tab-homeowner" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Homeowner Rights & Responsibilities</button>
        <button onclick="switchTab('parking')" id="tab-parking" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Parking</button>
        <button onclick="switchTab('pets')" id="tab-pets" class="tab-btn pb-3 text-sm font-bold uppercase tracking-wide text-zinc-400 hover:text-zinc-700 whitespace-nowrap border-b-2 border-transparent">Pets</button>
      </div>
    </div>

    <button type="button" onclick="slideFaqTabs(1)" class="shrink-0 pb-3 text-zinc-400 hover:text-zinc-900 transition-colors">
      🠚
    </button>
  </div>

  <!-- RESERVING -->
  <div id="panel-reserving" class="space-y-3">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        How do I reserve a unit at Zeppelin Suites?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed mb-4">A condominium unit may be reserved for thirty days by presenting a Reservation Fee or PHP 100,000 per unit, and the following documents:</p>
        <ul class="space-y-2 mb-4">
          <li class="flex items-center gap-2 text-sm text-zinc-600 ml-4"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>Photocopy of two valid IDs (i.e. passport, driver’s license)</li>
          <li class="flex items-center gap-2 text-sm text-zinc-600 ml-4"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>Tax Identification Number (TIN)</li>
          <li class="flex items-center gap-2 text-sm text-zinc-600 ml-4"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>Reservation Agreement signed by buyer</li>
        </ul>
        <p class="text-zinc-600 text-sm leading-relaxed mb-4">Please submit the fee and the specified documents within thirty days; otherwise your reservation may be cancelled and the fee may be forfeited. 
          In case you have problems acquiring the documents within the specified time or if you need assistance and clarification, please contact our Sales Department and we will be happy to help you.</p>
        <a class="text-sm text-amber-600 hover:text-amber-500 font-medium" href="mailto:sales@zeppelinsuites.com">sales@zeppelinsuites.com</a>
      </div>
    </div>

    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        What is a Reservation Agreement?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer bg-white px-5">
        <p class="text-zinc-600 text-sm leading-relaxed">A Reservation Agreement is a document, which formally expresses the interest of the buyer in purchasing a unit. Its main purpose is for the unit to be set-aside for the client for a certain period of time (in this case, 30 days).</p>
      </div>
    </div>

    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        Is the reservation fee refundable?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer bg-white px-5">
        <p class="text-zinc-600 text-sm leading-relaxed">As stipulated in the Reservation Agreement, the Reservation Fee is non-refundable.</p>
      </div>
    </div>
  </div>

  <!-- PAYMENT -->
  <div id="panel-payment" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        What kinds of payment are accepted?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed">The down payment, which is minimum of 35% of the contract price, can be paid via GCash QR, cash, personal check, or manager’s check.</p>
      </div>
    </div>

    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        What is my payment schedule? What kinds of Financing are available to me?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer bg-white px-5">
        <p class="text-zinc-600 text-sm leading-relaxed mb-4">As specified in the buyer’s Reservation Agreement and the Contract to Sell, the full or partial minimum down payment of thirty-five percent (35%) should be paid no later than thirty days from the date of reservation. We provide In-House Financing to cater and complete your full payment schedule.</p>
        <p class="text-zinc-600 text-sm leading-relaxed">Upon full payment of the unit, Zeppelin Suites will commence the transfer of ownership of the unit to the buyer.</p>
      </div>
    </div>

    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        Can I refund my down payment?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer bg-white px-5">
        <p class="text-zinc-600 text-sm leading-relaxed">As a general rule, should the buyer decide to back-out of the transaction, the down payment will be forfeited in favor of Zeppelin Suites and is therefore non-refundable.</p>
      </div>
    </div>
  </div>

  <!-- POWER OF ATTORNEY -->
  <div id="panel-attorney" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        If I am unable to personally transact because I am abroad, what should I do?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed">A duly notarized Special Power of Attorney (SPA) is needed in appointing a person to represent you. For individuals outside the Philippines, an SPA notarized at the Philippine Embassy or Consular office is legally recognized.</p>
      </div>
    </div>

    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        Can a foreigner purchase a Zeppelin Suites unit?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer bg-white px-5">
        <p class="text-zinc-600 text-sm leading-relaxed mb-4">Yes. Foreign nationals can legally own condominium units in the Philippines under Republic Act No. 4726 (The Condominium Act) up to the 40% foreign ownership limit.</p>
        <p class="text-zinc-600 text-sm leading-relaxed">Required documents include valid passport, ACR (Alien Certificate of Registration), and Tax Identification Number (TIN).</p>
      </div>
    </div>
  </div>

  <!-- ASSOCIATION DUE -->
  <div id="panel-association" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        What expenses are included in the Association Dues?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed mb-4">Association Dues are billed monthly to maintain common areas, 24/7 security, elevator maintenance, swimming pool and landscaping upkeep, garbage disposal, and building insurance.</p>
      </div>
    </div>
  </div>

  <!-- HOMEOWNER RIGHTS & RESPONSIBILITIES -->
  <div id="panel-homeowner" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        What are my responsibilities as a unit owner?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed">Unit owners are responsible for paying monthly association dues on time, real property taxes on their unit, abiding by community guidelines, and coordinating unit upkeep with property management.</p>
      </div>
    </div>
  </div>

  <!-- PARKING -->
  <div id="panel-parking" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        How do I get my own parking slot?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed">Parking slots in our underground facility are assigned on a first-come, first-served basis. Inquire with our management team for current availability.</p>
      </div>
    </div>
  </div>

  <!-- PETS -->
  <div id="panel-pets" class="space-y-3 hidden">
    <div class="border border-zinc-200 rounded-xl overflow-hidden">
      <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-5 py-4 bg-zinc-900 text-white text-left font-semibold text-sm">
        Can I keep pets in my unit?
        <svg class="faq-chevron w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="faq-answer open bg-white px-5 py-4">
        <p class="text-zinc-600 text-sm leading-relaxed">Please consult with the Property Management Office prior to moving in with pets. Guidelines on pet size, registration, and leashing apply across all shared premises.</p>
      </div>
    </div>
  </div>

  <!-- CTA -->
  <div class="mt-16 text-center border-t border-zinc-100 pt-12">
    <p class="text-zinc-500 mb-4 text-sm">Didn't find what you're looking for?</p>
    <a href="<?= htmlspecialchars($baseUrl) ?>/contact" class="btn-primary inline-block bg-zinc-900 text-white px-8 py-3.5 rounded-full text-sm font-semibold hover:bg-zinc-700 active:scale-95 transition-all">Contact Us</a>
  </div>
</section>

<!-- ── PREMIUM FOOTER ────────────────────────────────────────────── -->
<?php include __DIR__ . '/../components/public_footer.php'; ?>

<script>
function toggleFaq(btn) {
  const answer = btn.nextElementSibling;
  const chevron = btn.querySelector('.faq-chevron');
  const isOpen = answer.classList.contains('open');
  answer.classList.toggle('open', !isOpen);
  if (!isOpen) { answer.style.padding = '1rem 1.25rem'; } else { answer.style.padding = '0 1.25rem'; }
  chevron.classList.toggle('rotated', !isOpen);
}

const faqPanels = ['reserving', 'payment', 'attorney', 'association', 'homeowner', 'parking', 'pets'];
let faqTabIndex = 0;

function switchTab(tab) {
  faqPanels.forEach(panel => {
    const el = document.getElementById('panel-' + panel);
    if (el) el.classList.add('hidden');
    const btn = document.getElementById('tab-' + panel);
    if (btn) {
      btn.classList.remove('active', 'border-zinc-900', 'text-zinc-900');
      btn.classList.add('text-zinc-400', 'border-transparent');
    }
  });

  const activePanel = document.getElementById('panel-' + tab);
  if (activePanel) activePanel.classList.remove('hidden');

  const activeBtn = document.getElementById('tab-' + tab);
  if (activeBtn) {
    activeBtn.classList.add('active', 'border-zinc-900', 'text-zinc-900');
    activeBtn.classList.remove('text-zinc-400', 'border-transparent');
  }

  faqTabIndex = faqPanels.indexOf(tab);
}

function slideFaqTabs(direction) {
  const track = document.getElementById('faqTabsTrack');
  if (!track) return;
  const viewport = track.parentElement;
  const tabs = track.querySelectorAll('button');

  faqTabIndex += direction;
  if (faqTabIndex < 0) faqTabIndex = 0;
  if (faqTabIndex > tabs.length - 1) faqTabIndex = tabs.length - 1;

  const selectedTab = tabs[faqTabIndex];
  const maxMove = track.scrollWidth - viewport.clientWidth;
  const moveAmount = Math.min(selectedTab.offsetLeft, maxMove);
  track.style.transform = `translateX(-${moveAmount}px)`;
}
</script>
</body>
</html>
