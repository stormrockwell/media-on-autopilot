import {
	clickToFocalPoint,
	focalToMarkerStyle,
} from '../src/focal-point/coordinates';

const rect = { left: 100, top: 50, width: 200, height: 400 };

describe( 'clickToFocalPoint', () => {
	it( 'maps a click to a normalized point relative to the element', () => {
		expect( clickToFocalPoint( 200, 250, rect ) ).toEqual( {
			x: 0.5,
			y: 0.5,
		} );
	} );

	it( 'clamps clicks outside the element to the 0..1 range', () => {
		expect( clickToFocalPoint( 0, 0, rect ) ).toEqual( { x: 0, y: 0 } );
		expect( clickToFocalPoint( 9999, 9999, rect ) ).toEqual( {
			x: 1,
			y: 1,
		} );
	} );
} );

describe( 'focalToMarkerStyle', () => {
	it( 'converts a point to percent CSS offsets', () => {
		expect( focalToMarkerStyle( { x: 0.25, y: 0.75 } ) ).toEqual( {
			left: '25%',
			top: '75%',
		} );
	} );
} );
