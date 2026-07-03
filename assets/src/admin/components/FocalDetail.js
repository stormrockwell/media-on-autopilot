import { __ } from '@wordpress/i18n';
import { Breadcrumb } from './SubTabs';
import { Toggle } from './fields';
import { SaveBar } from './SaveBar';

export function FocalDetail( { onHome, onGotoAi } ) {
	return (
		<div className="moap-detail is-active">
			<Breadcrumb
				title={ __( 'Focal points', 'media-on-autopilot' ) }
				onHome={ onHome }
			/>
			<div className="moap-screen" data-section="focal">
				<div className="moap-card">
					<div className="moap-card__head">
						{ __( 'Focal points', 'media-on-autopilot' ) }
					</div>
					<div className="moap-card__body">
						<div className="moap-row">
							<div className="moap-row__label">
								{ __(
									'Focal-point cropping',
									'media-on-autopilot'
								) }
							</div>
							<div className="moap-row__control">
								<Toggle
									name="moap_focal_point_enabled"
									label={ __(
										'Use focal points for server-side cropping and front-end positioning',
										'media-on-autopilot'
									) }
									desc={ __(
										"On by default. Turn off to fall back to WordPress's default cropping everywhere.",
										'media-on-autopilot'
									) }
								/>
							</div>
						</div>
						<div className="moap-row">
							<div className="moap-row__label">
								{ __( 'Manual editing', 'media-on-autopilot' ) }
							</div>
							<div className="moap-row__control">
								<p className="moap-desc">
									{ __(
										'Open any image in the media library to fine-tune its focal point by hand.',
										'media-on-autopilot'
									) }
								</p>
								<p className="moap-desc">
									{ __(
										'Focal points are detected automatically on upload —',
										'media-on-autopilot'
									) }
									{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
									<a
										className="moap-goto-ai"
										href="#"
										onClick={ ( e ) => {
											e.preventDefault();
											onGotoAi();
										} }
									>
										{ __(
											'see AI tagging',
											'media-on-autopilot'
										) }
									</a>{ ' ' }
									{ __(
										'for how that works and how to retag existing images.',
										'media-on-autopilot'
									) }
								</p>
							</div>
						</div>
					</div>
				</div>
				<SaveBar section="focal" />
			</div>
		</div>
	);
}
