/* global MouseEvent */
import { initPicker } from '../src/focal-point/picker';

function makePicker( { x, y, id = 42 } = {} ) {
	const dataX = x !== undefined ? ` data-x="${ x }"` : '';
	const dataY = y !== undefined ? ` data-y="${ y }"` : '';
	document.body.innerHTML = `
		<div class="moap-focal-point" data-attachment-id="${ id }"${ dataX }${ dataY }>
			<div class="moap-focal-point__stage">
				<img class="moap-focal-point__image" alt="" />
				<span class="moap-focal-point__marker"></span>
			</div>
			<div class="moap-focal-point__controls">
				<button type="button" class="moap-focal-point__save"></button>
				<span class="moap-focal-point__status"></span>
			</div>
		</div>`;
	const root = document.querySelector( '.moap-focal-point' );
	const image = root.querySelector( '.moap-focal-point__image' );
	// jsdom returns a zero rect; provide a deterministic one.
	image.getBoundingClientRect = () => ( {
		left: 0,
		top: 0,
		width: 200,
		height: 400,
	} );
	return root;
}

beforeEach( () => {
	window.moapFocalPoint = { metaKey: '_moap_focal_point' };
} );

describe( 'initPicker', () => {
	it( 'positions the marker at the saved focal point on load', () => {
		const root = makePicker( { x: 0.25, y: 0.75 } );
		initPicker( root, { apiFetch: jest.fn() } );
		const marker = root.querySelector( '.moap-focal-point__marker' );
		expect( marker.style.left ).toBe( '25%' );
		expect( marker.style.top ).toBe( '75%' );
	} );

	it( 'defaults the marker to center when no point is saved', () => {
		const root = makePicker();
		initPicker( root, { apiFetch: jest.fn() } );
		const marker = root.querySelector( '.moap-focal-point__marker' );
		expect( marker.style.left ).toBe( '50%' );
		expect( marker.style.top ).toBe( '50%' );
	} );

	it( 'keeps Save hidden until the point changes', () => {
		const root = makePicker( { x: 0.25, y: 0.75 } );
		initPicker( root, { apiFetch: jest.fn() } );
		const save = root.querySelector( '.moap-focal-point__save' );
		expect( save.hidden ).toBe( true );

		const image = root.querySelector( '.moap-focal-point__image' );
		image.dispatchEvent(
			new MouseEvent( 'click', {
				clientX: 100,
				clientY: 200,
				bubbles: true,
			} )
		);
		expect( save.hidden ).toBe( false );
	} );

	it( 'previews on click without saving', () => {
		const root = makePicker( { x: 0.25, y: 0.75 } );
		const apiFetch = jest.fn().mockResolvedValue( {} );
		initPicker( root, { apiFetch } );

		const image = root.querySelector( '.moap-focal-point__image' );
		image.dispatchEvent(
			new MouseEvent( 'click', {
				clientX: 50,
				clientY: 100,
				bubbles: true,
			} )
		);

		const marker = root.querySelector( '.moap-focal-point__marker' );
		// (50/200, 100/400) => 25%, 25%
		expect( marker.style.left ).toBe( '25%' );
		expect( marker.style.top ).toBe( '25%' );
		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'saves the current point only when Save is clicked', async () => {
		const root = makePicker( { x: 0.25, y: 0.75 } );
		const apiFetch = jest.fn().mockResolvedValue( {} );
		initPicker( root, { apiFetch } );

		const image = root.querySelector( '.moap-focal-point__image' );
		image.dispatchEvent(
			new MouseEvent( 'click', {
				clientX: 100,
				clientY: 200,
				bubbles: true,
			} )
		);

		const save = root.querySelector( '.moap-focal-point__save' );
		save.dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
		await Promise.resolve();
		await Promise.resolve();

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: '/wp/v2/media/42',
				method: 'POST',
				data: { meta: { _moap_focal_point: { x: 0.5, y: 0.5 } } },
			} )
		);
		expect( save.hidden ).toBe( true );
		expect(
			root.querySelector( '.moap-focal-point__status' ).textContent
		).toBe( 'Saved' );
	} );
} );
