import { __ } from '@wordpress/i18n';

function badgeFor( kind, status ) {
	if ( kind === 'focal' ) {
		return status.focalEnabled
			? [ 'ok', __( 'Active', 'media-on-autopilot' ) ]
			: [ 'off', __( 'Off', 'media-on-autopilot' ) ];
	}
	if ( kind === 'ai' ) {
		if ( ! status.aiAvailable ) {
			return [ 'err', __( 'Unavailable', 'media-on-autopilot' ) ];
		}
		return status.autoTag
			? [ 'ok', __( 'On', 'media-on-autopilot' ) ]
			: [ 'off', __( 'Off', 'media-on-autopilot' ) ];
	}
	// cdn
	if ( status.provider === 'none' || ! status.cdnConfigured ) {
		return [ 'off', __( 'Off', 'media-on-autopilot' ) ];
	}
	if ( ! status.serving ) {
		return [ 'warn', __( 'Configured · off', 'media-on-autopilot' ) ];
	}
	return [ 'ok', __( 'Active', 'media-on-autopilot' ) ];
}

export function StatusBadge( { kind, status } ) {
	const [ tone, label ] = badgeFor( kind, status );
	return (
		<span className={ `moap-badge moap-badge--${ tone }` }>
			<span className="moap-badge__dot" />
			{ label }
		</span>
	);
}
