(function () {
	const statusLabel = {
		available: 'Dostępny',
		reserved: 'Rezerwacja',
		sold: 'Sprzedany',
	};

	const housesMock = [
		{ number: '1', status: 'available', price: 'od 500 000 zł', area: '120 m²', plot: '500 m²', plan_url: '#plan-1', dims_url: '#dims-1', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '2', status: 'reserved', price: 'od 530 000 zł', area: '118 m²', plot: '470 m²', plan_url: '#plan-2', dims_url: '#dims-2', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '3', status: 'sold', price: 'sprzedany', area: '116 m²', plot: '455 m²', plan_url: '#plan-3', dims_url: '#dims-3', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '4', status: 'available', price: 'od 560 000 zł', area: '126 m²', plot: '535 m²', plan_url: '#plan-4', dims_url: '#dims-4', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '5', status: 'reserved', price: 'od 545 000 zł', area: '122 m²', plot: '505 m²', plan_url: '#plan-5', dims_url: '#dims-5', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '6', status: 'available', price: 'od 590 000 zł', area: '132 m²', plot: '610 m²', plan_url: '#plan-6', dims_url: '#dims-6', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '7', status: 'available', price: 'od 570 000 zł', area: '124 m²', plot: '520 m²', plan_url: '#plan-7', dims_url: '#dims-7', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
		{ number: '8', status: 'reserved', price: 'od 540 000 zł', area: '119 m²', plot: '485 m²', plan_url: '#plan-8', dims_url: '#dims-8', model_img: 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/model_3d_przyklad.png' },
	];


	function setStep(root, step) {
		root.classList.remove('is-step-1', 'is-step-2', 'is-step-3');
		root.classList.add(`is-step-${step}`);
	}

	function initHouses(root) {
		const stage = root.querySelector('[data-houses-stage]');
		const overlay = root.querySelector('[data-houses-overlay]');
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

		if (!stage || !overlay) {
			return;
		}

		const getVisibleShapes = () => {
			return Array.from(root.querySelectorAll('.houses__shape')).filter((el) => {
				const svg = el.closest('svg');
				return svg && svg.offsetParent !== null;
			});
		};

		let shapes = getVisibleShapes();

		if (!shapes.length) {
			return;
		}

		const bindShapes = () => {
			shapes = getVisibleShapes();

			shapes.forEach((shape) => {
				if (shape.dataset.bound === '1') return;
				shape.dataset.bound = '1';

				const houseNumber = shape.dataset.house;
				const data = housesMock.find((item) => item.number === houseNumber);
				if (!data) return;

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

				shape.addEventListener('click', (event) => {
					event.preventDefault();

					getVisibleShapes().forEach((item) => item.classList.remove('is-active'));
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
		};

		bindShapes();

		const activateStepTwo = () => {
			if (root.classList.contains('is-step-1')) {
				setStep(root, 2);
			}
		};

		overlay.addEventListener('click', (event) => {
			event.preventDefault();
			activateStepTwo();
		});

		stage.addEventListener('click', (event) => {
			if (event.target === stage || event.target.classList.contains('houses__frame') || event.target.classList.contains('houses__img')) {
				activateStepTwo();
			}
		});

		// =========================================================
		// DEV: polygon drawer (włączane tylko gdy jest panel w DOM)
		// =========================================================
		const devWrap = root.querySelector('[data-houses-dev]');
		if (devWrap) {
			const btnToggle = devWrap.querySelector('[data-houses-dev-toggle]');
			const inputHouse = devWrap.querySelector('[data-houses-dev-house]');
			const selectMap = devWrap.querySelector('[data-houses-dev-map]');
			const out = devWrap.querySelector('[data-houses-dev-output]');
			const btnCopy = devWrap.querySelector('[data-houses-dev-copy]');
			const btnClear = devWrap.querySelector('[data-houses-dev-clear]');

			let devOn = false;
			let points = [];

			const getActiveSvg = () => {
				const wanted = selectMap ? selectMap.value : 'auto';
				const svgDesktop = root.querySelector('.houses__map--desktop');
				const svgMobile = root.querySelector('.houses__map--mobile');

				if (wanted === 'desktop' && svgDesktop) return svgDesktop;
				if (wanted === 'mobile' && svgMobile) return svgMobile;

				if (svgDesktop && svgDesktop.offsetParent !== null) return svgDesktop;
				if (svgMobile && svgMobile.offsetParent !== null) return svgMobile;

				return svgDesktop || svgMobile;
			};

			const getTargetPoly = () => {
				const svg = getActiveSvg();
				const num = (inputHouse && inputHouse.value) ? String(inputHouse.value).trim() : '1';
				if (!svg) return null;
				return svg.querySelector(`.houses__shape[data-house="${CSS.escape(num)}"]`);
			};

			const refreshOutput = () => {
				const str = points.map((p) => `${p.x},${p.y}`).join(' ');
				if (out) out.value = str;
			};

			const clearDev = () => {
				points = [];
				refreshOutput();
				const poly = getTargetPoly();
				if (poly) {
					poly.setAttribute('points', '');
					poly.classList.remove('is-dev-drawing');
				}
			};

			const toggleDev = () => {
				devOn = !devOn;
				btnToggle.setAttribute('aria-pressed', devOn ? 'true' : 'false');
				btnToggle.textContent = devOn ? 'Tryb łapania koordynatów: ON' : 'Tryb łapania koordynatów: OFF';

				if (devOn && root.classList.contains('is-step-1')) {
					setStep(root, 2);
				}

				const poly = getTargetPoly();
				if (poly) poly.classList.toggle('is-dev-drawing', devOn);
			};

			const getSvgPoint = (evt) => {
				const svg = getActiveSvg();
				if (!svg) return null;

				const pt = svg.createSVGPoint();
				pt.x = evt.clientX;
				pt.y = evt.clientY;

				const screenCTM = svg.getScreenCTM();
				if (!screenCTM) return null;

				const loc = pt.matrixTransform(screenCTM.inverse());
				return {
					x: Math.round(loc.x),
					y: Math.round(loc.y),
				};
			};

			const addPoint = (evt) => {
				if (!devOn) return;

				const svg = getActiveSvg();
				const poly = getTargetPoly();
				if (!svg || !poly) return;

				const p = getSvgPoint(evt);
				if (!p) return;

				points.push(p);

				const str = points.map((item) => `${item.x},${item.y}`).join(' ');
				poly.setAttribute('points', str);
				poly.classList.add('is-dev-drawing');

				refreshOutput();
			};

			btnToggle.addEventListener('click', toggleDev);

			if (btnCopy) {
				btnCopy.addEventListener('click', async () => {
					if (!out) return;
					try {
						await navigator.clipboard.writeText(out.value);
					} catch (e) {
						out.select();
						document.execCommand('copy');
					}
				});
			}

			if (btnClear) {
				btnClear.addEventListener('click', clearDev);
			}

			if (inputHouse) inputHouse.addEventListener('change', clearDev);
			if (selectMap) selectMap.addEventListener('change', clearDev);

			root.addEventListener('pointerdown', (evt) => {
				if (!devOn) return;
				const svg = getActiveSvg();
				if (!svg) return;
				if (!svg.contains(evt.target)) return;
				addPoint(evt);
			});


			window.addEventListener('keydown', (evt) => {
				if (!devOn) return;
				if (evt.key === 'Enter') {
					evt.preventDefault();
					refreshOutput();
				}
				if (evt.key === 'Escape') {
					evt.preventDefault();
					clearDev();
				}
			});
		}

		window.addEventListener('resize', () => {
			bindShapes();
		});
	}



	document.querySelectorAll('.houses').forEach(initHouses);
})();
