export function tagAttachment( apiFetch, attachmentId ) {
	return apiFetch( {
		path: `/moap/v1/ai-tagging/${ attachmentId }`,
		method: 'POST',
	} );
}
