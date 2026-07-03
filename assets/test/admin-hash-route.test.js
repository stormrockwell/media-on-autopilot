import { renderHook, act } from '@testing-library/react';
import { useHashRoute } from '../src/admin/state/useHashRoute';

beforeEach( () => {
	window.history.replaceState( null, '', '#' );
} );

test( 'parses #cdn/test on mount', () => {
	window.history.replaceState( null, '', '#cdn/test' );
	const { result } = renderHook( () => useHashRoute() );
	expect( result.current.view ).toBe( 'cdn' );
	expect( result.current.subTab ).toBe( 'test' );
} );

test( 'unknown view falls back to dashboard', () => {
	window.history.replaceState( null, '', '#nope' );
	const { result } = renderHook( () => useHashRoute() );
	expect( result.current.view ).toBe( 'dashboard' );
} );

test( 'setView + setSubTab update the hash', () => {
	const { result } = renderHook( () => useHashRoute() );
	act( () => result.current.setView( 'ai' ) );
	act( () => result.current.setSubTab( 'tools' ) );
	expect( window.location.hash ).toBe( '#ai/tools' );
} );

test( 'dashboard clears the hash', () => {
	window.history.replaceState( null, '', '#cdn/test' );
	const { result } = renderHook( () => useHashRoute() );
	act( () => result.current.setView( 'dashboard' ) );
	expect( window.location.hash === '' || window.location.hash === '#' ).toBe(
		true
	);
} );
