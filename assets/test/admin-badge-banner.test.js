import { render } from '@testing-library/react';
import { StatusBadge } from '../src/admin/components/StatusBadge';
import { Banner } from '../src/admin/components/Banner';

test( 'cdn badge shows configured-off warning', () => {
	const { container } = render(
		<StatusBadge
			kind="cdn"
			status={ {
				provider: 'bunny',
				cdnConfigured: true,
				serving: false,
			} }
		/>
	);
	expect( container.querySelector( '.moap-badge--warn' ) ).not.toBeNull();
	expect( container.textContent ).toContain( 'Configured' );
} );

test( 'ai badge shows unavailable', () => {
	const { container } = render(
		<StatusBadge kind="ai" status={ { aiAvailable: false } } />
	);
	expect( container.querySelector( '.moap-badge--err' ) ).not.toBeNull();
} );

test( 'banner renders nothing when healthy', () => {
	const { container } = render(
		<Banner
			status={ {
				provider: 'none',
				cdnConfigured: false,
				serving: false,
				aiAvailable: true,
			} }
		/>
	);
	expect( container.querySelector( '.moap-banner' ) ).toBeNull();
} );

test( 'banner warns when configured but not serving', () => {
	const { container } = render(
		<Banner
			status={ {
				provider: 'bunny',
				cdnConfigured: true,
				serving: false,
				aiAvailable: true,
			} }
		/>
	);
	expect( container.querySelector( '.moap-banner' ) ).not.toBeNull();
} );
