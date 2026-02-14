(function () {
	const statusLabel = {
		available: 'Dostępny',
		reserved: 'Rezerwacja',
		sold: 'Sprzedany',
	};

	const housesMock = [
		{ number: '1', status: 'available', price: 'od 500 000 zł', area: '120 m²', plot: '500 m²', plan_url: '#plan-1', dims_url: '#dims-1', model_img: 'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&w=800&q=80' },
		{ number: '2', status: 'reserved', price: 'od 530 000 zł', area: '118 m²', plot: '470 m²', plan_url: '#plan-2', dims_url: '#dims-2', model_img: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80' },
		{ number: '3', status: 'sold', price: 'sprzedany', area: '116 m²', plot: '455 m²', plan_url: '#plan-3', dims_url: '#dims-3', model_img: 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=800&q=80' },
		{ number: '4', status: 'available', price: 'od 560 000 zł', area: '126 m²', plot: '535 m²', plan_url: '#plan-4', dims_url: '#dims-4', model_img: 'https://images.unsplash.com/photo-1464146072230-91cabc968266?auto=format&fit=crop&w=800&q=80' },
		{ number: '5', status: 'reserved', price: 'od 545 000 zł', area: '122 m²', plot: '505 m²', plan_url: '#plan-5', dims_url: '#dims-5', model_img: 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=800&q=80' },
		{ number: '6', status: 'available', price: 'od 590 000 zł', area: '132 m²', plot: '610 m²', plan_url: '#plan-6', dims_url: '#dims-6', model_img: 'https://images.unsplash.com/photo-1560184897-ae75f418493e?auto=format&fit=crop&w=800&q=80' },
	];

	function setStep(root, step) {
		root.classList.remove('is-step-1', 'is-step-2', 'is-step-3');
		root.classList.add(`is-step-${step}`);
	}

	function initHouses(root) {
		const stage = root.querySelector('[data-houses-stage]');
		const overlay = root.querySelector('[data-houses-overlay]');
		const shapes = root.querySelectorAll('.houses__shape');
		const tooltip = root.querySelector('[data-houses-tooltip]');
		const tooltipTitle = root.querySelector('.houses__tooltip-title');
		const tooltipStatus = root.querySelector('.houses__tooltip-status');
		const expanded = root.querySelector('[data-houses-expanded]');
		const fieldPrice = root.querySelector('[data-house-price]');
		const fieldArea = root.querySelector('[data-house-area]');
		const fieldPlot = root.querySelector('[data-house-plot]');
		const linkPlan = root.querySelector('[data-house-plan]');
		const linkDims = root.querySelector('[data-house-dims]');
		const modelImg = root.querySelector('[data-house-model]');

		if (!stage || !overlay || !shapes.length) {
			return;
		}

		shapes.forEach((shape) => {
			const houseNumber = shape.dataset.house;
			const data = housesMock.find((item) => item.number === houseNumber);
			if (!data) {
				return;
			}

			shape.classList.add(`is-${data.status}`);

			shape.addEventListener('mouseenter', () => {
				if (window.matchMedia('(hover: hover)').matches) {
					tooltipTitle.textContent = `Dom: ${data.number}`;
					tooltipStatus.textContent = `Status: ${statusLabel[data.status]}`;
					tooltip.hidden = false;
				}
			});

			shape.addEventListener('mousemove', (event) => {
				if (!tooltip.hidden) {
					tooltip.style.left = `${event.clientX + 14}px`;
					tooltip.style.top = `${event.clientY + 14}px`;
				}
			});

			shape.addEventListener('mouseleave', () => {
				tooltip.hidden = true;
			});

			shape.addEventListener('click', () => {
				shapes.forEach((item) => item.classList.remove('is-active'));
				shape.classList.add('is-active');

				fieldPrice.textContent = data.price;
				fieldArea.textContent = data.area;
				fieldPlot.textContent = data.plot;
				linkPlan.href = data.plan_url;
				linkDims.href = data.dims_url;
				modelImg.src = data.model_img;

				setStep(root, 3);
				expanded.classList.add('is-visible');
				expanded.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});

			shape.addEventListener('keydown', (event) => {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					shape.click();
				}
			});
		});

		const activateStepTwo = () => {
			if (root.classList.contains('is-step-1')) {
				setStep(root, 2);
			}
		};

		overlay.addEventListener('click', activateStepTwo);
		stage.addEventListener('click', (event) => {
			if (event.target === stage || event.target.classList.contains('houses__frame') || event.target.classList.contains('houses__img')) {
				activateStepTwo();
			}
		});
	}

	document.querySelectorAll('.houses').forEach(initHouses);
})();
