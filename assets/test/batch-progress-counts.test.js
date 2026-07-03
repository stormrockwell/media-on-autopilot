import {
	computeCounts,
	renderCounts,
	pollProgress,
} from '../src/batch/progress';

function flushPromises() {
	return new Promise( ( resolve ) => setImmediate( resolve ) );
}

describe( 'computeCounts', () => {
	it( 'computes written as completed minus skipped minus failed', () => {
		expect(
			computeCounts( { completed: 10, skipped: 2, failed: 1 } )
		).toEqual( { written: 7, failed: 1, skipped: 2 } );
	} );

	it( 'clamps written to zero when skipped + failed exceed completed', () => {
		expect(
			computeCounts( { completed: 2, skipped: 2, failed: 1 } )
		).toEqual( { written: 0, failed: 1, skipped: 2 } );
	} );

	it( 'handles all zeros', () => {
		expect(
			computeCounts( { completed: 0, skipped: 0, failed: 0 } )
		).toEqual( { written: 0, failed: 0, skipped: 0 } );
	} );
} );

describe( 'renderCounts', () => {
	function makeCountsEl() {
		const el = document.createElement( 'p' );
		el.className = 'moap-batch-counts';
		return el;
	}

	it( 'renders done/failed/skipped counts into the counts element', () => {
		const countsEl = makeCountsEl();
		const state = {
			total: 10,
			completed: 10,
			skipped: 2,
			failed: 1,
			status: 'done',
			label: 'Offload',
		};
		renderCounts( countsEl, state );

		expect( countsEl.textContent ).toContain( '7 done' );
		expect( countsEl.textContent ).toContain( '1 failed' );
		expect( countsEl.textContent ).toContain( '2 skipped' );
	} );

	it( 'uses ✓ ✗ ⤼ symbols', () => {
		const countsEl = makeCountsEl();
		renderCounts( countsEl, {
			completed: 5,
			skipped: 0,
			failed: 0,
			status: 'done',
		} );

		expect( countsEl.textContent ).toContain( '✓' );
		expect( countsEl.textContent ).toContain( '✗' );
		expect( countsEl.textContent ).toContain( '⤼' );
	} );

	it( 'clears counts when state is idle (no active run)', () => {
		const countsEl = makeCountsEl();
		// Pre-populate to verify it gets cleared.
		countsEl.textContent = 'stale content';
		renderCounts( countsEl, {
			completed: 0,
			skipped: 0,
			failed: 0,
			status: 'idle',
		} );
		expect( countsEl.textContent ).toBe( '' );
	} );
} );

describe( 'pollProgress with counts', () => {
	function makeRoot( slug = 'test-job' ) {
		const root = document.createElement( 'div' );
		root.className = 'moap-batch';
		root.dataset.moapBatch = slug;

		const progressEl = document.createElement( 'div' );
		progressEl.className = 'moap-batch-progress';

		const bar = document.createElement( 'progress' );
		bar.className = 'moap-batch-progress__bar';
		bar.max = 100;
		bar.value = 0;
		progressEl.appendChild( bar );

		const text = document.createElement( 'span' );
		text.className = 'moap-batch-progress__text';
		progressEl.appendChild( text );

		const cancel = document.createElement( 'button' );
		cancel.className = 'moap-batch-progress__cancel';
		progressEl.appendChild( cancel );

		root.appendChild( progressEl );

		const summary = document.createElement( 'p' );
		summary.className = 'moap-batch-summary';
		root.appendChild( summary );

		const counts = document.createElement( 'p' );
		counts.className = 'moap-batch-counts';
		root.appendChild( counts );

		return root;
	}

	it( 'writes live counts into .moap-batch-counts while a run is in flight', async () => {
		const root = makeRoot();
		document.body.appendChild( root );

		const fakeState = {
			total: 10,
			completed: 7,
			skipped: 2,
			failed: 1,
			status: 'running',
			label: 'Offload',
			lastRun: null,
		};

		const apiFetch = jest.fn().mockResolvedValue( fakeState );

		// Use a large interval so polling doesn't recurse during the test.
		pollProgress( { slug: 'test-job', root, apiFetch, intervalMs: 99999 } );

		// Let the async tick (apiFetch → state updates) resolve fully.
		await flushPromises();

		const countsEl = root.querySelector( '.moap-batch-counts' );
		expect( countsEl.textContent ).toContain( '4 done' );
		expect( countsEl.textContent ).toContain( '1 failed' );
		expect( countsEl.textContent ).toContain( '2 skipped' );

		document.body.removeChild( root );
	} );

	it( 'clears the live counts once the run finishes (resting shows the summary)', async () => {
		const root = makeRoot();
		document.body.appendChild( root );

		const fakeState = {
			total: 10,
			completed: 10,
			skipped: 2,
			failed: 1,
			status: 'done',
			label: 'Offload',
			lastRun: null,
		};

		const apiFetch = jest.fn().mockResolvedValue( fakeState );
		pollProgress( { slug: 'test-job', root, apiFetch, intervalMs: 99999 } );
		await flushPromises();

		// When the job is no longer running, the live counts line is cleared so
		// only the "Last run …" summary remains.
		const countsEl = root.querySelector( '.moap-batch-counts' );
		expect( countsEl.textContent ).toBe( '' );

		document.body.removeChild( root );
	} );
} );
