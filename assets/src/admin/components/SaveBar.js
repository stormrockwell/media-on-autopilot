import { __ } from '@wordpress/i18n';
import { useSettingsContext } from '../state/SettingsContext';

export function SaveBar( { section } ) {
	const { save, saving, savedAt } = useSettingsContext();
	const labels = ( window.moapAdmin && window.moapAdmin.labels ) || {};
	let statusText = '';
	if ( saving[ section ] ) {
		statusText = labels.saving || __( 'Saving…', 'media-on-autopilot' );
	} else if ( savedAt[ section ] ) {
		statusText = labels.saved || __( 'Saved', 'media-on-autopilot' );
	}
	return (
		<>
			<button
				type="button"
				className="button button-primary moap-save"
				onClick={ () => save( section ) }
			>
				{ __( 'Save changes', 'media-on-autopilot' ) }
			</button>
			<span className="moap-save-status" aria-live="polite">
				{ statusText }
			</span>
		</>
	);
}
