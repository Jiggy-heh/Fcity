document.addEventListener('DOMContentLoaded', function () {
	const faqRoots = document.querySelectorAll('[data-faq]');
	if (!faqRoots.length) return;

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
		});

		if (!moreButton) return;

		if (faqItems.length <= 4) {
			moreButton.hidden = true;
			return;
		}

		moreButton.addEventListener('click', function () {
			const isExpanded = faqRoot.classList.toggle('is-expanded');
			moreButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
			moreButton.textContent = isExpanded ? 'Zobacz mniej' : 'Zobacz więcej';
		});
	});
});
