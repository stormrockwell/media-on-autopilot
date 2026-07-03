import { render, screen, act } from '@testing-library/react';
import { SettingsProvider } from '../src/admin/state/SettingsContext';
import { Toggle, Select } from '../src/admin/components/fields';

function wrap( ui, values ) {
	return render(
		<SettingsProvider
			initialState={ { values, status: {}, tools: [] } }
			apiFetch={ jest.fn() }
		>
			{ ui }
		</SettingsProvider>
	);
}

test( 'toggle reflects and updates value', () => {
	wrap( <Toggle name="moap_focal_point_enabled" label="Focal" />, {
		moap_focal_point_enabled: '1',
	} );
	const box = screen.getByRole( 'checkbox' );
	expect( box.checked ).toBe( true );
	act( () => box.click() );
	expect( box.checked ).toBe( false );
} );

test( 'select renders options and current value', () => {
	wrap(
		<Select
			name="moap_cdn_provider"
			options={ [
				[ 'none', 'None' ],
				[ 'bunny', 'Bunny' ],
			] }
		/>,
		{ moap_cdn_provider: 'bunny' }
	);
	expect( screen.getByRole( 'combobox' ).value ).toBe( 'bunny' );
} );
