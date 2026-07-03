import { render, screen, act } from '@testing-library/react';
import { Guide } from '../src/admin/components/Guide';

test( 'renders nothing when guideKey is null', () => {
	const { container } = render(
		<Guide guideKey={ null } onClose={ () => {} } />
	);
	expect( container.querySelector( '#moap-lightbox' ) ).toBeNull();
} );

test( 'renders dialog and closes on × click', () => {
	const onClose = jest.fn();
	render( <Guide guideKey="bunny" onClose={ onClose } /> );
	expect( document.querySelector( '#moap-lightbox' ) ).not.toBeNull();
	act( () => screen.getByText( '×' ).click() );
	expect( onClose ).toHaveBeenCalled();
} );
