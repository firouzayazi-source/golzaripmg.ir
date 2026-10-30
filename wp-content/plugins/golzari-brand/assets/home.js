// صفحه‌ی اصلیِ اختصاصی: منوی گوشی، سایه‌ی سربرگ، دسته‌های نمونه‌کار، نمایشگرِ عکس و نمایانی هنگامِ اسکرول.
(function () {
	var header = document.getElementById('gz-header');
	var nav = document.getElementById('gz-nav');
	var burger = document.getElementById('gz-burger');

	function closeNav() {
		nav.classList.remove('is-open');
		burger.setAttribute('aria-expanded', 'false');
	}
	burger.addEventListener('click', function () {
		var open = !nav.classList.contains('is-open');
		nav.classList.toggle('is-open', open);
		burger.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
	nav.addEventListener('click', function (e) {
		if (e.target.closest('a')) closeNav();
	});

	function onScroll() {
		header.classList.toggle('is-scrolled', window.scrollY > 8);
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	// دسته‌های نمونه‌کار
	var tabs = document.querySelectorAll('.gz-tabs button');
	var items = document.querySelectorAll('.gz-grid .gz-grid__item');
	tabs.forEach(function (t) {
		t.addEventListener('click', function () {
			tabs.forEach(function (x) { x.classList.toggle('is-on', x === t); });
			var cat = t.getAttribute('data-tab');
			items.forEach(function (it) {
				it.classList.toggle('is-hidden', cat !== 'all' && it.getAttribute('data-cat') !== cat);
			});
		});
	});

	// نمایشگرِ عکس
	var box = document.getElementById('gz-lightbox');
	if (box && typeof box.showModal === 'function') {
		var img = box.querySelector('img');
		document.querySelectorAll('.gz-grid__item').forEach(function (a) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				img.src = a.getAttribute('href');
				img.alt = (a.querySelector('img') || {}).alt || '';
				box.showModal();
			});
		});
		box.addEventListener('click', function (e) {
			if (e.target === box || e.target.closest('.gz-lightbox__close')) box.close();
		});
	}

	// نمایانی هنگامِ اسکرول
	var els = document.querySelectorAll('.gz-section .gz-head, .gz-card, .gz-about__text, .gz-about__media, .gz-academy__text, .gz-collage, .gz-cta__in');
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					en.target.classList.add('is-in');
					io.unobserve(en.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px' });
		els.forEach(function (el) {
			el.classList.add('gz-reveal');
			io.observe(el);
		});
	}
})();
