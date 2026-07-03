import { render, screen, act } from '@testing-library/react';
import { BatchProgress } from '../src/admin/components/tools/BatchProgress';

jest.useFakeTimers();

test( 'renders the batch shell with the slug', async () => {
	const apiFetch = jest.fn().mockResolvedValue( {
		status: 'idle',
		completed: 0,
		total: 0,
		lastRun: null,
	} );
	let container;
	await act( async () => {
		( { container } = render(
			<BatchProgress
				slug="ai_enrichment"
				label="Tagging"
				showSummary
				startEndpoint="moap/v1/batch/ai_enrichment/start"
				options={ {} }
				startLabel="Retag existing media"
				apiFetch={ apiFetch }
			/>
		) );
	} );
	expect(
		container.querySelector( '[data-moap-batch="ai_enrichment"]' )
	).not.toBeNull();
} );

test( 'start button posts to the start endpoint', async () => {
	const apiFetch = jest.fn().mockResolvedValue( {
		status: 'idle',
		completed: 0,
		total: 0,
		lastRun: null,
	} );
	await act( async () => {
		render(
			<BatchProgress
				slug="ai_enrichment"
				label="Tagging"
				showSummary
				startEndpoint="moap/v1/batch/ai_enrichment/start"
				options={ { alt: true } }
				startLabel="Retag existing media"
				apiFetch={ apiFetch }
			/>
		);
	} );
	await act( async () => {
		screen.getByText( 'Retag existing media' ).click();
	} );
	expect( apiFetch ).toHaveBeenCalledWith(
		expect.objectContaining( {
			path: 'moap/v1/batch/ai_enrichment/start',
			method: 'POST',
		} )
	);
} );
