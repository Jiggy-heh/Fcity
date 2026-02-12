(function () {
	'use strict';

	function qs(selector, context) {
		return (context || document).querySelector(selector);
	}

	function qsa(selector, context) {
		return Array.prototype.slice.call((context || document).querySelectorAll(selector));
	}

	var galleryGrid = qs('[data-flx-gallery]');
	if (!galleryGrid) return;

	var galleryItems = qsa('.gallery__item', galleryGrid);
	if (!galleryItems.length) return;

	var mobilePrevButton = qs('.gallery__nav-btn--prev');
	var mobileNextButton = qs('.gallery__nav-btn--next');

	var lightbox = document.createElement('div');
	lightbox.className = 'flx-lightbox';
	lightbox.innerHTML = ''
		+ '<div class="flx-lightbox__backdrop" data-flx-close></div>'
		+ '<div class="flx-lightbox__panel">'
		+ 	'<img class="flx-lightbox__img" alt="" />'
		+ '</div>'
		+ '<button type="button" class="flx-lightbox__close" aria-label="Zamknij" data-flx-close>×</button>'
		+ '<button type="button" class="flx-lightbox__arrow flx-lightbox__arrow--prev" aria-label="Poprzednie">‹</button>'
		+ '<button type="button" class="flx-lightbox__arrow flx-lightbox__arrow--next" aria-label="Następne">›</button>';

	document.body.appendChild(lightbox);

	var lightboxImage = qs('.flx-lightbox__img', lightbox);
	var lightboxPrevButton = qs('.flx-lightbox__arrow--prev', lightbox);
	var lightboxNextButton = qs('.flx-lightbox__arrow--next', lightbox);
	var currentIndex = 0;

	function openAt(index) {
		currentIndex = index;
		var itemHref = galleryItems[currentIndex].getAttribute('href');
		lightboxImage.src = itemHref;
		lightbox.classList.add('is-open');
		document.documentElement.style.overflow = 'hidden';
	}

	function closeLightbox() {
		lightbox.classList.remove('is-open');
		lightboxImage.src = '';
		document.documentElement.style.overflow = '';
	}

	function goPrev() {
		currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
		openAt(currentIndex);
	}

	function goNext() {
		currentIndex = (currentIndex + 1) % galleryItems.length;
		openAt(currentIndex);
	}

	galleryItems.forEach(function (item, index) {
		item.addEventListener('click', function (event) {
			event.preventDefault();
			openAt(index);
		});
	});

	qsa('[data-flx-close]', lightbox).forEach(function (closeElement) {
		closeElement.addEventListener('click', function () {
			closeLightbox();
		});
	});

	lightboxPrevButton.addEventListener('click', function (event) {
		event.preventDefault();
		goPrev();
	});

	lightboxNextButton.addEventListener('click', function (event) {
		event.preventDefault();
		goNext();
	});

	document.addEventListener('keydown', function (event) {
		if (!lightbox.classList.contains('is-open')) return;

		if (event.key === 'Escape') closeLightbox();
		if (event.key === 'ArrowLeft') goPrev();
		if (event.key === 'ArrowRight') goNext();
	});

	var touchStartX = 0;
	lightbox.addEventListener('touchstart', function (event) {
		if (!lightbox.classList.contains('is-open')) return;
		touchStartX = event.touches[0].clientX;
	}, { passive: true });

	lightbox.addEventListener('touchend', function (event) {
		if (!lightbox.classList.contains('is-open')) return;

		var touchEndX = event.changedTouches[0].clientX;
		var deltaX = touchEndX - touchStartX;

		if (Math.abs(deltaX) <= 45) return;
		if (deltaX > 0) goPrev();
		else goNext();
	}, { passive: true });

	function scrollByOne(direction) {
		var firstItem = galleryItems[0];
		if (!firstItem) return;

		var gridStyles = window.getComputedStyle(galleryGrid);
		var gap = parseFloat(gridStyles.gap || '0') || 0;
		var itemWidth = firstItem.getBoundingClientRect().width + gap;
		galleryGrid.scrollBy({ left: direction * itemWidth, behavior: 'smooth' });
	}

	if (mobilePrevButton) {
		mobilePrevButton.addEventListener('click', function (event) {
			event.preventDefault();

			var isMobileCarousel = window.getComputedStyle(galleryGrid).display === 'flex';
			if (isMobileCarousel) {
				scrollByOne(-1);
				return;
			}

			openAt(Math.max(0, currentIndex - 1));
		});
	}

	if (mobileNextButton) {
		mobileNextButton.addEventListener('click', function (event) {
			event.preventDefault();

			var isMobileCarousel = window.getComputedStyle(galleryGrid).display === 'flex';
			if (isMobileCarousel) {
				scrollByOne(1);
				return;
			}

			openAt(Math.min(galleryItems.length - 1, currentIndex + 1));
		});
	}
})();
