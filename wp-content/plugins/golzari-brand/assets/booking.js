// صفحه‌ی رزرو: خواندنِ زنده‌ی روزها و ساعت‌های آزاد، انتخاب و ثبتِ نوبت.
(function () {
	var C = window.GZBooking || {};
	var M = C.msg || {};
	var form = document.getElementById('gz-bookform');
	if (!form) return;
	var daysBox = document.getElementById('gz-days');
	var timesBox = document.getElementById('gz-times');
	var err = document.getElementById('gz-formerr');
	var steps = form.querySelectorAll('.gz-steps li');
	var opened = Date.now();
	var data = null;
	var pick = { date: '', time: '' };

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) e.className = cls;
		if (text != null) e.textContent = text;
		return e;
	}
	function showErr(m) {
		err.textContent = m || '';
		err.hidden = !m;
	}
	function step() {
		var n = 1;
		if (form.querySelector('input[name="service"]:checked')) n = 2;
		if (n === 2 && pick.date) n = 3;
		if (n === 3 && pick.time) n = 4;
		steps.forEach(function (s, i) {
			s.classList.toggle('is-on', i < n);
		});
	}

	function renderTimes() {
		timesBox.innerHTML = '';
		pick.time = '';
		var day = (data.days || []).filter(function (d) { return d.d === pick.date; })[0];
		if (!day) {
			timesBox.appendChild(el('p', 'gz-muted', '—'));
			return step();
		}
		var free = day.slots.filter(function (s) { return s.free; });
		if (!free.length) {
			timesBox.appendChild(el('p', 'gz-muted', M.b_nofree));
			return step();
		}
		day.slots.forEach(function (s) {
			var b = el('button', 'gz-slot', s.l);
			b.type = 'button';
			b.disabled = !s.free;
			b.addEventListener('click', function () {
				pick.time = s.t;
				timesBox.querySelectorAll('.gz-slot').forEach(function (x) { x.classList.toggle('is-on', x === b); });
				showErr('');
				step();
			});
			timesBox.appendChild(b);
		});
		step();
	}

	function renderDays() {
		daysBox.innerHTML = '';
		var first = '';
		(data.days || []).forEach(function (d) {
			var hasFree = d.open && d.slots.some(function (s) { return s.free; });
			var b = el('button', 'gz-day');
			b.type = 'button';
			b.appendChild(el('span', 'gz-day__w', d.w));
			b.appendChild(el('span', 'gz-day__d', d.l.replace(d.w, '').trim()));
			if (!d.open) b.appendChild(el('small', null, M.b_closed));
			b.disabled = !hasFree;
			b.dataset.d = d.d;
			b.addEventListener('click', function () {
				pick.date = d.d;
				daysBox.querySelectorAll('.gz-day').forEach(function (x) { x.classList.toggle('is-on', x === b); });
				showErr('');
				renderTimes();
			});
			daysBox.appendChild(b);
			if (hasFree && !first) first = b;
		});
		if (first) first.click();
	}

	function load() {
		fetch(C.api + (C.api.indexOf('?') > -1 ? '&' : '?') + '_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				data = j;
				renderDays();
			})
			.catch(function () {
				daysBox.innerHTML = '';
				daysBox.appendChild(el('p', 'gz-formerr', M.b_err_net));
			});
	}

	form.addEventListener('change', function (e) {
		if (e.target.name === 'service') {
			showErr('');
			step();
		}
	});

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var svc = form.querySelector('input[name="service"]:checked');
		if (!svc || !pick.date || !pick.time) return showErr(M.b_err_pick);
		var name = form.name.value.trim();
		if (name.length < 2) return showErr(M.b_err_name);
		var phone = form.phone.value.replace(/[۰-۹]/g, function (c) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c); }).replace(/\D/g, '');
		if (!/^(0?9\d{9}|989\d{9})$/.test(phone)) return showErr(M.b_err_phone);
		var btn = form.querySelector('.gz-bookform__submit');
		btn.disabled = true;
		showErr('');
		fetch(C.api, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				service: svc.value,
				date: pick.date,
				time: pick.time,
				name: name,
				phone: phone,
				note: form.note.value.trim(),
				website: form.website.value,
				elapsed: Math.round((Date.now() - opened) / 1000)
			})
		})
			.then(function (r) { return r.json(); })
			.then(function (j) {
				btn.disabled = false;
				if (!j || !j.ok) {
					showErr((j && j.message) || M.b_err_net);
					if (j && j.error === 'b_err_taken') load();
					return;
				}
				var dl = document.getElementById('gz-done-list');
				dl.innerHTML = '';
				[[M.b_step1, j.summary.service], [M.b_step2, j.summary.date], [M.b_step3, j.summary.time], [M.b_name, j.summary.name]].forEach(function (p) {
					var row = el('div', 'gz-done__row');
					row.appendChild(el('dt', null, p[0]));
					row.appendChild(el('dd', null, p[1]));
					dl.appendChild(row);
				});
				form.hidden = true;
				var done = document.getElementById('gz-done');
				done.hidden = false;
				done.scrollIntoView({ behavior: 'smooth', block: 'center' });
			})
			.catch(function () {
				btn.disabled = false;
				showErr(M.b_err_net);
			});
	});

	load();
})();
