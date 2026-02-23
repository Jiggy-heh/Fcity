(function () {
	const mobileQuery = window.matchMedia('(max-width: 991px)');

	const scrollByColumn = (track, direction) => {
		const firstCol = track.querySelector('.finish-standard__col');
		if (!firstCol) {
			return;
		}

		const colWidth = firstCol.getBoundingClientRect().width;
		track.scrollBy({
			left: direction * colWidth,
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
				scrollByColumn(track, dir);
			});
		});
	};

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.finish-standard').forEach(initSection);
	});
})();
