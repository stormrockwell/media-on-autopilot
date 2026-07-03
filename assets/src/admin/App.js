import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSettingsContext } from './state/SettingsContext';
import { useHashRoute } from './state/useHashRoute';
import { Banner } from './components/Banner';
import { Dashboard } from './components/Dashboard';
import { FocalDetail } from './components/FocalDetail';
import { AiDetail } from './components/AiDetail';
import { CdnDetail } from './components/CdnDetail';
import { Guide } from './components/Guide';

export function App() {
	const { status } = useSettingsContext();
	const { view, subTab, setView, setSubTab } = useHashRoute();
	const [ guideKey, setGuideKey ] = useState( null );
	const home = () => setView( 'dashboard' );

	return (
		<>
			<div className="moap-top">
				<span className="moap-top__logo" aria-hidden="true">
					📷
				</span>
				<h1 className="moap-top__title">
					{ __( 'Media on Autopilot', 'media-on-autopilot' ) }
				</h1>
			</div>

			<Banner status={ status } />

			{ view === 'dashboard' && <Dashboard onOpen={ setView } /> }
			{ view === 'focal' && (
				<FocalDetail
					onHome={ home }
					onGotoAi={ () => setView( 'ai' ) }
				/>
			) }
			{ view === 'ai' && (
				<AiDetail
					onHome={ home }
					subTab={ subTab }
					onSubTab={ setSubTab }
					onGuide={ setGuideKey }
				/>
			) }
			{ view === 'cdn' && (
				<CdnDetail
					onHome={ home }
					subTab={ subTab }
					onSubTab={ setSubTab }
					onGuide={ setGuideKey }
				/>
			) }

			<Guide
				guideKey={ guideKey }
				onClose={ () => setGuideKey( null ) }
			/>
		</>
	);
}
