(function () {
	const statusLabel = {
		available: 'Dostępny',
		reserved: 'Rezerwacja',
		sold: 'Sprzedany',
	};

	const formatPrice = (value, currency) => {
		const amount = Number(value);
		if (!Number.isFinite(amount) || amount <= 0) return '—';
		const fmt = new Intl.NumberFormat('pl-PL').format(amount);
		return currency ? `${fmt} ${currency}` : fmt;
	};

	const formatArea = (value) => {
		const amount = Number(value);
		if (!Number.isFinite(amount) || amount <= 0) return '—';
		return `${String(amount).replace('.', ',')} m²`;
	};


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
		const fieldRooms = root.querySelector('[data-house-rooms]');
		const fieldStatus = root.querySelector('[data-house-status]');
		const linkPlan = root.querySelector('[data-house-plan]');
		const linkDims = root.querySelector('[data-house-dims]');
		const modelImg = root.querySelector('[data-house-model]');

		if (!stage || !overlay) {
			return;
		}

		const endpoint = root.dataset.housesEndpoint || '';
		let housesData = [];

		const fetchHouses = async () => {
			if (!endpoint) return;
			try {
				const response = await fetch(endpoint, { credentials: 'same-origin' });
				if (!response.ok) return;
				const payload = await response.json();
				if (payload && payload.success && Array.isArray(payload.data)) {
					housesData = payload.data;
				}
			} catch (e) {
				// noop
			}
		};

		const getShapes = () => {
			return Array.from(root.querySelectorAll('.houses__shape'));
		};

		let shapes = getShapes();

		if (!shapes.length) {
			const tries = Number(root.dataset.housesTries || '0');
			if (tries < 30) {
				root.dataset.housesTries = String(tries + 1);
				setTimeout(() => initHouses(root), 100);
			}
			return;
		}
		const bindShapes = () => {
			shapes = getShapes();

			shapes.forEach((shape) => {
				if (shape.dataset.bound === '1') return;
				shape.dataset.bound = '1';

				const houseNumber = shape.dataset.house;
				const data = housesData.find((item) => String(item.number) === String(houseNumber));
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

					getShapes().forEach((item) => item.classList.remove('is-active'));
					shape.classList.add('is-active');


					fieldPrice.textContent = formatPrice(data.price, data.currency);
					fieldArea.textContent = formatArea(data.area);
					if (fieldStatus) {
						fieldStatus.textContent = statusLabel[data.status] || '—';
						fieldStatus.classList.remove('is-available', 'is-sold', 'is-reserved');
						fieldStatus.classList.add(`is-${data.status}`);
					}
					
					if (fieldRooms) fieldRooms.textContent = (data.rooms || data.rooms === 0) ? String(data.rooms) : '—';
					if (fieldPlot) fieldPlot.textContent = formatArea(data.plot);
					if (linkPlan && data.plan_url) {
						linkPlan.href = data.plan_url;
						linkPlan.target = '_blank';
						linkPlan.rel = 'noopener';
					}
					if (linkDims && data.dims_url) {
						linkDims.href = data.dims_url;
						linkDims.target = '_blank';
						linkDims.rel = 'noopener';
					}
					if (modelImg) {
						if (data.model_img) {
							modelImg.src = data.model_img;
							modelImg.removeAttribute('hidden');
						} else {
							modelImg.removeAttribute('src');
							modelImg.setAttribute('hidden', 'hidden');
						}
					}

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

		fetchHouses().then(() => {
			bindShapes();
			setTimeout(bindShapes, 150);
		});
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



	const boot = () => {
		document.querySelectorAll('.houses').forEach(initHouses);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// Fallback: jeśli sekcja .houses jest dogrywana po czasie (cache/Elementor/animacje)
	const observer = new MutationObserver(() => {
		const found = document.querySelector('.houses');
		if (found) {
			boot();
			observer.disconnect();
		}
	});

	observer.observe(document.documentElement, { childList: true, subtree: true });
})();
