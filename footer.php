    </div>
</main>
<?php track_public_visitor($conn); ?>
<div class="pb-footer-divider" aria-hidden="true"></div>
<footer class="pb-site-footer">
    <div class="mx-auto max-w-[1400px] px-2 sm:px-3 lg:px-4">
        <?php
        $footerSections = get_active_sections($conn, 'footer');
        render_footer_sections($conn, $settings, $navKategoris ?? [], $footerSections);
        ?>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>

<div id="portalToast" style="position:fixed;bottom:5.5rem;right:2rem;z-index:1000;display:none;max-width:min(22rem,calc(100vw - 4rem));background:#0f172a;color:#fff;border-radius:.9rem;padding:.8rem 1rem;font-size:.85rem;font-weight:600;box-shadow:0 18px 40px rgba(15,23,42,.35);"></div>
<script>
(function() {
    try { document.documentElement.classList.add('js-anim'); } catch (e) {}
    var io = null;
    function show(el) {
        if (!el || el.classList.contains('animate-in')) return;
        // Restart paksa agar transisi selalu terpancing, lalu kunci final.
        el.classList.remove('animate-in');
        try { void el.offsetWidth; } catch (e) {}
        el.classList.add('animate-in');
    }
    function hide(el) {
        if (!el || !el.classList.contains('animate-in')) return;
        el.classList.remove('animate-in');
    }
    function observe(el) {
        if (!('IntersectionObserver' in window)) { show(el); return; }
        if (!io) {
            io = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) show(e.target);
                    else hide(e.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
        }
        try { io.observe(el); } catch (x) { show(el); }
    }
    function initAnimate() {
        var els = document.querySelectorAll('[data-animate]');
        if (!els.length) return;
        els.forEach(function(el) { observe(el); });
        // Paksa cek awal: elemen di viewport ikut reveal walau observer belum callback.
        setTimeout(function() {
            els.forEach(function(el) {
                try {
                    var r = el.getBoundingClientRect();
                    if (r.top < (window.innerHeight || 0) && r.bottom > 0) show(el);
                } catch (e) { show(el); }
            });
        }, 400);
    }
    window.initAnimate = initAnimate;
    function boot() {
        if ('requestAnimationFrame' in window) {
            requestAnimationFrame(function() { requestAnimationFrame(function() { setTimeout(initAnimate, 60); }); });
        } else {
            setTimeout(initAnimate, 60);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    window.addEventListener('load', initAnimate);
    window.portalToast = function(msg) {
        var t = document.getElementById('portalToast');
        if (!t) return;
        t.textContent = msg;
        t.style.display = 'block';
        clearTimeout(t._timer);
        t._timer = setTimeout(function() { t.style.display = 'none'; }, 3200);
    };
    // Back to top functionality
    var backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });
        
        // Smooth scroll to top
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Live clock functionality
    var clockElement = document.getElementById('liveClock');
    if (clockElement) {
        function updateClock() {
            var now = new Date();
            var hours = now.getHours().toString().padStart(2, '0');
            var minutes = now.getMinutes().toString().padStart(2, '0');
            var seconds = now.getSeconds().toString().padStart(2, '0');
            clockElement.textContent = hours + ':' + minutes + ':' + seconds + ' WIB';
        }
        
        // Update immediately
        updateClock();
        
        // Update every second
        setInterval(updateClock, 1000);
    }
})();
</script>

</body>
</html>
