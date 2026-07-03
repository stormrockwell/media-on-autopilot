import { render, screen, act } from '@testing-library/react';
import {
	SettingsProvider,
	useSettingsContext,
} from '../src/admin/state/SettingsContext';

const initialState = {
	values: { moap_cdn_provider: 'none', moap_bunnycdn_hostname: '' },
	status: { cdnConfigured: false, provider: 'none' },
	tools: [],
};

function Probe() {
	const { values, status, setField, save } = useSettingsContext();
	return (
		<div>
			<span data-testid="provider">{ values.moap_cdn_provider }</span>
			<span data-testid="configured">
				{ String( status.cdnConfigured ) }
			</span>
			<button onClick={ () => setField( 'moap_cdn_provider', 'bunny' ) }>
				set
			</button>
			<button onClick={ () => save( 'cdn' ) }>save</button>
		</div>
	);
}

test( 'seeds from initial state and updates a field', () => {
	render(
		<SettingsProvider initialState={ initialState } apiFetch={ jest.fn() }>
			<Probe />
		</SettingsProvider>
	);
	expect( screen.getByTestId( 'provider' ).textContent ).toBe( 'none' );
	act( () => screen.getByText( 'set' ).click() );
	expect( screen.getByTestId( 'provider' ).textContent ).toBe( 'bunny' );
} );

test( 'save merges returned status', async () => {
	const apiFetch = jest.fn().mockResolvedValue( {
		values: { moap_cdn_provider: 'bunny' },
		status: { cdnConfigured: true, provider: 'bunny' },
		tools: [],
	} );
	render(
		<SettingsProvider initialState={ initialState } apiFetch={ apiFetch }>
			<Probe />
		</SettingsProvider>
	);
	await act( async () => {
		screen.getByText( 'save' ).click();
	} );
	expect( apiFetch ).toHaveBeenCalled();
	expect( screen.getByTestId( 'configured' ).textContent ).toBe( 'true' );
} );
