/**
 * Cross-plugin chat bubble coordination.
 *
 * Chat Help and Better Chat Support both render a `position: fixed` bubble.
 * When two of them are pinned to the same corner they collide in two ways:
 *
 *   1. The floating bubbles sit on top of each other. Handled by stacking them
 *      with `margin-bottom`, which shifts a fixed element without touching the
 *      `transform` the animation classes use.
 *   2. Both popups can be open at the same time, and because both plugins use
 *      the same z-index the one that happens to be later in the DOM always wins
 *      — the visitor sees one chat box drawn over the other. Handled by only
 *      ever letting one widget be open: opening one closes the other, the open
 *      one drops back to its own corner offset, and the rest are hidden until
 *      it is closed again.
 *
 * The file ships identically in all four plugins under the same script handle,
 * so WordPress loads a single copy and the guard below keeps it idempotent.
 */
(function () {
	if (window.tmaBubbleStackLoaded) {
		return;
	}
	window.tmaBubbleStackLoaded = true;

	var GAP = 12;
	var SELECTOR = '.wHelp_bubble, .mSupport_bubble';
	var SHOW_CLASSES = ['wHelp-show', 'mSupport-show'];
	var ACTIVE_Z_INDEX = '100000000000';

	// Set while we edit classes ourselves, so the class observer below does not
	// react to its own changes.
	var syncing = false;

	function bubbles() {
		return Array.prototype.slice.call(document.querySelectorAll(SELECTOR));
	}

	function isOpen(el) {
		for (var i = 0; i < SHOW_CLASSES.length; i++) {
			if (el.classList.contains(SHOW_CLASSES[i])) {
				return true;
			}
		}
		return false;
	}

	function close(el) {
		SHOW_CLASSES.forEach(function (name) {
			el.classList.remove(name);
		});
	}

	/**
	 * Chat Help uses `right_bottom`, Better Chat Support uses `bottom_right`.
	 * Both spellings, plus the Pro tablet/mobile variants, land on one key.
	 */
	function sideKey(el) {
		var name = ' ' + el.className + ' ';

		if (/left_bottom|bottom_left/.test(name)) {
			return 'bottom-left';
		}
		if (/left_middle|middle_left/.test(name)) {
			return 'middle-left';
		}
		if (/right_middle|middle_right/.test(name)) {
			return 'middle-right';
		}

		return 'bottom-right';
	}

	function reset(el) {
		el.style.marginBottom = '';
		el.style.visibility = '';
		el.style.pointerEvents = '';
		el.style.zIndex = '';
	}

	/**
	 * Lay the bubbles out. With a widget open, that widget owns its corner and
	 * the others step aside; otherwise every bubble in a corner is stacked.
	 */
	function layout() {
		var all = bubbles();
		var open = null;

		all.forEach(function (el) {
			if (!open && isOpen(el)) {
				open = el;
			}
		});

		if (open) {
			all.forEach(function (el) {
				reset(el);

				if (el === open) {
					el.style.zIndex = ACTIVE_Z_INDEX;
					return;
				}

				// Only step aside for a widget sharing this corner; a bubble in
				// another corner is not in the way.
				if (sideKey(el) === sideKey(open)) {
					el.style.visibility = 'hidden';
					el.style.pointerEvents = 'none';
				}
			});
			return;
		}

		var groups = {};

		all.forEach(function (el) {
			reset(el);

			if (window.getComputedStyle(el).display === 'none') {
				return;
			}

			var key = sideKey(el);
			groups[key] = groups[key] || [];
			groups[key].push(el);
		});

		Object.keys(groups).forEach(function (key) {
			var offset = 0;

			groups[key].forEach(function (el) {
				el.style.marginBottom = offset ? offset + 'px' : '';
				offset += el.offsetHeight + GAP;
			});
		});
	}

	/**
	 * Keep at most one widget open. `changed` is the bubble that just changed
	 * class, so it wins over one that was already open (auto-open popups from
	 * two plugins race at page load, where nothing "just changed" and the first
	 * in the DOM keeps the screen).
	 */
	function enforceSingleOpen(changed) {
		var all = bubbles();
		var keep = changed && isOpen(changed) ? changed : null;

		if (!keep) {
			for (var i = 0; i < all.length; i++) {
				if (isOpen(all[i])) {
					keep = all[i];
					break;
				}
			}
		}

		if (!keep) {
			return;
		}

		syncing = true;
		all.forEach(function (el) {
			if (el !== keep && isOpen(el) && sideKey(el) === sideKey(keep)) {
				close(el);
			}
		});
		syncing = false;
	}

	function sync(changed) {
		enforceSingleOpen(changed);
		layout();
	}

	function watch(el) {
		if (el.tmaBubbleWatched || !window.MutationObserver) {
			return;
		}
		el.tmaBubbleWatched = true;

		new window.MutationObserver(function () {
			if (!syncing) {
				sync(el);
			}
		}).observe(el, { attributes: true, attributeFilter: ['class'] });
	}

	function boot() {
		bubbles().forEach(watch);
		sync(null);

		window.addEventListener('resize', layout);

		// Bubbles are appended to the body late by some templates and by the
		// admin live preview, so pick up new ones when the body's children change.
		if (window.MutationObserver && document.body) {
			new window.MutationObserver(function () {
				bubbles().forEach(watch);
				sync(null);
			}).observe(document.body, { childList: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.addEventListener('load', function () {
		bubbles().forEach(watch);
		sync(null);
	});
})();
