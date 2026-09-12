    </div>
</main>
<?php track_public_visitor($conn); ?>
<footer class="mt-auto border-t border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-purple-900">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
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
    // Scroll animations: sederhana + anti-macet.
    function reveal(el) {
        if (!el || el.classList.contains('animate-in')) return;
        el.classList.add('animate-in');
        el.setAttribute('data-anim-done', '1');
    }
    function initAnimate() {
        var els = document.querySelectorAll('[data-animate]:not([data-anim-done])');
        if (!els.length) return;
        if (!('IntersectionObserver' in window)) {
            els.forEach(function(el, i) { setTimeout(function() { reveal(el); }, i * 80); });
            return;
        }
        var seen = 0, total = els.length;
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (!e.isIntersecting) return;
                var t = e.target;
                try { io.unobserve(t); } catch (x) {}
                seen++;
                reveal(t);
            });
        }, { threshold: 0.05, rootMargin: '0px 0px 80px 0px' });
        els.forEach(function(el) { try { io.observe(el); } catch (x) { reveal(el); } });
        // Pengaman: bila observer macet total (tidak ada yang reveal),
        // tampilkan yang sedang terlihat — TANPA menandai done yang lain.
        setTimeout(function() {
            if (seen > 0) return;
            document.querySelectorAll('[data-animate]:not([data-anim-done])').forEach(function(el) {
                try {
                    var r = el.getBoundingClientRect();
                    if (r.top < window.innerHeight && r.bottom > 0) reveal(el);
                } catch (err) {}
            });
        }, 5000);
    }
    window.initAnimate = initAnimate;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnimate);
    } else {
        initAnimate();
    }
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
