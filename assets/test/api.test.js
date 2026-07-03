import { saveFocalPoint } from '../src/focal-point/api';

beforeEach( () => {
	window.moapFocalPoint = { metaKey: '_moap_focal_point' };
} );

it( 'POSTs the focal point as attachment meta to the media endpoint', async () => {
	const apiFetch = jest.fn().mockResolvedValue( {} );

	await saveFocalPoint( apiFetch, 42, { x: 0.5, y: 0.5 } );

	expect( apiFetch ).toHaveBeenCalledWith( {
		path: '/wp/v2/media/42',
		method: 'POST',
		data: { meta: { _moap_focal_point: { x: 0.5, y: 0.5 } } },
	} );
} );
