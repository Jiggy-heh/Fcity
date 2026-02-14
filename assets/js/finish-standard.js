(function () {
	const mobileQuery = window.matchMedia('(max-width: 991px)');

	const scrollByItem = (track, direction) => {
		const firstItem = track.querySelector('.finish-standard__item');
		if (!firstItem) {
			return;
		}

		const itemWidth = firstItem.getBoundingClientRect().width;
		track.scrollBy({
			left: direction * itemWidth,
			behavior: 'smooth',
		});
	};

	const initSection = (section) => {
		const track = section.querySelector('.finish-standard__checks');
		const buttons = section.querySelectorAll('.finish-standard__nav-btn');

		if (!track || !buttons.length) {
			return;
		}

		buttons.forEach((button) => {
			button.addEventListener('click', () => {
				if (!mobileQuery.matches) {
					return;
				}

				const dir = button.dataset.dir === 'prev' ? -1 : 1;
				scrollByItem(track, dir);
			});
		});
	};

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.finish-standard').forEach(initSection);
	});
})();
