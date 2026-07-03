/* global MutationObserver */

// The Edit Media full screen uses #attachment_alt; the media modal uses
// #attachments-<id>-alt.
function altInputFor( id ) {
	return (
		document.getElementById( 'attachment_alt' ) ||
		document.getElementById( `attachments-${ id }-alt` )
	);
}

function initButton( button, deps ) {
	if ( button.dataset.moapReady ) {
		return;
	}
	button.dataset.moapReady = '1';

	const root = button.closest( '.moap-ai-tagging' );
	const status = root
		? root.querySelector( '.moap-ai-tagging__status' )
		: null;
	const id = parseInt( button.dataset.attachmentId, 10 );

	button.addEventListener( 'click', async () => {
		button.disabled = true;
		if ( status ) {
			status.textContent = 'Generating…';
		}
		try {
			const result = await deps.tagAttachment( deps.apiFetch, id );
			const altInput = altInputFor( id );
			if ( altInput && result.alt ) {
				altInput.value = result.alt;
			}
			const tagList = root
				? root.querySelector( '.moap-ai-tagging__tag-list' )
				: null;
			if ( tagList && Array.isArray( result.tags ) ) {
				tagList.textContent = '';
				result.tags.forEach( ( tag ) => {
					const chip = document.createElement( 'span' );
					chip.className = 'moap-ai-tagging__chip';
					chip.textContent = tag;
					tagList.appendChild( chip );
				} );
			}
			if ( status ) {
				const count = Array.isArray( result.tags )
					? result.tags.length
					: 0;
				status.textContent = `Done — ${ count } tags added`;
			}
		} catch ( e ) {
			if ( window.console ) {
				// eslint-disable-next-line no-console
				window.console.error(
					'Media on Autopilot: AI tagging failed',
					e
				);
			}
			if ( status ) {
				const detail = e && e.message ? e.message : '';
				status.textContent = detail
					? `Failed — ${ detail }`
					: 'Failed — try again';
			}
		}
		button.disabled = false;
	} );
}

export function observeButtons( deps ) {
	const scan = () => {
		// The observer can fire after the document is torn down (e.g. between
		// tests); bail if the DOM is no longer available.
		if ( ! document || ! document.body ) {
			return;
		}
		document
			.querySelectorAll(
				'.moap-ai-tagging__generate:not([data-moap-ready])'
			)
			.forEach( ( button ) => initButton( button, deps ) );
	};

	scan();
	const observer = new MutationObserver( scan );
	observer.observe( document.body, { childList: true, subtree: true } );
}
