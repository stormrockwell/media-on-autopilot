import { render, act } from '@testing-library/react';
import { SettingsProvider } from '../src/admin/state/SettingsContext';
import { ProviderFields } from '../src/admin/components/ProviderFields';

function wrap( values ) {
	return render(
		<SettingsProvider
			initialState={ { values, status: {}, tools: [] } }
			apiFetch={ jest.fn() }
		>
			<ProviderFields onGuide={ () => {} } />
		</SettingsProvider>
	);
}

test( 'shows bunny fields and hides cloudflare when provider=bunny', () => {
	const { container } = wrap( {
		moap_cdn_provider: 'bunny',
		moap_bunnycdn_hostname: '',
	} );
	expect(
		container.querySelector( '[name="moap_bunnycdn_hostname"]' )
	).not.toBeNull();
	expect(
		container.querySelector( '[name="moap_cloudflare_account_id"]' )
	).toBeNull();
} );

test( 'hides delivery toggle when provider=none', () => {
	const { container } = wrap( { moap_cdn_provider: 'none' } );
	expect( container.querySelector( '[name="moap_cdn_serve"]' ) ).toBeNull();
} );

test( 'switching provider swaps the field group', () => {
	const { container } = wrap( {
		moap_cdn_provider: 'bunny',
		moap_cloudflare_account_id: '',
	} );
	const select = container.querySelector(
		'select[name="moap_cdn_provider"]'
	);
	act( () => {
		const setter = Object.getOwnPropertyDescriptor(
			window.HTMLSelectElement.prototype,
			'value'
		).set;
		setter.call( select, 'cloudflare' );
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
	expect(
		container.querySelector( '[name="moap_cloudflare_account_id"]' )
	).not.toBeNull();
} );
