import { __, sprintf } from '@wordpress/i18n';
import { useSettingsContext } from '../state/SettingsContext';
import { StatusBadge } from './StatusBadge';
import { FeedbackCard } from './FeedbackCard';

const CARDS = [
	{
		key: 'focal',
		icon: '◎',
		iconClass: 'moap-fcard__icon--focal',
		title: __( 'Focal points', 'media-on-autopilot' ),
		text: __(
			'Smart cropping that keeps the subject in frame at any size.',
			'media-on-autopilot'
		),
	},
	{
		key: 'ai',
		icon: '✨',
		iconClass: 'moap-fcard__icon--ai',
		title: __( 'AI tagging', 'media-on-autopilot' ),
		text: __(
			'Auto alt text, taxonomy tags, and focal points on upload.',
			'media-on-autopilot'
		),
	},
	{
		key: 'cdn',
		icon: '☁',
		iconClass: 'moap-fcard__icon--cdn',
		title: __( 'CDN delivery', 'media-on-autopilot' ),
		text: __(
			'Serve resized, optimized images from Cloudflare or BunnyCDN.',
			'media-on-autopilot'
		),
	},
];

export function Dashboard( { onOpen } ) {
	const { status } = useSettingsContext();
	const offloaded = status.offloaded || { done: 0, total: 0 };
	const offloadLine =
		status.provider === 'cloudflare' && offloaded.total > 0
			? sprintf(
					/* translators: 1: done count, 2: total count */
					__(
						'%1$s / %2$s offloaded to Cloudflare',
						'media-on-autopilot'
					),
					offloaded.done,
					offloaded.total
			  )
			: '';

	return (
		<>
			<div className="moap-deck">
				{ CARDS.map( ( card ) => (
					<div
						key={ card.key }
						className="moap-fcard"
						data-open={ card.key }
						role="button"
						tabIndex={ 0 }
						onClick={ () => onOpen( card.key ) }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								e.preventDefault();
								onOpen( card.key );
							}
						} }
					>
						<div
							className={ `moap-fcard__icon ${ card.iconClass }` }
						>
							{ card.icon }
						</div>
						<h3>{ card.title }</h3>
						<p>{ card.text }</p>
						{ card.key === 'cdn' && offloadLine && (
							<div className="moap-fcard__offload">
								{ offloadLine }
							</div>
						) }
						<div className="moap-fcard__foot">
							<StatusBadge kind={ card.key } status={ status } />
							<span className="moap-fcard__go">
								{ __( 'Open →', 'media-on-autopilot' ) }
							</span>
						</div>
					</div>
				) ) }
			</div>
			<FeedbackCard />
		</>
	);
}
