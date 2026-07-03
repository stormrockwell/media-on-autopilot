import { render, act } from '@testing-library/react';
import { TestPanel } from '../src/admin/components/TestPanel';

beforeEach( () => {
	window.moapAdmin = {
		restBase: 'http://x/moap/v1',
		nonce: 'n',
		sampleImage: 'http://x/sample.jpg',
		connectorsUrl: 'http://x/wp-admin/admin.php?page=connectors',
		labels: { testing: 'Testing…' },
	};
} );

test( 'run button disabled with hint when disabled', () => {
	const { container } = render(
		<TestPanel kind="cdn" disabled hint="Configure a CDN provider first" />
	);
	expect( container.querySelector( 'button' ).disabled ).toBe( true );
	expect(
		container.querySelector( '.moap-test-hint' ).textContent
	).toContain( 'Configure a CDN provider first' );
} );

test( 'ai test posts to /ai/test and shows result', async () => {
	const apiFetch = jest
		.fn()
		.mockResolvedValue( { alt: 'A dog', tags: [ 'dog', 'pet' ] } );
	const { container } = render(
		<TestPanel kind="ai" disabled={ false } hint="" apiFetch={ apiFetch } />
	);
	await act( async () => {
		container.querySelector( 'button.moap-test' ).click();
	} );
	expect( apiFetch ).toHaveBeenCalledWith(
		expect.objectContaining( {
			url: 'http://x/moap/v1/ai/test',
			method: 'POST',
		} )
	);
	expect( container.textContent ).toContain( 'A dog' );
} );

test( 'ai test failure links to the connectors screen', async () => {
	const apiFetch = jest.fn().mockResolvedValue( {
		state: 'error',
		message:
			'No models found that support text_generation for this prompt.',
	} );
	const { container } = render(
		<TestPanel kind="ai" disabled={ false } hint="" apiFetch={ apiFetch } />
	);
	await act( async () => {
		container.querySelector( 'button.moap-test' ).click();
	} );
	const link = container.querySelector(
		'a[href="http://x/wp-admin/admin.php?page=connectors"]'
	);
	expect( link ).not.toBeNull();
	expect( container.textContent ).toContain( 'Connectors' );
} );
