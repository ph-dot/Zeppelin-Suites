<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Studio Type B View
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
  <title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Studio Type B') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
    .slider-wrap { overflow: hidden; position: relative; }
    .slider-track { display: flex; transition: transform 0.4s ease; }
    .slide { min-width: 100%; }
  </style>
</head>
<body class="bg-white text-zinc-900 flex flex-col min-h-screen">

  <!-- ── NAV ──────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_navbar.php'; ?>

  <!-- ── PAGE HERO ─────────────────────────────────────────── -->
  <section class="px-6 md:px-16 lg:px-24 xl:px-32 py-16 border-b border-zinc-100">
    <p class="text-xs tracking-widest uppercase text-zinc-400 mb-2">Explore Our</p>
    <h1 class="text-4xl md:text-5xl font-bold text-zinc-900">Studio Type Units</h1>
    <p class="text-zinc-500 mt-3 max-w-lg">Discover the perfect home for your lifestyle. Each unit is thoughtfully designed to deliver comfort, style, and convenience.</p>
  </section>

  <!-- ── UNIT DETAILS ────────────────────────────────────────── -->
  <main class="flex-grow px-6 md:px-16 lg:px-24 xl:px-32 py-16 space-y-24">
    <div id="studio-b" class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center scroll-mt-24">
      <div class="slider-wrap rounded-2xl overflow-hidden shadow-sm relative">
        <div class="slider-track" id="slider-sb">
          <div class="slide bg-zinc-100 aspect-video flex items-center justify-center">
            <img src="<?= htmlspecialchars($baseUrl) ?>/images/studio_b.jpg" alt="Studio B" class="w-full h-full object-cover"
              onerror="this.parentElement.innerHTML='<span class=\'text-zinc-400 font-bold text-xl\'>ROOM IMG</span>'">
          </div>
          <div class="slide bg-zinc-200 aspect-video flex items-center justify-center">
            <img src="<?= htmlspecialchars($baseUrl) ?>/images/studio_b_2.jpg" alt="Studio B Angle 2" class="w-full h-full object-cover"
              onerror="this.parentElement.innerHTML='<span class=\'text-zinc-400 font-bold text-xl\'>ROOM IMG</span>'">
          </div>
        </div>
        <div class="flex justify-between absolute top-1/2 -translate-y-1/2 w-full px-3 pointer-events-none">
          <button type="button" onclick="slide('slider-sb','sliderIdx-sb',-1)" class="pointer-events-auto bg-white/80 rounded-full w-9 h-9 flex items-center justify-center shadow text-zinc-700 font-bold hover:bg-white transition-colors">‹</button>
          <button type="button" onclick="slide('slider-sb','sliderIdx-sb',1)" class="pointer-events-auto bg-white/80 rounded-full w-9 h-9 flex items-center justify-center shadow text-zinc-700 font-bold hover:bg-white transition-colors">›</button>
        </div>
        <input type="hidden" id="sliderIdx-sb" value="0">
      </div>
      <div>
        <p class="text-xs tracking-widest uppercase text-zinc-400 mb-2">Studio</p>
        <h2 class="text-3xl font-bold text-zinc-900 mb-4">Studio Type B</h2>
        <p class="text-zinc-500 leading-relaxed mb-4">An upgraded take on compact luxury, offering an expansive feel with an extended floor plan. It features distinct zones for rest and daily activity, elevated by high-quality fixtures and sleek finishes.</p>
        <ul class="space-y-2 mb-6">
          <li class="flex items-center gap-2 text-sm text-zinc-600"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>Floor Area: 40.65 sqm</li>
          <li class="flex items-center gap-2 text-sm text-zinc-600"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>L-shaped zoning for semi-private sleeping quarters</li>
          <li class="flex items-center gap-2 text-sm text-zinc-600"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>Premium panoramic window layout for enhanced ambient light</li>
          <li class="flex items-center gap-2 text-sm text-zinc-600"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 inline-block"></span>For individuals desiring a more defined living area</li>
        </ul>
        <div class="flex flex-wrap gap-3">
          <a href="<?= htmlspecialchars($baseUrl) ?>/contact"
            class="btn-primary bg-zinc-900 text-white px-6 py-3 rounded-full text-sm font-semibold hover:bg-zinc-700 active:scale-95 transition-all">Inquire Now</a>
          <a href="<?= htmlspecialchars($baseUrl) ?>/tour"
            class="btn-primary border border-zinc-300 text-zinc-700 px-6 py-3 rounded-full text-sm font-semibold hover:bg-zinc-50 active:scale-95 transition-all">Virtual Tour →</a>
        </div>
      </div>
    </div>
  </main>

  <!-- ── FOOTER ────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_footer.php'; ?>

  <script>
    function slide(trackId, idxId, dir) {
      const track = document.getElementById(trackId);
      const idxInput = document.getElementById(idxId);
      let idx = parseInt(idxInput.value, 10);
      const total = track.children.length;
      idx = (idx + dir + total) % total;
      idxInput.value = idx;
      track.style.transform = `translateX(-${idx * 100}%)`;
    }
  </script>
</body>
</html>
