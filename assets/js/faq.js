document.addEventListener('DOMContentLoaded', function () {

	const faqContainers = document.querySelectorAll('[data-faq]');
	if (!faqContainers.length) return;

	faqContainers.forEach(function (root) {

		root.addEventListener('click', function (e) {

			const btn = e.target.closest('.faq__question');
			if (!btn) return;

			const item = btn.closest('.faq__item');
			if (!item) return;

			const isOpen = item.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

		});

	});

});
