import { useState, useEffect, useCallback } from '@wordpress/element';

const VIEWS = [ 'dashboard', 'focal', 'ai', 'cdn' ];

function parseHash() {
	const raw = window.location.hash.replace( /^#/, '' );
	const [ view, subTab = '' ] = raw.split( '/' );
	return {
		view: VIEWS.includes( view ) ? view : 'dashboard',
		subTab,
	};
}

function writeHash( view, subTab ) {
	let hash = '';
	if ( view && view !== 'dashboard' ) {
		hash = subTab ? `#${ view }/${ subTab }` : `#${ view }`;
	}
	const url = window.location.pathname + window.location.search + hash;
	window.history.replaceState( null, '', url );
}

export function useHashRoute() {
	const initial = parseHash();
	const [ view, setViewState ] = useState( initial.view );
	const [ subTab, setSubTabState ] = useState( initial.subTab );

	useEffect( () => {
		writeHash( view, subTab );
	}, [ view, subTab ] );

	const setView = useCallback( ( next ) => {
		setViewState( next );
		setSubTabState( '' );
	}, [] );

	const setSubTab = useCallback( ( next ) => {
		setSubTabState( next );
	}, [] );

	return { view, subTab, setView, setSubTab };
}
