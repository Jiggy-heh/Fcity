(function () {
	const statusLabel = {
		available: 'Dostępny',
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
		root.classList.remove('is-step-1', 'is-step-2', 'is-step-3', 'is-step-4');
		root.classList.add(`is-step-${step}`);
	}

	function initHouses(root) {
		if (root.dataset.housesInited === '1') {
			return;
		}
		root.dataset.housesInited = '1';

		const stage = root.querySelector('[data-houses-stage]');
		const overlay = root.querySelector('[data-houses-overlay]');
		const tooltip = root.querySelector('[data-houses-tooltip]');
		const tooltipTitle = root.querySelector('.houses__tooltip-title');
		const tooltipStatus = root.querySelector('.houses__tooltip-status');
		const chooseWrap = root.querySelector('[data-houses-choose]');
		const expanded = root.querySelector('[data-houses-expanded]');

		const fieldPrice = root.querySelector('[data-house-price]');
		const fieldArea = root.querySelector('[data-house-area]');
		const fieldPlot = root.querySelector('[data-house-plot]');
		const fieldRooms = root.querySelector('[data-house-rooms]');
		const fieldStatus = root.querySelector('[data-house-status]');
		const linkPlan = root.querySelector('[data-house-plan]');
		const modelImg = root.querySelector('[data-house-model]');

		if (!stage || !overlay || !chooseWrap || !expanded) {
			return;
		}

		const sideCards = Array.from(root.querySelectorAll('[data-house-side-card]'));
		const sideButtons = Array.from(root.querySelectorAll('[data-house-side-button]'));
		const sideAreas = {
			left: root.querySelector('[data-house-side-area="left"]'),
			right: root.querySelector('[data-house-side-area="right"]'),
		};
		const sideImages = {
			left: root.querySelector('[data-house-side-image="left"]'),
			right: root.querySelector('[data-house-side-image="right"]'),
		};

		const endpoint = root.dataset.housesEndpoint || '';
		let unitsData = [];
		let buildingsData = [];
		let activeBuilding = null;
		let activeUnit = null;
		let shapes = [];
		let cardsMap = {};

		try {
			cardsMap = JSON.parse(root.dataset.housesCards || '{}');
		} catch (e) {
			cardsMap = {};
		}

		const fetchHouses = async () => {
			if (!endpoint) return;
			try {
				const response = await fetch(endpoint, { credentials: 'same-origin' });
				if (!response.ok) return;
				const payload = await response.json();
				if (payload && payload.success) {
					if (Array.isArray(payload.data)) {
						unitsData = payload.data;
					}
					if (Array.isArray(payload.buildings)) {
						buildingsData = payload.buildings;
					}
				}
			} catch (e) {
				// noop
			}
		};

		const getShapes = () => Array.from(root.querySelectorAll('.houses__shape'));
		const getBuildingStatus = (buildingNumber) => {
			const fromPayload = buildingsData.find((item) => String(item.building_number) === String(buildingNumber));
			if (fromPayload) {
				return fromPayload.status === 'sold' ? 'sold' : 'available';
			}

			const units = unitsData.filter((item) => String(item.number) === String(buildingNumber));
			if (!units.length) {
				return null;
			}
			return units.some((item) => item.status === 'available') ? 'available' : 'sold';
		};

		const getBuildingUnits = (buildingNumber) => {
			const result = { left: null, right: null };
			unitsData.forEach((item) => {
				if (String(item.number) !== String(buildingNumber)) return;
				if (item.unit_side === 'left' || item.unit_side === 'right') {
					result[item.unit_side] = item;
				}
			});
			return result;
		};

		const setSideActive = (side) => {
			sideCards.forEach((card) => card.classList.toggle('is-active', card.dataset.houseSideCard === side));
			sideButtons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.houseSideButton === side));
		};

		const renderDetails = (unit) => {
			if (!unit) return;

			activeUnit = unit;
			if (fieldPrice) fieldPrice.textContent = formatPrice(unit.price, unit.currency);
			if (fieldArea) fieldArea.textContent = formatArea(unit.area);
			if (fieldRooms) fieldRooms.textContent = (unit.rooms || unit.rooms === 0) ? String(unit.rooms) : '—';
			if (fieldPlot) fieldPlot.textContent = formatArea(unit.plot);
			if (fieldStatus) {
				const status = unit.status === 'sold' ? 'sold' : 'available';
				fieldStatus.textContent = statusLabel[status] || '—';
				fieldStatus.classList.remove('is-available', 'is-sold');
				fieldStatus.classList.add(`is-${status}`);
			}

			if (linkPlan) {
				if (unit.plan_url) {
					linkPlan.href = unit.plan_url;
					linkPlan.target = '_blank';
					linkPlan.rel = 'noopener';
					linkPlan.hidden = false;
				} else {
					linkPlan.removeAttribute('href');
					linkPlan.hidden = true;
				}
			}

			if (modelImg) {
				if (unit.model_img) {
					modelImg.src = unit.model_img;
					modelImg.removeAttribute('hidden');
				} else {
					modelImg.removeAttribute('src');
					modelImg.setAttribute('hidden', 'hidden');
				}
			}

			setStep(root, 4);
			expanded.classList.add('is-visible');
			expanded.scrollIntoView({ behavior: 'smooth', block: 'start' });
		};

		const renderChooseSide = (buildingNumber) => {
			activeBuilding = String(buildingNumber);
			activeUnit = null;
			setStep(root, 3);
			expanded.classList.remove('is-visible');
			setSideActive('');

			const units = getBuildingUnits(activeBuilding);
			['left', 'right'].forEach((side) => {
				const unit = units[side];
				if (sideAreas[side]) {
					sideAreas[side].textContent = `Powierzchnia: ${formatArea(unit ? unit.area : 0)}`;
				}
				const imageUrl = cardsMap[activeBuilding] && cardsMap[activeBuilding][side] ? cardsMap[activeBuilding][side] : '';
				if (sideImages[side]) {
					if (imageUrl) {
						sideImages[side].src = imageUrl;
						sideImages[side].removeAttribute('hidden');
					} else {
						sideImages[side].removeAttribute('src');
						sideImages[side].setAttribute('hidden', 'hidden');
					}
				}
			});

			const availableSides = ['left', 'right'].filter((side) => !!units[side]);
			if (availableSides.length === 1) {
				setSideActive(availableSides[0]);
				renderDetails(units[availableSides[0]]);
				return;
			}

			chooseWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
		};

		const bindShapes = () => {
			shapes = getShapes();
			shapes.forEach((shape) => {
				const buildingNumber = shape.dataset.house;
				const status = getBuildingStatus(buildingNumber);

				shape.classList.remove('is-available', 'is-sold');
				if (status === 'available' || status === 'sold') {
					shape.classList.add(`is-${status}`);
				}

				if (shape.dataset.bound === '1') return;
				shape.dataset.bound = '1';

				shape.addEventListener('mouseenter', () => {
					if (window.matchMedia('(hover: hover)').matches && tooltip) {
						tooltipTitle.textContent = `Dom: ${buildingNumber}`;
						const currentStatus = getBuildingStatus(buildingNumber);
						tooltipStatus.textContent = currentStatus ? `Status: ${statusLabel[currentStatus]}` : 'Status: —';
						tooltip.hidden = false;
					}
				});

				shape.addEventListener('mousemove', (event) => {
					if (tooltip && !tooltip.hidden) {
						tooltip.style.left = `${event.clientX + 14}px`;
						tooltip.style.top = `${event.clientY + 14}px`;
					}
				});

				shape.addEventListener('mouseleave', () => {
					if (tooltip) tooltip.hidden = true;
				});

				shape.addEventListener('click', (event) => {
					event.preventDefault();
					shapes.forEach((item) => item.classList.remove('is-active'));
					shape.classList.add('is-active');
					renderChooseSide(buildingNumber);
				});

				shape.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						shape.click();
					}
				});
			});
		};

		sideCards.forEach((card) => {
			card.addEventListener('click', () => {
				if (!activeBuilding) return;
				const side = card.dataset.houseSideCard;
				const units = getBuildingUnits(activeBuilding);
				if (!units[side]) return;
				setSideActive(side);
				renderDetails(units[side]);
			});
		});

		sideButtons.forEach((btn) => {
			btn.addEventListener('click', () => {
				if (!activeBuilding) return;
				const side = btn.dataset.houseSideButton;
				const units = getBuildingUnits(activeBuilding);
				if (!units[side]) return;
				setSideActive(side);
				renderDetails(units[side]);
			});
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

		// DEV: polygon drawer
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
				return { x: Math.round(loc.x), y: Math.round(loc.y) };
			};

			const addPoint = (evt) => {
				if (!devOn) return;
				const svg = getActiveSvg();
				const poly = getTargetPoly();
				if (!svg || !poly) return;
				const p = getSvgPoint(evt);
				if (!p) return;
				points.push(p);
				poly.setAttribute('points', points.map((item) => `${item.x},${item.y}`).join(' '));
				poly.classList.add('is-dev-drawing');
				refreshOutput();
			};

			btnToggle.addEventListener('click', toggleDev);
			if (btnCopy) {
				btnCopy.addEventListener('click', async () => {
					if (!out) return;
					try { await navigator.clipboard.writeText(out.value); }
					catch (e) { out.select(); document.execCommand('copy'); }
				});
			}
			if (btnClear) btnClear.addEventListener('click', clearDev);
			if (inputHouse) inputHouse.addEventListener('change', clearDev);
			if (selectMap) selectMap.addEventListener('change', clearDev);
			root.addEventListener('pointerdown', (evt) => {
				if (!devOn) return;
				const svg = getActiveSvg();
				if (!svg || !svg.contains(evt.target)) return;
				addPoint(evt);
			});
			window.addEventListener('keydown', (evt) => {
				if (!devOn) return;
				if (evt.key === 'Enter') { evt.preventDefault(); refreshOutput(); }
				if (evt.key === 'Escape') { evt.preventDefault(); clearDev(); }
			});
		}

		bindShapes();

		fetchHouses().then(() => {
			bindShapes();
			setTimeout(bindShapes, 150);
		});
		window.addEventListener('resize', bindShapes);
	}

	const boot = () => {
		document.querySelectorAll('.houses').forEach(initHouses);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	const observer = new MutationObserver(() => {
		const found = document.querySelector('.houses');
		if (found) {
			boot();
			observer.disconnect();
		}
	});
	observer.observe(document.documentElement, { childList: true, subtree: true });
})();
