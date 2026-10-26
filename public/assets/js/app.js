/* BenBooks – script giao diện (phong cách SmartBen): toast, gợi ý tìm kiếm, slider hero, đếm ngược,
   thêm giỏ AJAX, chatbot, thông báo realtime (polling), hiệu ứng hiện khi cuộn. */
(function () {
    'use strict';
    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrf = meta ? meta.getAttribute('content') : '';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function fmt(s) { // **đậm** + đường link
        return esc(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1">xem chi tiết</a>');
    }

    /* ---------- toast (sb-toast của SmartBen) ---------- */
    function toastBox() {
        var box = document.querySelector('.sb-toasts');
        if (!box) { box = document.createElement('div'); box.className = 'sb-toasts'; document.body.appendChild(box); }
        return box;
    }
    function toast(html, loi, timeout) {
        var t = document.createElement('div');
        t.className = 'sb-toast' + (loi ? ' loi' : '');
        t.innerHTML = '<span class="sb-toast-icon"><i class="fas ' + (loi ? 'fa-exclamation' : 'fa-check') + '"></i></span>' +
            '<div class="sb-toast-body">' + html + '</div><button class="sb-toast-close" type="button" aria-label="Đóng">&times;</button>';
        toastBox().appendChild(t);
        var close = function () { t.classList.add('an'); setTimeout(function () { t.remove(); }, 250); };
        t.querySelector('.sb-toast-close').addEventListener('click', close);
        setTimeout(close, timeout || 4000);
    }
    window.MS = {esc: esc, toast: toast, csrf: csrf};

    // flash message từ server
    document.querySelectorAll('[data-toast]').forEach(function (el) { toast(esc(el.dataset.toast), el.dataset.loi === '1', 5000); });
    document.querySelectorAll('.sb-alert .x').forEach(function (b) { b.addEventListener('click', function () { b.parentNode.remove(); }); });

    /* ---------- gợi ý tìm kiếm ---------- */
    document.querySelectorAll('[data-suggest]').forEach(function (input) {
        var box = input.parentNode.querySelector('.ms-suggest'), timer;
        if (!box) return;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) { box.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch(input.dataset.suggest + '?q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}})
                    .then(function (r) { return r.json(); })
                    .then(function (list) {
                        if (!list.length) { box.style.display = 'none'; return; }
                        box.innerHTML = list.map(function (b) {
                            return '<a href="' + b.url + '"><img src="' + b.cover + '" alt=""><span><b>' + esc(b.title) + '</b><small>' +
                                esc(b.author) + ' · <span class="sb-price">' + esc(b.price) + '</span></small></span></a>';
                        }).join('');
                        box.style.display = 'block';
                    });
            }, 250);
        });
        document.addEventListener('click', function (e) { if (!input.parentNode.contains(e.target)) box.style.display = 'none'; });
    });

    /* ---------- slider sân khấu hero ---------- */
    document.querySelectorAll('[data-slider]').forEach(function (stage) {
        var slides = stage.querySelectorAll('.sb-slide'), dots = stage.querySelector('.sb-dots'), card = stage.parentNode.querySelector('.sb-stage-card'),
            badge = stage.parentNode.querySelector('.sb-stage-badge'), i = 0;
        if (!slides.length) return;
        slides.forEach(function (s, k) {
            var b = document.createElement('button');
            b.type = 'button'; b.setAttribute('aria-label', 'Sách ' + (k + 1));
            b.addEventListener('click', function () { show(k); });
            dots.appendChild(b);
        });
        function show(k) {
            slides[i].classList.remove('active'); dots.children[i].classList.remove('active');
            i = (k + slides.length) % slides.length;
            slides[i].classList.add('active'); dots.children[i].classList.add('active');
            var d = slides[i].dataset;
            if (card) { card.href = d.url; card.querySelector('b').textContent = d.title; card.querySelector('.sb-price').textContent = d.price; }
            if (badge) { badge.hidden = !d.pct || d.pct === '0'; badge.innerHTML = '<b>-' + d.pct + '%</b>giảm'; }
        }
        show(0);
        setInterval(function () { show(i + 1); }, 4500);
    });

    /* ---------- đếm ngược ---------- */
    function tick() {
        document.querySelectorAll('[data-countdown]').forEach(function (el) {
            var s = Math.max(0, Math.floor((new Date(el.dataset.countdown).getTime() - Date.now()) / 1000));
            var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), x = s % 60;
            var p = function (n) { return String(n).padStart(2, '0'); };
            el.innerHTML = (d ? '<span>' + d + 'n</span>' : '') + '<span>' + p(h) + '</span>:<span>' + p(m) + '</span>:<span>' + p(x) + '</span>';
        });
    }
    tick(); setInterval(tick, 1000);

    /* ---------- số lượng +/- ---------- */
    document.querySelectorAll('[data-qty]').forEach(function (box) {
        var input = box.querySelector('input');
        box.querySelectorAll('button').forEach(function (b) {
            b.addEventListener('click', function () {
                var v = parseInt(input.value || '1', 10) + parseInt(b.dataset.step, 10), max = parseInt(input.max || '99', 10);
                input.value = Math.max(parseInt(input.min || '1', 10), Math.min(max, v));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
        });
    });

    /* ---------- thêm giỏ / yêu thích bằng AJAX ---------- */
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.matches('[data-ajax]')) return;
        e.preventDefault();
        fetch(f.action, {method: 'POST', body: new FormData(f), headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.count !== undefined) document.querySelectorAll('[data-so-gio]').forEach(function (el) { el.textContent = res.count; });
                if (res.in !== undefined) {
                    var btn = f.querySelector('button');
                    btn.classList.toggle('on', res.in);
                }
                toast(esc(res.message) + (res.ok && f.dataset.ajax === 'cart' ? '<br><a class="sb-btn sb-btn-sm" href="' + (f.dataset.cartUrl || '#') + '">Xem giỏ hàng</a>' : ''), !res.ok);
            })
            .catch(function () { f.removeAttribute('data-ajax'); f.submit(); });
    });

    /* ---------- chatbot ---------- */
    var fab = document.getElementById('chatFab'), chat = document.getElementById('chatBox');
    if (fab && chat) {
        var body = chat.querySelector('.ms-chat-body'), form = chat.querySelector('form'), input = form.querySelector('input');
        var add = function (html, who) {
            var d = document.createElement('div');
            d.className = 'ms-msg ' + who; d.innerHTML = html;
            body.appendChild(d); body.scrollTop = body.scrollHeight;
            return d;
        };
        var send = function (text) {
            if (!text.trim()) return;
            add(esc(text), 'me');
            var wait = add('<i class="fas fa-circle-notch fa-spin"></i> Đang trả lời…', 'bot');
            fetch(chat.dataset.url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({message: text})})
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    var html = fmt(res.answer || '');
                    if (res.books && res.books.length) {
                        html += '<div class="ms-chat-books">' + res.books.map(function (b) {
                            return '<a href="' + b.url + '"><img src="' + b.cover + '" alt=""><span><b>' + esc(b.title) + '</b><small class="text-muted">' +
                                esc(b.author) + '</small><br><span class="sb-price">' + esc(b.price) + '</span></span></a>';
                        }).join('') + '</div>';
                    }
                    if (res.sources && res.sources.length) html += '<div class="mt-1"><small class="text-muted"><i class="fas fa-book"></i> Nguồn: ' + res.sources.map(esc).join(', ') + '</small></div>';
                    wait.innerHTML = html; body.scrollTop = body.scrollHeight;
                })
                .catch(function () { wait.innerHTML = 'Xin lỗi, kết nối bị lỗi. Bạn thử lại nhé!'; });
        };
        fab.addEventListener('click', function () { chat.classList.toggle('open'); if (chat.classList.contains('open')) input.focus(); });
        chat.querySelector('[data-close]').addEventListener('click', function () { chat.classList.remove('open'); });
        form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); input.value = ''; });
        chat.querySelectorAll('[data-q]').forEach(function (b) { b.addEventListener('click', function () { send(b.dataset.q); }); });
    }

    /* ---------- thông báo realtime (polling 10s) ---------- */
    var bell = document.getElementById('notifBell');
    if (bell) {
        var lastId = 0, first = true, badgeEl = bell.querySelector('.sb-badge-count');
        var poll = function () {
            fetch(bell.dataset.url + '?after_id=' + lastId, {headers: {'Accept': 'application/json'}})
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (res) {
                    if (!res) return;
                    badgeEl.textContent = res.unread;
                    badgeEl.style.display = res.unread > 0 ? '' : 'none';
                    if (!first) {
                        res.items.slice().reverse().forEach(function (n) {
                            toast('<a href="' + n.url + '">' + esc(n.title) + '</a><small>' + esc(n.content) + '</small>', false, 9000);
                        });
                    }
                    lastId = Math.max(lastId, res.last_id || 0);
                    first = false;
                }).catch(function () {});
        };
        poll(); setInterval(poll, 10000);
    }

    /* ---------- hiện dần khi cuộn ---------- */
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('reveal-ready');
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('show'); io.unobserve(en.target); } });
        }, {threshold: .08});
        document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
    }

    /* ---------- bộ lọc thu gọn trên điện thoại ---------- */
    document.querySelectorAll('.sb-filter-toggle').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('.sb-filter').classList.toggle('mo'); });
    });
})();
