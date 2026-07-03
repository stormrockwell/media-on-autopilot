import { __ } from '@wordpress/i18n';
import { useSettingsContext } from '../state/SettingsContext';
import { Breadcrumb, SubTabs } from './SubTabs';
import { Toggle, NumberField } from './fields';
import { SaveBar } from './SaveBar';
import { TestPanel } from './TestPanel';
import { ToolsPanel } from './tools/ToolsPanel';

export function AiDetail( { onHome, subTab, onSubTab, onGuide } ) {
	const { status } = useSettingsContext();
	const active = subTab || 'config';
	return (
		<div className="moap-detail is-active">
			<Breadcrumb
				title={ __( 'AI tagging', 'media-on-autopilot' ) }
				onHome={ onHome }
			/>
			<SubTabs
				tabs={ [
					[ 'config', __( 'Configuration', 'media-on-autopilot' ) ],
					[ 'test', __( 'Test', 'media-on-autopilot' ) ],
					[ 'tools', __( 'Tools', 'media-on-autopilot' ) ],
				] }
				subTab={ active }
				onSelect={ onSubTab }
			/>
			{ active === 'config' && (
				<div className="moap-subpanel is-active">
					<div className="moap-screen" data-section="ai">
						<div className="moap-card">
							<div className="moap-card__head">
								{ __( 'AI tagging', 'media-on-autopilot' ) }{ ' ' }
								<button
									type="button"
									className="moap-guide"
									onClick={ () => onGuide( 'ai' ) }
								>
									{ __(
										'Setup guide',
										'media-on-autopilot'
									) }
								</button>
							</div>
							<div className="moap-card__body">
								<p className="moap-desc">
									{ __(
										'On upload, a single vision call writes alt text, suggests taxonomy tags, and detects a focal point used for cropping.',
										'media-on-autopilot'
									) }
								</p>
								<div className="moap-row">
									<div className="moap-row__label">
										{ __(
											'Auto-tag on upload',
											'media-on-autopilot'
										) }
									</div>
									<div className="moap-row__control moap-toggle">
										<Toggle
											name="moap_ai_tagging_auto"
											label={ __(
												'Generate alt text, tags, and focal points automatically for newly uploaded images.',
												'media-on-autopilot'
											) }
										/>
									</div>
								</div>
								<div className="moap-row">
									<div className="moap-row__label">
										{ __(
											'Number of tags per image',
											'media-on-autopilot'
										) }
									</div>
									<div className="moap-row__control">
										<NumberField
											name="moap_ai_tagging_target_tag_count_option"
											min={ 1 }
											max={ 50 }
										/>
									</div>
								</div>
							</div>
						</div>
						<SaveBar section="ai" />
					</div>
				</div>
			) }
			{ active === 'test' && (
				<div className="moap-subpanel is-active">
					<TestPanel
						kind="ai"
						disabled={ ! status.aiAvailable }
						hint={
							status.aiAvailable
								? ''
								: __(
										'AI provider not available',
										'media-on-autopilot'
								  )
						}
					/>
				</div>
			) }
			{ active === 'tools' && (
				<div className="moap-subpanel is-active">
					<ToolsPanel group="ai" />
				</div>
			) }
		</div>
	);
}
