document.addEventListener('DOMContentLoaded', () => {
	const nav = document.querySelector('.lp-nav');
	if (!nav) return;

	const threshold = 30;

	const onScroll = () => {
		if (window.scrollY > threshold) nav.classList.add('is-sticky');
		else nav.classList.remove('is-sticky');
	};

	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });
});
