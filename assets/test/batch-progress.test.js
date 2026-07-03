import {
	computeProgressText,
	isTerminal,
	computeSummaryText,
} from '../src/batch/progress';

describe( 'batch progress helpers', () => {
	it( 'formats running progress', () => {
		expect(
			computeProgressText( {
				status: 'running',
				completed: 3,
				total: 10,
				label: 'AI',
			} )
		).toBe( 'AI: 3 / 10' );
	} );

	it( 'reports done as terminal', () => {
		expect( isTerminal( { status: 'done' } ) ).toBe( true );
		expect( isTerminal( { status: 'idle' } ) ).toBe( true );
		expect( isTerminal( { status: 'running' } ) ).toBe( false );
		expect( isTerminal( { status: 'cancelling' } ) ).toBe( false );
	} );
} );

describe( 'computeSummaryText', () => {
	it( 'returns empty string when no lastRun', () => {
		expect( computeSummaryText( null ) ).toBe( '' );
		expect( computeSummaryText( undefined ) ).toBe( '' );
	} );

	it( 'always shows updated and failed, including zero', () => {
		const result = computeSummaryText( {
			finishedAt: 1700000000,
			succeeded: 9,
			skipped: 0,
			failed: 0,
		} );
		expect( result ).toContain( '9 updated' );
		expect( result ).toContain( '0 failed' );
		expect( result ).not.toContain( 'skipped' );
	} );

	it( 'shows skipped only when non-zero', () => {
		const result = computeSummaryText( {
			finishedAt: 1700000000,
			succeeded: 0,
			skipped: 9,
			failed: 0,
		} );
		expect( result ).toContain( '0 updated' );
		expect( result ).toContain( '9 skipped' );
		expect( result ).toContain( '0 failed' );
	} );
} );
