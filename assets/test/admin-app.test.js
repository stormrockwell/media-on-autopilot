import { render, act } from '@testing-library/react';
import { SettingsProvider } from '../src/admin/state/SettingsContext';
import { App } from '../src/admin/App';

beforeEach( () => {
	window.history.replaceState( null, '', '#' );
	window.moapAdmin = { restBase: '', nonce: '', labels: {} };
} );

const state = {
	values: { moap_cdn_provider: 'none', moap_focal_point_enabled: '1' },
	status: {
		provider: 'none',
		cdnConfigured: false,
		serving: false,
		aiAvailable: true,
		focalEnabled: true,
		autoTag: false,
		offloaded: { done: 0, total: 0 },
	},
	tools: [],
};

function renderApp() {
	return render(
		<SettingsProvider initialState={ state } apiFetch={ jest.fn() }>
			<App />
		</SettingsProvider>
	);
}

test( 'shows the dashboard deck by default', () => {
	const { container } = renderApp();
	expect( container.querySelector( '.moap-deck' ) ).not.toBeNull();
} );

test( 'opening the focal card shows the focal detail and sets the hash', () => {
	const { container } = renderApp();
	act( () =>
		container.querySelector( '.moap-fcard[data-open="focal"]' ).click()
	);
	expect(
		container.querySelector( '[data-section="focal"]' )
	).not.toBeNull();
	expect( window.location.hash ).toBe( '#focal' );
} );
