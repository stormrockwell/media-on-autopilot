/* global MutationObserver */
import { clickToFocalPoint, focalToMarkerStyle } from './coordinates';
import { saveFocalPoint } from './api';

const clamp = ( value ) => Math.max( 0, Math.min( 1, value ) );

function readPoint( root ) {
	const x = parseFloat( root.dataset.x );
	const y = parseFloat( root.dataset.y );
	return {
		x: Number.isFinite( x ) ? clamp( x ) : 0.5,
		y: Number.isFinite( y ) ? clamp( y ) : 0.5,
	};
}

export function initPicker( root, deps ) {
	if ( root.dataset.moapReady ) {
		return;
	}
	root.dataset.moapReady = '1';

	const image = root.querySelector( '.moap-focal-point__image' );
	const stage = root.querySelector( '.moap-focal-point__stage' ) || image;
	const marker = root.querySelector( '.moap-focal-point__marker' );
	const saveButton = root.querySelector( '.moap-focal-point__save' );
	const status = root.querySelector( '.moap-focal-point__status' );
	const attachmentId = parseInt( root.dataset.attachmentId, 10 );

	let point = readPoint( root );
	let savedPoint = { ...point };

	const positionMarker = () => {
		const style = focalToMarkerStyle( point );
		marker.style.left = style.left;
		marker.style.top = style.top;
	};

	const isDirty = () => point.x !== savedPoint.x || point.y !== savedPoint.y;

	const refreshSaveState = () => {
		if ( saveButton ) {
			saveButton.hidden = ! isDirty();
			saveButton.disabled = false;
		}
	};

	// Reflect the stored focal point on load.
	positionMarker();
	refreshSaveState();

	stage.addEventListener( 'click', ( event ) => {
		const rect = image.getBoundingClientRect();
		point = clickToFocalPoint( event.clientX, event.clientY, rect );
		positionMarker();
		refreshSaveState();
		if ( status ) {
			status.textContent = '';
		}
	} );

	if ( saveButton ) {
		saveButton.addEventListener( 'click', async () => {
			saveButton.disabled = true;
			if ( status ) {
				status.textContent = '';
			}
			try {
				await saveFocalPoint( deps.apiFetch, attachmentId, point );
				savedPoint = { ...point };
				if ( status ) {
					status.textContent = 'Saved';
				}
			} catch ( e ) {
				if ( status ) {
					status.textContent = 'Save failed';
				}
			}
			refreshSaveState();
		} );
	}
}

export function observePickers( deps ) {
	const scan = () => {
		// The observer can fire after the document is torn down (e.g. between
		// tests); bail if the DOM is no longer available.
		if ( ! document || ! document.body ) {
			return;
		}
		document
			.querySelectorAll( '.moap-focal-point:not([data-moap-ready])' )
			.forEach( ( root ) => initPicker( root, deps ) );
	};

	scan();
	const observer = new MutationObserver( scan );
	observer.observe( document.body, { childList: true, subtree: true } );
}
