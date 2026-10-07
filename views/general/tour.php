<?php
declare(strict_types=1);

/**
 * Zeppelin Suites - Virtual 360 Tour View
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
  <title><?= htmlspecialchars($pageTitle ?? 'Zeppelin Suites — Virtual Tour') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.7/build/pannellum.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * { font-family: "Geist", sans-serif; }
    .btn-primary { transition: all 0.15s ease; }
    .btn-primary:active { transform: scale(0.95); }
    .tour-select {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2318181b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 16px center;
      background-size: 16px;
    }
    .tour-select:focus {
      outline: none;
      border-color: #18181b;
      box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.07);
    }
    .thumbnail { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
    .thumbnail:hover { transform: scale(1.03); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
    .thumbnail.active { border-color: #18181b; box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.15); }
    .gallery-container { scrollbar-width: thin; scrollbar-color: #71717a #e4e4e7; }
    .gallery-container::-webkit-scrollbar { height: 6px; }
    .gallery-container::-webkit-scrollbar-thumb { background-color: #71717a; border-radius: 20px; }
    #panorama { width: 100%; height: 100%; display: none; }
  </style>
</head>
<body class="bg-white text-zinc-900">

  <!-- ── NAV ──────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_navbar.php'; ?>

  <section class="bg-zinc-950 text-white py-16 px-6 text-center">
    <p class="text-xs tracking-[0.25em] uppercase text-zinc-400 mb-3 font-medium">Immersive Experience</p>
    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">Explore Zeppelin Suites</h1>
  </section>

  <section class="px-6 md:pb-8 lg:px-24 xl:px-32 py-14">
    <div class="max-w-5xl mx-auto">

      <!-- Unit Selector -->
      <div class="mb-8 text-center">
        <p class="text-xs tracking-widest uppercase text-zinc-400 font-semibold mb-3">Select a unit to view</p>
        <div class="relative max-w-sm mx-auto">
          <select id="tourSelect" onchange="changeTour(this.value)"
            class="tour-select w-full border-2 border-zinc-900 rounded-xl px-5 py-4 text-base font-bold text-zinc-900 bg-white cursor-pointer pr-12">
            <option value="">— Choose a Unit Type —</option>
            <option value="amenities">Amenities</option>
            <option value="studio-a">Studio Type A</option>
            <option value="studio-b">Studio Type B</option>
            <option value="one-bedroom">One Bedroom</option>
            <option value="two-bedroom">Two Bedroom</option>
          </select>
        </div>
      </div>

      <!-- Main Panorama Viewer -->
      <div id="tourArea" class="tour-frame bg-zinc-100 rounded-3xl overflow-hidden border-2 border-zinc-200 relative" style="height: 520px;">
        <div id="tourContainer" class="flex flex-col items-center justify-center h-full text-center px-6 transition-opacity duration-300">
          <div id="statusIcon" class="w-16 h-16 bg-zinc-200 rounded-2xl flex items-center justify-center mb-4 transition-all">
            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.262a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
            </svg>
          </div>
          <div id="tourText">
            <p class="text-zinc-500 font-semibold mb-1">No unit selected</p>
            <p class="text-zinc-400 text-sm">Choose a unit type from the dropdown to start.</p>
          </div>
          <button id="startTourBtn" onclick="launchVR()"
            class="hidden mt-6 bg-zinc-900 text-white px-8 py-3 rounded-full font-bold text-sm flex items-center gap-2 hover:bg-zinc-800 shadow-xl btn-primary">
            Start 360° Walkthrough
          </button>
        </div>

        <div id="panorama"></div>
      </div>

      <!-- Dynamic Thumbnail Gallery -->
      <div id="gallerySection" class="mt-8 hidden">
        <p class="text-xs tracking-widest uppercase text-zinc-400 font-semibold mb-4 px-1">Explore Different Rooms</p>
        <div id="galleryContainer" class="gallery-container flex gap-4 overflow-x-auto pb-6 snap-x snap-mandatory scrollbar-thin">
        </div>
      </div>

      <div class="mt-12 text-center">
        <a href="<?= htmlspecialchars($baseUrl) ?>/contact"
          class="bg-zinc-900 text-white px-8 py-4 rounded-full font-semibold text-sm hover:bg-zinc-700 transition-all btn-primary inline-block">
          Book a Physical Viewing
        </a>
      </div>

    </div>
  </section>

  <!-- ── PREMIUM FOOTER ────────────────────────────────────────────── -->
  <?php include __DIR__ . '/../components/public_footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.7/build/pannellum.js"></script>

  <script>
    const baseUrl = <?= json_encode($baseUrl) ?>;

    const tourData = {
      "amenities": [
        { title: "Lobby", image: baseUrl + "/vrTourImages/lobby.png" },
        { title: "Pool", image: baseUrl + "/vrTourImages/pool.png" },
        { title: "Parking Lot", image: baseUrl + "/vrTourImages/parkingLot.png" }
      ],
      "studio-a": [
        { title: "Whole Room", image: baseUrl + "/vrTourImages/studioTypeA.png" }
      ],
      "studio-b": [
        { title: "Whole Room", image: baseUrl + "/vrTourImages/studioTypeA.png" }
      ],
      "one-bedroom": [
        { title: "Bedroom", image: baseUrl + "/vrTourImages/oneBedroomBedroom_1.png" },
        { title: "Living Room", image: baseUrl + "/vrTourImages/oneBedroomLivingRoom_3.png" }
      ],
      "two-bedroom": [
        { title: "Bedroom 1", image: baseUrl + "/vrTourImages/twoBedroomBedroom1.png" },
        { title: "Bedroom 2", image: baseUrl + "/vrTourImages/twoBedroomBedroom2.png" },
        { title: "Living Room", image: baseUrl + "/vrTourImages/twoBedroomLivingRoom.png" }
      ]
    };

    let viewer = null;
    let currentImagePath = "";
    let currentUnit = "";

    function createThumbnail(item, index) {
      const div = document.createElement('div');
      div.className = `thumbnail flex-shrink-0 w-52 rounded-2xl overflow-hidden border-2 border-transparent cursor-pointer snap-start relative group`;
      div.innerHTML = `
      <div class="aspect-video bg-zinc-200 relative">
        <img src="${item.image}" 
             alt="${item.title}" 
             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
             onerror="this.src='${baseUrl}/images/placeholder.jpg'; this.alt='Image not found'">
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent h-16"></div>
        <div class="absolute bottom-3 left-3 text-white text-xs font-medium tracking-wide">${item.title}</div>
      </div>
    `;
      div.onclick = () => switchToImage(item.image, div);
      return div;
    }

    function renderGallery(unit) {
      const container = document.getElementById('galleryContainer');
      container.innerHTML = '';
      if (!tourData[unit]) return;

      tourData[unit].forEach((item, index) => {
        const thumb = createThumbnail(item, index);
        container.appendChild(thumb);
      });

      document.getElementById('gallerySection').classList.remove('hidden');
    }

    function switchToImage(imagePath, thumbnailElement) {
      currentImagePath = imagePath;
      if (viewer) {
        viewer.destroy();
        viewer = null;
      }
      launchVR();
      document.querySelectorAll('.thumbnail').forEach(el => {
        el.classList.remove('active');
      });
      thumbnailElement.classList.add('active');
    }

    function changeTour(unitValue) {
      if (!unitValue) {
        location.reload();
        return;
      }

      currentUnit = unitValue;
      if (viewer) {
        viewer.destroy();
        viewer = null;
      }

      document.getElementById('panorama').style.display = 'none';
      document.getElementById('tourContainer').style.display = 'flex';

      const unitList = tourData[unitValue];
      if (unitList && unitList.length > 0) {
        currentImagePath = unitList[0].image;
        document.getElementById('tourText').innerHTML = `
          <p class="text-zinc-900 font-bold text-lg mb-1">${unitValue.toUpperCase().replace('-', ' ')}</p>
          <p class="text-zinc-500 text-sm">Ready to begin your 360° tour.</p>
        `;
        document.getElementById('startTourBtn').classList.remove('hidden');
        renderGallery(unitValue);
        const firstThumb = document.querySelector('.thumbnail');
        if (firstThumb) firstThumb.classList.add('active');
      } else {
        document.getElementById('tourText').innerHTML = `
          <p class="text-zinc-900 font-bold mb-1">Coming Soon</p>
          <p class="text-zinc-500 text-sm">A 360° tour is not available for this unit yet.</p>
        `;
        document.getElementById('startTourBtn').classList.add('hidden');
        document.getElementById('gallerySection').classList.add('hidden');
      }
    }

    function launchVR() {
      if (!currentImagePath) return;

      document.getElementById('tourContainer').style.display = 'none';
      const panoElement = document.getElementById('panorama');
      panoElement.style.display = 'block';

      viewer = pannellum.viewer('panorama', {
        type: 'equirectangular',
        panorama: currentImagePath,
        autoLoad: true,
        autoRotate: -2,
        compass: false,
        showZoomCtrl: true,
        showFullscreenCtrl: true,
        mouseZoom: true
      });
    }
  </script>
</body>
</html>
