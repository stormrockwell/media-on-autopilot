/* eslint-env browser */

export function computeProgressText( state ) {
	return `${ state.label }: ${ state.completed } / ${ state.total }`;
}

export function isTerminal( state ) {
	return state.status === 'done' || state.status === 'idle';
}

/**
 * Derive the three live counts from a batch status payload.
 *
 * @param {{ completed: number, skipped: number, failed: number }} state
 * @return {{ written: number, failed: number, skipped: number }} Counts object.
 */
export function computeCounts( state ) {
	const written = Math.max(
		0,
		state.completed - state.skipped - state.failed
	);
	return { written, failed: state.failed, skipped: state.skipped };
}

/**
 * Write the ✓/✗/⤼ counts line into the given element.
 * Clears the element when the state is idle (no active run).
 *
 * @param {Element}                                                                countsEl The `.moap-batch-counts` element.
 * @param {{ completed: number, skipped: number, failed: number, status: string }} state    Batch status payload.
 * @return {void}
 */
export function renderCounts( countsEl, state ) {
	if ( state.status === 'idle' ) {
		countsEl.textContent = '';
		return;
	}
	const { written, failed, skipped } = computeCounts( state );
	countsEl.textContent = `✓ ${ written } done  ✗ ${ failed } failed  ⤼ ${ skipped } skipped`;
}

export function computeSummaryText( lastRun ) {
	if ( ! lastRun ) {
		return '';
	}
	// Always show updated + failed; drop "skipped" only when nothing was
	// skipped (e.g. jobs like cleanup that never skip).
	const parts = [ `${ lastRun.succeeded } updated` ];
	if ( lastRun.skipped ) {
		parts.push( `${ lastRun.skipped } skipped` );
	}
	parts.push( `${ lastRun.failed } failed` );
	const when = new Date( lastRun.finishedAt * 1000 ).toLocaleString();
	return `Last run ${ when }: ${ parts.join( ', ' ) }`;
}

async function fetchState( apiFetch, slug ) {
	return apiFetch( { path: `moap/v1/batch/${ slug }` } );
}

export function pollProgress( { slug, root, apiFetch, intervalMs = 2000 } ) {
	const progressEl = root.querySelector( '.moap-batch-progress' );
	const bar = progressEl.querySelector( '.moap-batch-progress__bar' );
	const text = progressEl.querySelector( '.moap-batch-progress__text' );
	const cancel = progressEl.querySelector( '.moap-batch-progress__cancel' );
	const summary = root.querySelector( '.moap-batch-summary' );
	const counts = root.querySelector( '.moap-batch-counts' );
	// Some tools (offload) surface their own resting state and opt out of the
	// "Last run …" summary line.
	const showSummary = root.dataset.moapSummary !== 'off';

	// Guard so a manual re-kick (after start) never stacks two polling loops.
	let running = false;

	async function tick() {
		running = true;
		let state;
		try {
			state = await fetchState( apiFetch, slug );
		} catch ( e ) {
			// A failed poll must not leave `running` stuck true, or the start
			// button's re-kick would be permanently disabled.
			running = false;
			return;
		}
		const active = ! isTerminal( state );
		// Reveal the inner progress widget only while a run is in flight; the
		// server renders it hidden so an idle screen shows nothing.
		progressEl.classList.toggle( 'is-active', active );
		const total = state.total || 0;
		bar.value =
			total > 0 ? Math.round( ( state.completed / total ) * 100 ) : 0;
		text.textContent = computeProgressText( state );
		// Toggle display directly: the .button class overrides the [hidden]
		// attribute, so relying on cancel.hidden would not actually hide it.
		cancel.style.display = state.status === 'running' ? '' : 'none';
		// Swap displays: while a run is in flight show the live counts; while
		// resting (idle or finished) show the "Last run …" summary instead, so
		// only one is ever visible at a time.
		if ( active ) {
			if ( counts ) {
				renderCounts( counts, state );
			}
			if ( showSummary ) {
				summary.textContent = '';
			}
		} else {
			if ( counts ) {
				counts.textContent = '';
			}
			if ( showSummary ) {
				summary.textContent = computeSummaryText( state.lastRun );
			}
		}
		if ( active ) {
			window.setTimeout( tick, intervalMs );
		} else {
			running = false;
		}
	}

	cancel.addEventListener( 'click', async () => {
		cancel.disabled = true;
		await apiFetch( {
			path: `moap/v1/batch/${ slug }/cancel`,
			method: 'POST',
		} );
	} );

	tick();

	// Return a re-kick handle so the start button can resume polling after it
	// queues a fresh run on an otherwise-idle batch.
	return () => {
		if ( ! running ) {
			tick();
		}
	};
}
