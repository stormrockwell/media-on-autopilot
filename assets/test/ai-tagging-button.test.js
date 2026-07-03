import { observeButtons } from '../src/ai-tagging/button';

function setup( id ) {
	document.body.innerHTML = `
		<div class="moap-ai-tagging">
			<button class="moap-ai-tagging__generate" data-attachment-id="${ id }">Go</button>
			<span class="moap-ai-tagging__status"></span>
			<span class="moap-ai-tagging__tag-list"></span>
		</div>
		<input id="attachments-${ id }-alt" value="" />
	`;
}

it( 'tags on click, fills the alt input and status', async () => {
	setup( 9 );
	const tagAttachment = jest
		.fn()
		.mockResolvedValue( { alt: 'A dog', tags: [ 'dog', 'pet' ] } );

	observeButtons( { apiFetch: jest.fn(), tagAttachment } );

	document.querySelector( '.moap-ai-tagging__generate' ).click();
	await Promise.resolve();
	await Promise.resolve();

	expect( tagAttachment ).toHaveBeenCalledWith( expect.any( Function ), 9 );
	expect( document.getElementById( 'attachments-9-alt' ).value ).toBe(
		'A dog'
	);
	expect(
		document.querySelector( '.moap-ai-tagging__status' ).textContent
	).toContain( '2' );
	const chips = document.querySelectorAll(
		'.moap-ai-tagging__tag-list .moap-ai-tagging__chip'
	);
	expect( [ ...chips ].map( ( c ) => c.textContent ) ).toEqual( [
		'dog',
		'pet',
	] );
} );

it( 'surfaces the actual error message when tagging rejects', async () => {
	setup( 4 );
	const tagAttachment = jest
		.fn()
		.mockRejectedValue( new Error( 'No AI provider is configured.' ) );

	observeButtons( { apiFetch: jest.fn(), tagAttachment } );
	document.querySelector( '.moap-ai-tagging__generate' ).click();
	await Promise.resolve();
	await Promise.resolve();

	expect(
		document.querySelector( '.moap-ai-tagging__status' ).textContent
	).toContain( 'No AI provider is configured.' );
	expect( console ).toHaveErrored();
} );

it( 'falls back to a generic message when the error has no message', async () => {
	setup( 5 );
	const tagAttachment = jest.fn().mockRejectedValue( {} );

	observeButtons( { apiFetch: jest.fn(), tagAttachment } );
	document.querySelector( '.moap-ai-tagging__generate' ).click();
	await Promise.resolve();
	await Promise.resolve();

	expect(
		document.querySelector( '.moap-ai-tagging__status' ).textContent
	).toContain( 'Failed' );
	expect( console ).toHaveErrored();
} );
