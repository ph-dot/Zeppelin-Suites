<?php 
require_once '../php_files/auth.php'; // Use your new auth file

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = normalizeRole($_SESSION['role'] ?? '');
    
    if ($role === 'admin') {
        header("Location: ../adminPages/homeAdmin.php");
    } elseif ($role === 'unit owner') {
        header("Location: ../unitOwnerPages/overview.php");
    } elseif ($role === 'tenant') {
        header("Location: ../tenantPages/homeTenant.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Restrict pinch-to-zoom on mobile and set initial scale -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="../output.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Zeppelin Suites - Login Page</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    * {
        font-family: "Geist", sans-serif;
    }
    /* Lock viewport height and prevent mobile pinch gestures via touch actions */
    html, body {
        touch-action: pan-x pan-y;
        overscroll-behavior: none;
    }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white overflow-x-hidden">
<!-- ── NAV ──────────────────────────────────────────────── -->
<nav class="sticky top-0 w-full bg-white/80 backdrop-blur-md px-6 md:px-16 lg:px-24 xl:px-32 py-4 flex items-center justify-between z-50 border-b border-zinc-200/50">
    <a href="../generalViewPages/index.html">
        <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites Logo" style="height:75px;">
    </a>
    <!-- Desktop Nav Items -->
    <div class="hidden md:flex items-center gap-8">
        <a href="../generalViewPages/index.html" class="text-sm text-zinc-500 hover:text-zinc-800">Home</a>
        <a href="../generalViewPages/tour.html" class="text-sm text-zinc-500 hover:text-zinc-800 transition-colors">Take a Tour</a>
        <div class="relative group">
            <button class="flex items-center gap-1.5 text-sm text-zinc-500 cursor-pointer bg-transparent border-0 py-2 hover:text-zinc-800">
                Browse Units
                <svg id="desktopChevron" class="transition-transform group-hover:rotate-180" width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="absolute top-full left-0 mt-1 w-44 bg-white border border-zinc-200 rounded-xl shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                <a href="../generalViewPages/studioTypeA.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Studio Type A</a>
                <a href="../generalViewPages/studioTypeB.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Studio Type B</a>
                <a href="../generalViewPages/oneBedroom.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">One Bedroom</a>
                <a href="../generalViewPages/twoBedroom.html" class="block px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">Two Bedroom</a>
            </div>
        </div>
        <a href="../generalViewPages/faq.html" class="text-sm text-zinc-500 hover:text-zinc-800">FAQ</a>
        <a href="../generalViewPages/aboutUs.html" class="text-sm text-zinc-500 hover:text-zinc-800">About Us</a>
        <a href="../generalViewPages/contact.php" class="text-sm text-zinc-500 hover:text-zinc-800">Contact</a>
        <a href="../generalViewPages/login.php" class="text-sm text-zinc-800 font-medium hover:text-zinc-800">Portal</a>
    </div>

    <!-- Mobile Menu Button -->
    <button onclick="toggleMenu()" class="md:hidden flex flex-col gap-1.5 cursor-pointer bg-transparent border-0 p-1 focus:outline-none" aria-label="Toggle Navigation Menu">
        <span id="bar1" class="block w-6 h-0.5 bg-zinc-800 transition-all origin-center"></span>
        <span id="bar2" class="block w-6 h-0.5 bg-zinc-800 transition-all"></span>
        <span id="bar3" class="block w-6 h-0.5 bg-zinc-800 transition-all origin-center"></span>
    </button>

    <!-- Mobile Dropdown Navigation -->
    <div id="mobileMenu" class="absolute top-full left-0 w-full bg-white border-t border-zinc-200 flex flex-col p-5 gap-1 md:hidden z-50 shadow-lg hidden">
        <a href="../generalViewPages/index.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">Home</a>
        <a href="../generalViewPages/tour.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">Take a Tour</a>
        
        <div>
            <button onclick="toggleDropdown('mobileDropdown','mobileChevron')" class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50 bg-transparent border-0 cursor-pointer">
                Browse Units
                <svg id="mobileChevron" class="transition-transform duration-200" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="m1 1 4 4 4-4" stroke="#71717b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div id="mobileDropdown" class="hidden flex-col pl-4 mt-1 space-y-1">
                <a href="../generalViewPages/studioTypeA.html" class="block px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Studio Type A</a>
                <a href="../generalViewPages/studioTypeB.html" class="block px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Studio Type B</a>
                <a href="../generalViewPages/oneBedroom.html" class="block px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">One Bedroom</a>
                <a href="../generalViewPages/twoBedroom.html" class="block px-4 py-2 rounded-lg text-sm text-zinc-500 hover:bg-zinc-50">Two Bedroom</a>
            </div>
        </div>

        <a href="../generalViewPages/faq.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">FAQ</a>
        <a href="../generalViewPages/aboutUs.html" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">About Us</a>
        <a href="../generalViewPages/contact.php" class="px-4 py-2.5 rounded-lg text-sm text-zinc-600 hover:bg-zinc-50">Contact</a>
        <a href="../generalViewPages/login.php" class="px-4 py-2.5 rounded-lg text-sm text-zinc-900 font-semibold hover:bg-zinc-50">Portal</a>
    </div>
</nav>

<!-- Main Section: fills the rest of screen and spans top to bottom edge -->
<main class="flex flex-1 w-full min-h-[calc(100vh-107px)]">
    <div class="hidden md:block w-1/2 min-h-full bg-cover bg-center bg-no-repeat" style="background-image: url('../images/zeppelin-suites-slider-exterior-2.jpg');"></div>

    <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-12">
        <form action="ActionsGV/loginAction.php" method="POST" class="w-full max-w-sm flex flex-col items-center justify-center">
            <h1 class="text-4xl text-gray-900 font-medium">Sign in</h1>
            <p class="text-sm text-gray-500 mt-3">Welcome back! Please sign in to continue</p>

            <div class="flex items-center mt-8 w-full bg-transparent border border-black h-12 rounded-full overflow-hidden px-5 gap-3 focus-within:ring-2 focus-within:ring-zinc-400">
                <svg width="16" height="11" viewBox="0 0 16 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0 .55.571 0H15.43l.57.55v9.9l-.571.55H.57L0 10.45zm1.143 1.138V9.9h13.714V1.69l-6.503 4.8h-.697zM13.749 1.1H2.25L8 5.356z" fill="#6B7280"/>
                </svg>
                <input type="email" name="email" placeholder="Email id" class="bg-transparent text-gray-700 placeholder-gray-500 outline-none text-sm w-full h-full" required>
            </div>

            <!-- Password input with eye toggle -->
            <div class="flex items-center mt-4 w-full bg-transparent border border-black h-12 rounded-full overflow-hidden pl-5 pr-4 gap-3 focus-within:ring-2 focus-within:ring-zinc-400">
                <svg width="13" height="17" viewBox="0 0 13 17" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                    <path d="M13 8.5c0-.938-.729-1.7-1.625-1.7h-.812V4.25C10.563 1.907 8.74 0 6.5 0S2.438 1.907 2.438 4.25V6.8h-.813C.729 6.8 0 7.562 0 8.5v6.8c0 .938.729 1.7 1.625 1.7h9.75c.896 0 1.625-.762 1.625-1.7zM4.063 4.25c0-1.406 1.093-2.55 2.437-2.55s2.438 1.144 2.438 2.55V6.8H4.061z" fill="#6B7280"/>
                </svg>
                <input id="passwordInput" type="password" name="password" placeholder="Password" class="bg-transparent text-gray-700 placeholder-gray-500 outline-none text-sm w-full h-full" required>
                <button type="button" onclick="togglePasswordVisibility()" class="cursor-pointer text-zinc-500 hover:text-zinc-800 transition-colors shrink-0 p-1 flex items-center justify-center focus:outline-none" aria-label="Toggle password visibility">
                    <!-- Eye Open Icon -->
                    <svg id="eyeOpenIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <!-- Eye Slash (Closed) Icon -->
                    <svg id="eyeClosedIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <div class="w-full flex items-center justify-between mt-6 text-gray-500">
                <div class="flex items-center gap-2">
                    <input class="h-4 w-4 rounded accent-black cursor-pointer" type="checkbox" id="checkbox" name="remember">
                    <label class="text-sm cursor-pointer select-none" for="checkbox">Remember me</label>
                </div>
                <a class="text-sm underline text-black hover:text-zinc-600" href="#">Forgot password?</a>
            </div>

            <button type="submit" class="mt-8 w-full h-11 rounded-full text-white bg-black hover:bg-zinc-800 transition-colors font-medium">
                Login
            </button>
        </form>
    </div>
</main>

<!-- Error Modal -->
<div id="errorModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
    <div class="bg-white p-6 rounded-2xl w-full max-w-xs text-center shadow-lg">
        <h2 class="text-lg font-semibold text-red-600">Login Failed</h2>
        <p id="errorMessage" class="text-sm text-gray-600 mt-2"></p>

        <button onclick="closeModal()" class="mt-5 w-full bg-black hover:bg-zinc-800 transition-colors text-white py-2 rounded-full text-sm font-medium">
            OK
        </button>
    </div>
</div>

<script>
    let menuOpen = false;

    function toggleMenu() {
        menuOpen = !menuOpen;
        const menu = document.getElementById('mobileMenu');
        const bar1 = document.getElementById('bar1');
        const bar2 = document.getElementById('bar2');
        const bar3 = document.getElementById('bar3');
        
        menu.classList.toggle('hidden', !menuOpen);
        
        bar1.style.transform = menuOpen ? 'translateY(8px) rotate(45deg)' : '';
        bar2.style.opacity = menuOpen ? '0' : '1';
        bar3.style.transform = menuOpen ? 'translateY(-8px) rotate(-45deg)' : '';
    }

    function toggleDropdown(id, chevronId) {
        const el = document.getElementById(id);
        const ch = document.getElementById(chevronId);
        const isHidden = el.classList.contains('hidden');
        
        el.classList.toggle('hidden', !isHidden);
        el.classList.toggle('flex', isHidden);
        ch.style.transform = isHidden ? 'rotate(180deg)' : '';
    }

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('passwordInput');
        const eyeOpenIcon = document.getElementById('eyeOpenIcon');
        const eyeClosedIcon = document.getElementById('eyeClosedIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpenIcon.classList.add('hidden');
            eyeClosedIcon.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeOpenIcon.classList.remove('hidden');
            eyeClosedIcon.classList.add('hidden');
        }
    }

    function showError(message) {
        document.getElementById("errorMessage").innerText = message;
        document.getElementById("errorModal").classList.remove("hidden");
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById("errorModal").classList.add("hidden");
        document.body.style.overflow = 'auto';
    }

    // Optional zoom prevention for desktop Ctrl/Cmd + Wheel & Shortcuts
    window.addEventListener('wheel', (e) => {
        if (e.ctrlKey) e.preventDefault();
    }, { passive: false });

    window.addEventListener('keydown', (e) => {
        if (e.ctrlKey && (e.key === '+' || e.key === '-' || e.key === '=')) {
            e.preventDefault();
        }
    });

<?php
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
    echo "showError(" . json_encode($message) . ");\n";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === "no_user") {
        echo "showError('No account found with this email.');\n";
    } elseif ($_GET['error'] === "wrong_password") {
        echo "showError('Incorrect password. Try again.');\n";
    }
}
?>
</script>
</body>
</html>