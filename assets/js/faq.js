document.addEventListener('DOMContentLoaded', function () {
	const faqRoots = document.querySelectorAll('[data-faq]');
	if (!faqRoots.length) return;

	function setCollapsedHeight(faqRoot) {
		const faqItems = faqRoot.querySelectorAll('.faq__item');
		if (!faqItems.length) return;

		const targetIndex = 5; // 6-te pytanie (0-based)
		const peekRatio = 0.35; // ile 6-tego ma wystawać

		if (faqItems.length <= targetIndex) {
			faqRoot.style.setProperty('--faq-collapsed-h', faqRoot.scrollHeight + 'px');
			return;
		}

		const item6 = faqItems[targetIndex];

		const listRect = faqRoot.getBoundingClientRect();
		const itemRect = item6.getBoundingClientRect();

		const itemHeight = itemRect.height || item6.offsetHeight || 0;
		const peek = Math.max(48, Math.min(90, Math.round(itemHeight * peekRatio)));

		const topInsideList = itemRect.top - listRect.top;
		const collapsed = Math.round(topInsideList + peek);

		faqRoot.style.setProperty('--faq-collapsed-h', collapsed + 'px');
	}

	faqRoots.forEach(function (faqRoot) {
		const moreButton = faqRoot.parentElement ? faqRoot.parentElement.querySelector('.faq__more') : null;
		const faqItems = faqRoot.querySelectorAll('.faq__item');

		faqRoot.addEventListener('click', function (event) {
			const questionButton = event.target.closest('.faq__question');
			if (!questionButton) return;

			const faqItem = questionButton.closest('.faq__item');
			if (!faqItem) return;

			const isOpen = faqItem.classList.toggle('is-open');
			questionButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

			if (!faqRoot.classList.contains('is-expanded')) {
				requestAnimationFrame(function () {
					setCollapsedHeight(faqRoot);
				});
			}
		});

		if (!moreButton) return;

		if (faqItems.length <= 5) {
			moreButton.hidden = true;
			faqRoot.classList.add('is-expanded');
			faqRoot.style.removeProperty('--faq-collapsed-h');
			return;
		}

		moreButton.hidden = false;

		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				setCollapsedHeight(faqRoot);
			});
		});

		window.addEventListener('resize', function () {
			if (!faqRoot.classList.contains('is-expanded')) {
				setCollapsedHeight(faqRoot);
			}
		});

		moreButton.addEventListener('click', function () {
			const isExpanded = faqRoot.classList.toggle('is-expanded');
			moreButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
			moreButton.querySelector('.btn__text').textContent = isExpanded ? 'Zobacz mniej' : 'Zobacz więcej';

			if (!isExpanded) {
				setCollapsedHeight(faqRoot);
			} else {
				faqRoot.style.removeProperty('--faq-collapsed-h');
			}
		});
	});
});
