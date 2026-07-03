import { __ } from '@wordpress/i18n';
import { useSettingsContext } from '../state/SettingsContext';
import {
	Toggle,
	NumberField,
	Select,
	TextField,
	PasswordField,
} from './fields';

const FORMAT_OPTIONS = [
	[
		'auto',
		__(
			'Auto (WebP/AVIF, JPG fallback for opaque images)',
			'media-on-autopilot'
		),
	],
	[ 'webp', __( 'Force WebP', 'media-on-autopilot' ) ],
	[ 'avif', __( 'Force AVIF', 'media-on-autopilot' ) ],
	[ 'off', __( 'Off (resize only)', 'media-on-autopilot' ) ],
];

function Row( { label, small, children } ) {
	return (
		<div className="moap-row">
			<div className="moap-row__label">
				{ label }
				{ small && <small>{ small }</small> }
			</div>
			<div className="moap-row__control">{ children }</div>
		</div>
	);
}

function ProviderCard( { title, onGuide, guideKey, children } ) {
	return (
		<div className="moap-card">
			<div className="moap-card__head">
				{ title }{ ' ' }
				<button
					type="button"
					className="moap-guide"
					onClick={ () => onGuide( guideKey ) }
				>
					{ __( 'Setup guide', 'media-on-autopilot' ) }
				</button>
			</div>
			<div className="moap-card__body">{ children }</div>
		</div>
	);
}

export function ProviderFields( { onGuide } ) {
	const { values } = useSettingsContext();
	const provider = values.moap_cdn_provider || 'none';

	return (
		<>
			<div className="moap-card">
				<div className="moap-card__head">
					{ __( 'Provider', 'media-on-autopilot' ) }
				</div>
				<div className="moap-card__body">
					<Row label={ __( 'CDN provider', 'media-on-autopilot' ) }>
						<Select
							name="moap_cdn_provider"
							options={ [
								[
									'none',
									__(
										'None (serve locally)',
										'media-on-autopilot'
									),
								],
								[
									'bunny',
									__(
										'BunnyCDN (pull zone)',
										'media-on-autopilot'
									),
								],
								[
									'cloudflare',
									__(
										'Cloudflare Images (storage offload)',
										'media-on-autopilot'
									),
								],
							] }
						/>
						<p className="moap-desc">
							{ __(
								'Credentials save even while serving is off.',
								'media-on-autopilot'
							) }
						</p>
					</Row>
				</div>
			</div>

			{ provider === 'bunny' && (
				<ProviderCard
					title={ __( 'BunnyCDN', 'media-on-autopilot' ) }
					onGuide={ onGuide }
					guideKey="bunny"
				>
					<Row
						label={ __(
							'Pull-zone hostname',
							'media-on-autopilot'
						) }
					>
						<TextField
							name="moap_bunnycdn_hostname"
							placeholder="mysite.b-cdn.net"
						/>
					</Row>
					<Row label={ __( 'Image quality', 'media-on-autopilot' ) }>
						<NumberField
							name="moap_bunnycdn_quality"
							min={ 1 }
							max={ 100 }
						/>
					</Row>
					<Row
						label={ __( 'Format strategy', 'media-on-autopilot' ) }
					>
						<Select
							name="moap_bunnycdn_format"
							options={ FORMAT_OPTIONS }
						/>
					</Row>
				</ProviderCard>
			) }

			{ provider === 'cloudflare' && (
				<ProviderCard
					title={ __( 'Cloudflare Images', 'media-on-autopilot' ) }
					onGuide={ onGuide }
					guideKey="cloudflare"
				>
					<Row label={ __( 'Account ID', 'media-on-autopilot' ) }>
						<TextField name="moap_cloudflare_account_id" />
					</Row>
					<Row
						label={ __( 'API token', 'media-on-autopilot' ) }
						small={ __(
							'leave blank to keep saved token',
							'media-on-autopilot'
						) }
					>
						<PasswordField name="moap_cloudflare_api_token" />
					</Row>
					<Row
						label={ __( 'Account hash', 'media-on-autopilot' ) }
						small={ __( 'auto if blank', 'media-on-autopilot' ) }
					>
						<TextField name="moap_cloudflare_account_hash" />
						<p className="description">
							{ __(
								'Leave blank to auto-detect on first upload.',
								'media-on-autopilot'
							) }
						</p>
					</Row>
					<Row label={ __( 'Image quality', 'media-on-autopilot' ) }>
						<NumberField
							name="moap_cloudflare_quality"
							min={ 1 }
							max={ 100 }
						/>
					</Row>
					<Row
						label={ __( 'Format strategy', 'media-on-autopilot' ) }
					>
						<Select
							name="moap_cloudflare_format"
							options={ FORMAT_OPTIONS }
						/>
					</Row>
				</ProviderCard>
			) }

			{ provider !== 'none' && (
				<div className="moap-card">
					<div className="moap-card__head">
						{ __( 'Delivery', 'media-on-autopilot' ) }
					</div>
					<div className="moap-card__body">
						<Row
							label={ __(
								'Serve images through CDN',
								'media-on-autopilot'
							) }
						>
							<Toggle
								name="moap_cdn_serve"
								label={ __(
									'Test on the Test tab first, then flip on.',
									'media-on-autopilot'
								) }
							/>
						</Row>
					</div>
				</div>
			) }
		</>
	);
}
