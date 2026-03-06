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

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function formatNumber(n) {
		if (typeof n !== "number" || Number.isNaN(n)) return "";
		return new Intl.NumberFormat("pl-PL").format(n);
	}

	function formatAreaNum(n) {
		if (typeof n !== "number" || Number.isNaN(n) || n <= 0) return "—";
		return formatNumber(n) + " m²";
	}

	function formatPriceNum(amount, currency) {
		if (typeof amount !== "number" || Number.isNaN(amount) || amount <= 0) return "—";
		const cur = (currency && typeof currency === "string") ? currency : "PLN";

		try {
			return new Intl.NumberFormat("pl-PL", {
				style: "currency",
				currency: cur,
				maximumFractionDigits: 0
			}).format(amount);
		} catch (e) {
			return formatNumber(amount) + " " + cur;
		}
	}

	function tableStatusLabel(status) {
		if (status === "sold") return "Sprzedany";
		if (status === "available") return "Dostępny";
		return "Dostępny";
	}

	function tableStatusClass(status) {
		if (status === "sold") return "is-sold";
		if (status === "available") return "is-available";
		return "is-available";
	}

	function renderHousesTable(houses, root) {
		const scope = root || document;
		const tbody = scope.querySelector("[data-houses-table-body]");
		if (!tbody) return;

		const rows = Array.isArray(houses) ? houses.slice() : [];

		rows.sort(function(a, b) {
			const an = parseInt(a.number, 10);
			const bn = parseInt(b.number, 10);

			if (!Number.isNaN(an) && !Number.isNaN(bn) && an !== bn) return an - bn;

			const aside = (a.unit_side || "").toString();
			const bside = (b.unit_side || "").toString();

			if (aside === bside) return 0;
			if (aside === "left") return -1;
			if (bside === "left") return 1;

			return aside.localeCompare(bside);
		});

		if (rows.length === 0) {
			tbody.innerHTML = "<tr><td colspan=\"7\">Brak danych do wyświetlenia.</td></tr>";
			return;
		}

		const html = rows.map(function(h) {
			const labelRaw = (h.label && h.label.toString().trim()) ? h.label.toString().trim() : ("Dom " + h.number + " (" + h.unit_side + ")");
			const label = escapeHtml(labelRaw);

			const area = formatAreaNum(Number(h.area));
			const rooms = (typeof h.rooms === "number" && !Number.isNaN(h.rooms) && h.rooms > 0) ? String(h.rooms) : "—";
			const plot = formatAreaNum(Number(h.plot));

			const statusLabel = tableStatusLabel(h.status);
			const statusClass = tableStatusClass(h.status);

			const price = formatPriceNum(Number(h.price), h.currency);

			const planUrl = (h.plan_url && h.plan_url.toString().trim()) ? h.plan_url.toString().trim() : "";
			const planLink = planUrl
				? "<a class=\"houses__table-link\" href=\"" + escapeHtml(planUrl) + "\" target=\"_blank\" rel=\"noopener\">Pobierz PDF</a>"
				: "—";

			return ""
				+ "<tr>"
				+   "<td data-label=\"Oznaczenie domu (działka)\">" + label + "</td>"
				+   "<td data-label=\"Powierzchnia lokalu\">" + area + "</td>"
				+   "<td data-label=\"Liczba pokoi\">" + rooms + "</td>"
				+   "<td data-label=\"Dostępność\"><span class=\"houses__table-status " + statusClass + "\">" + statusLabel + "</span></td>"
				+   "<td data-label=\"Powierzchnia działki\">" + plot + "</td>"
				+   "<td data-label=\"Cena\">" + price + "</td>"
				+   "<td data-label=\"Karta lokalu\">" + planLink + "</td>"
				+ "</tr>";
		}).join("");

		tbody.innerHTML = html;
	}

	function setHousesTableCollapsedHeight(root, visibleCount) {
		const list = root.querySelector('[data-houses-table-list]');
		if (!list) return;

		const tbody = root.querySelector('[data-houses-table-body]');
		if (!tbody) return;

		const trs = tbody.querySelectorAll('tr');
		if (!trs.length) return;

		const targetIndex = Math.max(0, (visibleCount || 4)); // np 4 -> 5-ty ma wystawać
		const peekRatio = 0.35;

		if (trs.length <= targetIndex) {
			list.style.setProperty('--houses-table-collapsed-h', list.scrollHeight + 'px');
			return;
		}

		const rowPeek = trs[targetIndex];

		const listRect = list.getBoundingClientRect();
		const rowRect = rowPeek.getBoundingClientRect();

		const rowHeight = rowRect.height || rowPeek.offsetHeight || 0;
		const peek = Math.max(32, Math.min(80, Math.round(rowHeight * peekRatio)));

		const topInside = rowRect.top - listRect.top;
		const collapsed = Math.round(topInside + peek);

		list.style.setProperty('--houses-table-collapsed-h', collapsed + 'px');
	}

	function initHousesTablePeek(root, visibleCount) {
		const list = root.querySelector('[data-houses-table-list]');
		const btn = root.querySelector('[data-houses-table-more]');
		const tbody = root.querySelector('[data-houses-table-body]');
		if (!list || !btn || !tbody) return;

		const trs = tbody.querySelectorAll('tr');
		const limit = (typeof visibleCount === 'number' && visibleCount > 0) ? visibleCount : 4;

		if (trs.length <= limit) {
			btn.hidden = true;
			list.classList.add('is-expanded');
			list.style.removeProperty('--houses-table-collapsed-h');
			return;
		}

		btn.hidden = false;

		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				if (!list.classList.contains('is-expanded')) {
					setHousesTableCollapsedHeight(root, limit);
				}
			});
		});

		window.addEventListener('resize', function () {
			if (!list.classList.contains('is-expanded')) {
				setHousesTableCollapsedHeight(root, limit);
			}
		});

		if (btn.dataset.bound === '1') {
			return;
		}
		btn.dataset.bound = '1';

		btn.addEventListener('click', function (e) {
			e.preventDefault();

			const isExpanded = list.classList.toggle('is-expanded');
			btn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
			btn.querySelector('.btn__text').textContent = isExpanded ? 'Zobacz mniej' : 'Zobacz więcej';

			if (!isExpanded) {
				setHousesTableCollapsedHeight(root, limit);
			} else {
				list.style.removeProperty('--houses-table-collapsed-h');
			}
		});
	}

	function initHousesTableToggle(root) {
		const wrap = root.querySelector("[data-houses-table-wrap]");
		if (!wrap) return;
		if (wrap.dataset.tableInited === "1") return;
		wrap.dataset.tableInited = "1";

		const btn = wrap.querySelector("[data-houses-table-toggle]");
		const panel = wrap.querySelector("[data-houses-table-panel]");
		const text = wrap.querySelector(".houses__table-toggle-text");

		if (!btn || !panel) return;

		const setOpen = (isOpen) => {
			wrap.classList.toggle("is-open", isOpen);
			btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
			panel.hidden = !isOpen;
			if (text) text.textContent = isOpen ? "Ukryj listę lokali" : "Pokaż listę lokali";
		};

		setOpen(false);

		btn.addEventListener("click", () => {
			const isOpen = btn.getAttribute("aria-expanded") === "true";
			setOpen(!isOpen);

			if (!isOpen) {
				panel.scrollIntoView({ behavior: "smooth", block: "start" });
			}
		});
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
		const expandedNumber = root.querySelector('[data-house-expanded-number]');
		const fieldPrice = root.querySelector('[data-house-price]');
		const fieldArea = root.querySelector('[data-house-area]');
		const fieldPlot = root.querySelector('[data-house-plot]');
		const fieldRooms = root.querySelector('[data-house-rooms]');
		const fieldStatus = root.querySelector('[data-house-status]');
		const linkPlan = root.querySelector('[data-house-plan]');
		const modelImg = root.querySelector('[data-house-model]');
		const sideStatuses = {
			left: root.querySelector('[data-house-side-status="left"]'),
			right: root.querySelector('[data-house-side-status="right"]'),
		};


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

		const sideLabels = {
			left: root.querySelector('[data-house-side-label="left"]'),
			right: root.querySelector('[data-house-side-label="right"]'),
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

					if (typeof renderHousesTable === "function") {
						renderHousesTable(payload.data, root);
					}

					if (typeof initHousesTablePeek === "function") {
						initHousesTablePeek(root, 4);
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
			if (expandedNumber) {
				const label = unit && unit.label ? unit.label : '—';
				expandedNumber.textContent = label;
			}
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

				if (sideLabels[side]) {
					const label = unit && unit.label ? unit.label : '—';
					sideLabels[side].textContent = `Nr działki: ${label}`;
				}

				if (sideStatuses[side]) {
					if (unit) {
						const status = unit.status === 'sold' ? 'sold' : 'available';
						sideStatuses[side].textContent = statusLabel[status] || '—';
						sideStatuses[side].classList.remove('is-available', 'is-sold');
						sideStatuses[side].classList.add(`is-${status}`);
						sideStatuses[side].hidden = false;
					} else {
						sideStatuses[side].textContent = '';
						sideStatuses[side].hidden = true;
					}
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
