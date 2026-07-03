import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSettingsContext } from '../../state/SettingsContext';
import { BatchProgress } from './BatchProgress';

function ToolCard( { descriptor } ) {
	const initial = {};
	( descriptor.options || [] ).forEach( ( o ) => {
		initial[ o.key ] = o.default;
	} );
	const [ opts, setOpts ] = useState( initial );

	const options = descriptor.options || [];
	const checks = options.filter( ( o ) => o.type !== 'toggle' );
	const toggles = options.filter( ( o ) => o.type === 'toggle' );
	const optionId = ( key ) => `moap-opt-${ descriptor.slug }-${ key }`;
	const onToggle = ( key ) => ( e ) =>
		setOpts( ( p ) => ( { ...p, [ key ]: e.target.checked } ) );

	return (
		<div className="moap-card moap-tool">
			<div className="moap-card__head">{ descriptor.title }</div>
			<div className="moap-card__body">
				<p className="moap-desc">{ descriptor.description }</p>
				{ descriptor.syncedLine && (
					<p className="moap-tool__synced">
						{ descriptor.syncedLine }
					</p>
				) }
				{ checks.length > 0 && (
					<div className="moap-checks">
						{ checks.map( ( o ) => (
							<label
								key={ o.key }
								className="moap-check"
								htmlFor={ optionId( o.key ) }
							>
								<input
									id={ optionId( o.key ) }
									type="checkbox"
									checked={ !! opts[ o.key ] }
									onChange={ onToggle( o.key ) }
								/>{ ' ' }
								<span>{ o.label }</span>
							</label>
						) ) }
					</div>
				) }
				{ toggles.map( ( o ) => (
					<label
						key={ o.key }
						className="moap-toggle moap-tool__option"
						htmlFor={ optionId( o.key ) }
					>
						<input
							id={ optionId( o.key ) }
							type="checkbox"
							checked={ !! opts[ o.key ] }
							onChange={ onToggle( o.key ) }
						/>{ ' ' }
						<span className="moap-toggle__label">{ o.label }</span>
					</label>
				) ) }
				<BatchProgress
					slug={ descriptor.slug }
					showSummary={ descriptor.showSummary }
					startEndpoint={ descriptor.startEndpoint }
					options={ opts }
					startLabel={ descriptor.title }
				/>
			</div>
		</div>
	);
}

export function ToolsPanel( { group } ) {
	const { tools } = useSettingsContext();
	const shown = ( tools || [] ).filter(
		( t ) => t.group === group && t.available
	);
	if ( ! shown.length ) {
		return (
			<div className="moap-tools-hint">
				<p className="moap-desc">
					{ group === 'cdn'
						? __(
								'Select and configure Cloudflare to offload images.',
								'media-on-autopilot'
						  )
						: __( 'No tools available.', 'media-on-autopilot' ) }
				</p>
			</div>
		);
	}
	return (
		<>
			{ shown.map( ( t ) => (
				<ToolCard key={ t.slug } descriptor={ t } />
			) ) }
		</>
	);
}
