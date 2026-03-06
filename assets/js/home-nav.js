document.addEventListener('DOMContentLoaded', () => {
	const nav = document.querySelector('.home-nav');
	if (!nav) return;

	const toggle = nav.querySelector('.home-nav__toggle');
	const links = nav.querySelectorAll('.home-nav__links a');

	const onScroll = () => {
		if (window.scrollY > 30) nav.classList.add('is-sticky');
		else nav.classList.remove('is-sticky');
	};

	if (toggle) {
		toggle.addEventListener('click', () => {
			const expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			nav.classList.toggle('is-open', !expanded);
		});
	}

	links.forEach((link) => {
		link.addEventListener('click', () => {
			nav.classList.remove('is-open');
			if (toggle) toggle.setAttribute('aria-expanded', 'false');
		});
	});

	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });
});
