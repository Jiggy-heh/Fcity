(function(){
	'use strict';

	function qs(sel, ctx){ return (ctx || document).querySelector(sel); }
	function qsa(sel, ctx){ return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	var grid = qs('[data-flx-gallery]');
	if(!grid) return;

	var items = qsa('.gallery__item', grid);
	if(!items.length) return;

	var navPrev = qs('.gallery__nav-btn--prev');
	var navNext = qs('.gallery__nav-btn--next');

	// ===== LIGHTBOX DOM (tworzymy raz)
	var lb = document.createElement('div');
	lb.className = 'flx-lightbox';
	lb.innerHTML = ''
		+ '<div class="flx-lightbox__backdrop" data-flx-close></div>'
		+ '<div class="flx-lightbox__panel">'
		+ 	'<img class="flx-lightbox__img" alt="" />'
		+ '</div>'
		+ '<button type="button" class="flx-lightbox__close" aria-label="Zamknij" data-flx-close>×</button>'
		+ '<button type="button" class="flx-lightbox__arrow flx-lightbox__arrow--prev" aria-label="Poprzednie">‹</button>'
		+ '<button type="button" class="flx-lightbox__arrow flx-lightbox__arrow--next" aria-label="Następne">›</button>';

	document.body.appendChild(lb);

	var lbImg = qs('.flx-lightbox__img', lb);
	var lbPrev = qs('.flx-lightbox__arrow--prev', lb);
	var lbNext = qs('.flx-lightbox__arrow--next', lb);

	var current = 0;

	function openAt(index){
		current = index;
		var href = items[current].getAttribute('href');
		lbImg.src = href;
		lb.classList.add('is-open');
		document.documentElement.style.overflow = 'hidden';
	}

	function close(){
		lb.classList.remove('is-open');
		lbImg.src = '';
		document.documentElement.style.overflow = '';
	}

	function prev(){
		current = (current - 1 + items.length) % items.length;
		openAt(current);
	}

	function next(){
		current = (current + 1) % items.length;
		openAt(current);
	}

	// ===== klik w miniaturę -> lightbox
	items.forEach(function(a, i){
		a.addEventListener('click', function(e){
			e.preventDefault();
			openAt(i);
		});
	});

	// ===== close
	qsa('[data-flx-close]', lb).forEach(function(el){
		el.addEventListener('click', function(){
			close();
		});
	});

	// ===== arrows in lightbox
	lbPrev.addEventListener('click', function(e){ e.preventDefault(); prev(); });
	lbNext.addEventListener('click', function(e){ e.preventDefault(); next(); });

	// ===== keyboard
	document.addEventListener('keydown', function(e){
		if(!lb.classList.contains('is-open')) return;

		if(e.key === 'Escape') close();
		if(e.key === 'ArrowLeft') prev();
		if(e.key === 'ArrowRight') next();
	});

	// ===== swipe (mobile)
	var touchX = 0;
	lb.addEventListener('touchstart', function(e){
		if(!lb.classList.contains('is-open')) return;
		touchX = e.touches[0].clientX;
	}, {passive:true});

	lb.addEventListener('touchend', function(e){
		if(!lb.classList.contains('is-open')) return;
		var endX = e.changedTouches[0].clientX;
		var dx = endX - touchX;

		if(Math.abs(dx) > 45){
			if(dx > 0) prev();
			else next();
		}
	}, {passive:true});

	// ===== NAV buttons for MOBILE carousel (scroll)
	function scrollByOne(dir){
		// szerokość kafla + gap
		var first = items[0];
		if(!first) return;

		var style = window.getComputedStyle(grid);
		var gap = parseFloat(style.gap || '0') || 0;

		var w = first.getBoundingClientRect().width + gap;
		grid.scrollBy({ left: dir * w, behavior: 'smooth' });
	}

	if(navPrev){
		navPrev.addEventListener('click', function(e){
			e.preventDefault();

			// jeśli grid jest flex (mobile) -> scroll. jeśli grid jest desktop -> otwórz lightbox na prev
			var isMobileCarousel = window.getComputedStyle(grid).display === 'flex';
			if(isMobileCarousel) scrollByOne(-1);
			else openAt(Math.max(0, current - 1));
		});
	}

	if(navNext){
		navNext.addEventListener('click', function(e){
			e.preventDefault();

			var isMobileCarousel = window.getComputedStyle(grid).display === 'flex';
			if(isMobileCarousel) scrollByOne(1);
			else openAt(Math.min(items.length - 1, current + 1));
		});
	}

})();
