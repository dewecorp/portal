            <div style="height:2.5rem;flex-shrink:0;" aria-hidden="true"></div>
            <footer class="admin-footer">
                <p class="small text-muted mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'Portal Berita'); ?> Admin Panel &mdash; Portal Berita Management System
                </p>
            </footer>
        </main>
    </div>
</div>
<script src="https://cdn.ckeditor.com/4.22.1/full-all/ckeditor.js"></script>
<script>
    (function () {
        try {
            if (window.CKEDITOR && CKEDITOR.addCss) {
                CKEDITOR.addCss('img.image-center{display:block;margin-left:auto;margin-right:auto;}img.image-left{float:left;margin:0 1rem 1rem 0;}img.image-right{float:right;margin:0 0 1rem 1rem;}figure.image{max-width:100%;}figure.image img{max-width:100%;height:auto;}figure.image.image-center{margin-left:auto;margin-right:auto;}figure.image.image-left{float:left;margin:0 1rem 1rem 0;}figure.image.image-right{float:right;margin:0 0 1rem 1rem;}figcaption{text-align:center;font-size:0.8rem;color:#64748b;padding:0.4rem 0;}');
            }
        } catch (e) {}
        var openButtons = document.querySelectorAll('[data-modal-open]');
        var closeButtons = document.querySelectorAll('[data-modal-close]');

        function ckeditorHeight(textarea) {
            var value = parseInt(textarea.getAttribute('data-editor-height') || '350', 10);
            if (isNaN(value) || value < 350) {
                return 350;
            }
            return value;
        }

        function ckeditorConfig(textarea) {
            return {
                toolbar: [
                    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                    { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl'] },
                    { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                    { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'Iframe'] },
                    { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize', 'TextColor', 'BGColor'] },
                    { name: 'tools', items: ['Maximize', 'ShowBlocks'] },
                    { name: 'document', items: ['Source'] }
                ],
                height: ckeditorHeight(textarea),
                versionCheck: false,
                removePlugins: 'image,elementspath',
                extraPlugins: 'image2',
                extraAllowedContent: true,
                image2_alignClasses: ['image-left', 'image-center', 'image-right'],
                image2_captionedClass: 'image-captioned',
                contentsCss: (function () {
                    var css = [];
                    try {
                        var links = document.querySelectorAll('link[rel="stylesheet"]');
                        for (var i = 0; i < links.length; i++) {
                            if (links[i].href) css.push(links[i].href);
                        }
                    } catch (e) {}
                    return css;
                })()
            };
        }

        function initCKEditor(textarea) {
            if (!window.CKEDITOR || textarea._ckInstance) {
                return;
            }
            var editor = CKEDITOR.replace(textarea, ckeditorConfig(textarea));
            textarea._ckInstance = editor;
            editor.on('instanceReady', function () {
                editor.resize('100%', ckeditorHeight(textarea), true);
                var form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (editor.status !== 'destroyed') {
                            editor.updateElement();
                        }
                    });
                }
            });
        }

        function destroyCKEditor(textarea) {
            if (textarea._ckInstance) {
                textarea._ckInstance.updateElement();
                textarea._ckInstance.destroy();
                textarea._ckInstance = null;
            }
        }

        function openModal(id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            modal.querySelectorAll('textarea.js-ckeditor').forEach(function (ta) {
                initCKEditor(ta);
                if (ta._ckInstance) {
                    ta._ckInstance.resize('100%', ckeditorHeight(ta), true);
                }
            });
        }

        function closeModal(modal) {
            if (!modal) return;
            modal.querySelectorAll('textarea.js-ckeditor').forEach(destroyCKEditor);
            modal.classList.remove('is-open');
            if (!document.querySelector('.admin-modal.is-open')) {
                document.body.style.overflow = '';
            }
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                openModal(button.getAttribute('data-modal-open'));
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.closest('.admin-modal'));
            });
        });

        document.querySelectorAll('.admin-modal').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal(modal);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.admin-modal.is-open').forEach(closeModal);
            }
        });

        // Init CKEditor for textareas NOT inside a modal (e.g. edit page)
        if (window.CKEDITOR) {
            document.querySelectorAll('textarea.js-ckeditor').forEach(function (textarea) {
                if (!textarea.closest('.admin-modal')) {
                    initCKEditor(textarea);
                }
            });
        }
    })();

    (function () {
        var menu = document.getElementById('adminUserMenu');
        var trigger = document.getElementById('adminUserTrigger');

        if (!menu || !trigger) {
            return;
        }

        function closeUserMenu() {
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = menu.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) {
                closeUserMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeUserMenu();
            }
        });
    })();

    // Konfirmasi logout sidebar
    (function () {
        var btn = document.getElementById('sidebarLogout');
        if (!btn) return;
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            Swal.fire({ title: 'Logout?', text: 'Anda akan keluar dari panel admin.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Logout', cancelButtonText: 'Batal' }).then(function (r) {
                if (r.isConfirmed) window.location.href = btn.getAttribute('href');
            });
        });
    })();
    
    // Live datetime functionality for admin navbar
    (function() {
        var dateTimeElement = document.getElementById('adminDateTime');
        if (!dateTimeElement) return;
        
        var hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        function updateDateTime() {
            var now = new Date();
            var namaHari = hari[now.getDay()];
            var tgl = now.getDate();
            var bln = bulan[now.getMonth()];
            var thn = now.getFullYear();
            var hours = now.getHours().toString().padStart(2, '0');
            var minutes = now.getMinutes().toString().padStart(2, '0');
            var seconds = now.getSeconds().toString().padStart(2, '0');
            
            dateTimeElement.textContent = namaHari + ', ' + tgl + ' ' + bln + ' ' + thn + ' ' + hours + ':' + minutes + ':' + seconds + ' WIB';
        }
        
        // Update immediately
        updateDateTime();
        
        // Update every second
        setInterval(updateDateTime, 1000);
    })();
</script>
</body>
</html>
