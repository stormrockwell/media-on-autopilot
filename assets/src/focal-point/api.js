export function saveFocalPoint( apiFetch, attachmentId, point ) {
	const metaKey = window.moapFocalPoint.metaKey;
	return apiFetch( {
		path: `/wp/v2/media/${ attachmentId }`,
		method: 'POST',
		data: { meta: { [ metaKey ]: point } },
	} );
}
