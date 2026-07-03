import { __ } from '@wordpress/i18n';

export function Banner( { status } ) {
	let title = '';
	let sub = '';

	if (
		status.provider !== 'none' &&
		status.cdnConfigured &&
		! status.serving
	) {
		title = __(
			'CDN is configured but not serving yet.',
			'media-on-autopilot'
		);
		sub = __(
			'Run a delivery test, then turn on "Serve images through CDN".',
			'media-on-autopilot'
		);
	} else if ( ! status.aiAvailable ) {
		title = __( 'AI tagging is unavailable.', 'media-on-autopilot' );
		sub = __(
			'AI tagging requires WordPress 7.0 or later with a connected AI provider.',
			'media-on-autopilot'
		);
	}

	if ( ! title ) {
		return null;
	}

	return (
		<div className="moap-banner">
			<span className="moap-badge moap-badge--warn">
				<span className="moap-badge__dot" />
				{ __( 'Action', 'media-on-autopilot' ) }
			</span>
			<div className="moap-banner__body">
				<div className="moap-banner__title">{ title }</div>
				<div className="moap-banner__sub">{ sub }</div>
			</div>
		</div>
	);
}
