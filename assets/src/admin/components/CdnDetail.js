import { __ } from '@wordpress/i18n';
import { useSettingsContext } from '../state/SettingsContext';
import { Breadcrumb, SubTabs } from './SubTabs';
import { ProviderFields } from './ProviderFields';
import { SaveBar } from './SaveBar';
import { TestPanel } from './TestPanel';
import { ToolsPanel } from './tools/ToolsPanel';

export function CdnDetail( { onHome, subTab, onSubTab, onGuide } ) {
	const { status, tools } = useSettingsContext();
	const testDisabled = ! status.cdnConfigured || status.provider === 'none';
	// Only the active, configured provider contributes CDN tools (Cloudflare
	// offload). With none available — e.g. BunnyCDN — the Tools tab is hidden
	// rather than showing an empty panel.
	const hasTools = ( tools || [] ).some(
		( t ) => t.group === 'cdn' && t.available
	);
	let active = subTab || 'config';
	if ( active === 'tools' && ! hasTools ) {
		active = 'config';
	}

	const tabs = [
		[ 'config', __( 'Configuration', 'media-on-autopilot' ) ],
		[ 'test', __( 'Test', 'media-on-autopilot' ) ],
	];
	if ( hasTools ) {
		tabs.push( [ 'tools', __( 'Tools', 'media-on-autopilot' ) ] );
	}

	return (
		<div className="moap-detail is-active">
			<Breadcrumb
				title={ __( 'CDN delivery', 'media-on-autopilot' ) }
				onHome={ onHome }
			/>
			<SubTabs tabs={ tabs } subTab={ active } onSelect={ onSubTab } />
			{ active === 'config' && (
				<div className="moap-subpanel is-active">
					<div className="moap-screen" data-section="cdn">
						<ProviderFields onGuide={ onGuide } />
						<SaveBar section="cdn" />
					</div>
				</div>
			) }
			{ active === 'test' && (
				<div className="moap-subpanel is-active">
					<TestPanel
						kind="cdn"
						disabled={ testDisabled }
						provider={ status.provider }
						hint={
							testDisabled
								? __(
										'Configure a CDN provider first',
										'media-on-autopilot'
								  )
								: ''
						}
					/>
				</div>
			) }
			{ active === 'tools' && (
				<div className="moap-subpanel is-active">
					<ToolsPanel group="cdn" />
				</div>
			) }
		</div>
	);
}
