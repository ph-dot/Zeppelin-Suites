<!-- Navigation Bar -->
<header class="w-full border-b border-slate-100 bg-white/95 backdrop-blur-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <a href="<?= htmlspecialchars($baseUrl) ?>/" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-bold text-lg tracking-wider transition-transform group-hover:scale-105">
                ZS
            </div>
            <div>
                <span class="text-xl font-bold tracking-tight text-slate-900 block leading-tight">Zeppelin Suites</span>
                <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Luxury Residences</span>
            </div>
        </a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Home
        </a>
    </div>
</header>

<!-- Main Section: Two-Column Login View -->
<main class="flex flex-1 w-full min-h-[calc(100vh-80px)]">
    <!-- Left Hero Image Banner -->
    <div class="hidden md:block w-1/2 min-h-full bg-cover bg-center bg-no-repeat relative" style="background-image: url('<?= htmlspecialchars($baseUrl) ?>/images/zeppelin-suites-slider-exterior-2.jpg');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent flex flex-col justify-end p-12 text-white">
            <span class="text-xs uppercase font-semibold tracking-widest text-emerald-400 mb-2">Welcome Home</span>
            <h2 class="text-3xl font-bold tracking-tight mb-2">Refined Living in the Heart of the City</h2>
            <p class="text-sm text-slate-200 max-w-md">Access your resident portal, manage reservations, and stay updated with Zeppelin Suites services.</p>
        </div>
    </div>

    <!-- Right Login Form Section -->
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-12 bg-white">
        <form action="<?= htmlspecialchars($actionUrl) ?>" method="POST" class="w-full max-w-sm flex flex-col items-center justify-center">
            <h1 class="text-3xl sm:text-4xl text-slate-900 font-semibold tracking-tight">Sign in</h1>
            <p class="text-sm text-slate-500 mt-2 text-center">Welcome back! Please enter your details to continue</p>

            <!-- Email Input -->
            <div class="flex items-center mt-8 w-full bg-white border border-slate-300 h-12 rounded-full overflow-hidden px-5 gap-3 transition-all focus-within:border-slate-900 focus-within:ring-2 focus-within:ring-slate-900/10">
                <svg width="16" height="11" viewBox="0 0 16 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0 text-slate-400">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0 .55.571 0H15.43l.57.55v9.9l-.571.55H.57L0 10.45zm1.143 1.138V9.9h13.714V1.69l-6.503 4.8h-.697zM13.749 1.1H2.25L8 5.356z" fill="currentColor"/>
                </svg>
                <input type="email" name="email" placeholder="Email address" class="bg-transparent text-slate-800 placeholder-slate-400 outline-none text-sm w-full h-full" required autofocus>
            </div>

            <!-- Password input with eye toggle -->
            <div class="flex items-center mt-4 w-full bg-white border border-slate-300 h-12 rounded-full overflow-hidden pl-5 pr-4 gap-3 transition-all focus-within:border-slate-900 focus-within:ring-2 focus-within:ring-slate-900/10">
                <svg width="13" height="17" viewBox="0 0 13 17" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0 text-slate-400">
                    <path d="M13 8.5c0-.938-.729-1.7-1.625-1.7h-.812V4.25C10.563 1.907 8.74 0 6.5 0S2.438 1.907 2.438 4.25V6.8h-.813C.729 6.8 0 7.562 0 8.5v6.8c0 .938.729 1.7 1.625 1.7h9.75c.896 0 1.625-.762 1.625-1.7zM4.063 4.25c0-1.406 1.093-2.55 2.437-2.55s2.438 1.144 2.438 2.55V6.8H4.061z" fill="currentColor"/>
                </svg>
                <input id="passwordInput" type="password" name="password" placeholder="Password" class="bg-transparent text-slate-800 placeholder-slate-400 outline-none text-sm w-full h-full" required>
                <button type="button" onclick="togglePasswordVisibility()" class="cursor-pointer text-slate-400 hover:text-slate-800 transition-colors shrink-0 p-1 flex items-center justify-center focus:outline-none" aria-label="Toggle password visibility">
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

            <!-- Options Row -->
            <div class="w-full flex items-center justify-between mt-6 text-slate-500">
                <div class="flex items-center gap-2">
                    <input class="h-4 w-4 rounded accent-slate-900 cursor-pointer" type="checkbox" id="rememberCheckbox" name="remember">
                    <label class="text-sm cursor-pointer select-none" for="rememberCheckbox">Remember me</label>
                </div>
                <a class="text-sm text-slate-700 hover:text-slate-900 underline transition-colors" href="mailto:support@zeppelinsuites.com?subject=Password%20Reset%20Request">Forgot password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="mt-8 w-full h-12 rounded-full text-white bg-slate-900 hover:bg-slate-800 transition-all font-medium text-sm shadow-sm hover:shadow active:scale-[0.99] cursor-pointer">
                Sign in to Account
            </button>
        </form>
    </div>
</main>

<!-- Error Modal -->
<div id="errorModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 px-4 transition-opacity">
    <div class="bg-white p-6 sm:p-7 rounded-2xl w-full max-w-sm text-center shadow-xl border border-slate-100 animate-in fade-in zoom-in duration-200">
        <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 mx-auto flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h2 class="text-lg font-bold text-slate-900">Authentication Failed</h2>
        <p id="errorMessage" class="text-sm text-slate-600 mt-2 leading-relaxed"></p>

        <button onclick="closeModal()" class="mt-6 w-full bg-slate-900 hover:bg-slate-800 transition-colors text-white py-2.5 rounded-full text-sm font-medium cursor-pointer shadow-sm">
            Try Again
        </button>
    </div>
</div>

<script>
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

    <?php if (!empty($errorMessage)): ?>
        showError(<?= json_encode($errorMessage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
    <?php endif; ?>
</script>
