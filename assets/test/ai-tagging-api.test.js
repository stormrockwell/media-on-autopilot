import { tagAttachment } from '../src/ai-tagging/api';

it( 'POSTs to the ai-tagging endpoint', async () => {
	const apiFetch = jest.fn().mockResolvedValue( { alt: 'x', tags: [] } );

	await tagAttachment( apiFetch, 7 );

	expect( apiFetch ).toHaveBeenCalledWith( {
		path: '/moap/v1/ai-tagging/7',
		method: 'POST',
	} );
} );
