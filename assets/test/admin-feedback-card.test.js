import { render, screen } from '@testing-library/react';
import { FeedbackCard } from '../src/admin/components/FeedbackCard';

describe( 'FeedbackCard', () => {
	it( 'renders bug and feature links to GitHub issue templates', () => {
		render( <FeedbackCard /> );

		const bug = screen.getByRole( 'link', { name: /report a bug/i } );
		expect( bug ).toHaveAttribute(
			'href',
			'https://github.com/stormrockwell/media-on-autopilot/issues/new?template=bug_report.yml'
		);

		const feature = screen.getByRole( 'link', {
			name: /request a feature/i,
		} );
		expect( feature ).toHaveAttribute(
			'href',
			'https://github.com/stormrockwell/media-on-autopilot/issues/new?template=feature_request.yml'
		);
	} );

	it( 'opens links in a new tab safely', () => {
		render( <FeedbackCard /> );
		const bug = screen.getByRole( 'link', { name: /report a bug/i } );
		expect( bug ).toHaveAttribute( 'target', '_blank' );
		expect( bug ).toHaveAttribute( 'rel', 'noopener noreferrer' );
	} );
} );
