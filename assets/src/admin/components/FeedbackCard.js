import { __ } from '@wordpress/i18n';

const REPO = 'https://github.com/stormrockwell/media-on-autopilot';
const NEW_ISSUE = `${ REPO }/issues/new`;

export function FeedbackCard() {
	return (
		<div className="moap-feedback">
			<div className="moap-feedback__text">
				<h3>
					{ __(
						'Found a problem or have an idea?',
						'media-on-autopilot'
					) }
				</h3>
				<p>
					{ __(
						'Report bugs and request features on GitHub. We read every one.',
						'media-on-autopilot'
					) }
				</p>
			</div>
			<div className="moap-feedback__actions">
				<a
					className="button"
					href={ `${ NEW_ISSUE }?template=bug_report.yml` }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Report a bug', 'media-on-autopilot' ) }
				</a>
				<a
					className="button button-primary"
					href={ `${ NEW_ISSUE }?template=feature_request.yml` }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Request a feature', 'media-on-autopilot' ) }
				</a>
			</div>
		</div>
	);
}
