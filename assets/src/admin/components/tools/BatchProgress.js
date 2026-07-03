import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import defaultApiFetch from '@wordpress/api-fetch';
import {
	computeProgressText,
	computeCounts,
	computeSummaryText,
	isTerminal,
} from '../../../batch/progress';

export function BatchProgress( {
	slug,
	showSummary,
	startEndpoint,
	options,
	startLabel,
	apiFetch = defaultApiFetch,
} ) {
	const [ state, setState ] = useState( null );
	const [ starting, setStarting ] = useState( false );
	const timer = useRef( null );
	const mounted = useRef( true );

	const tick = useCallback( async () => {
		let next;
		try {
			next = await apiFetch( { path: `moap/v1/batch/${ slug }` } );
		} catch ( e ) {
			return;
		}
		if ( ! mounted.current ) {
			return;
		}
		setState( next );
		if ( ! isTerminal( next ) ) {
			timer.current = window.setTimeout( tick, 2000 );
		}
	}, [ apiFetch, slug ] );

	useEffect( () => {
		mounted.current = true;
		tick();
		return () => {
			mounted.current = false;
			window.clearTimeout( timer.current );
		};
	}, [ tick ] );

	async function start() {
		if ( starting ) {
			return;
		}
		setStarting( true );
		try {
			await apiFetch( {
				path: startEndpoint,
				method: 'POST',
				data: options,
			} );
			window.clearTimeout( timer.current );
			await tick();
		} finally {
			if ( mounted.current ) {
				setStarting( false );
			}
		}
	}

	async function cancel() {
		await apiFetch( {
			path: `moap/v1/batch/${ slug }/cancel`,
			method: 'POST',
		} );
	}

	const active = state && ! isTerminal( state );
	const total = ( state && state.total ) || 0;
	const pct =
		active && total > 0
			? Math.round( ( state.completed / total ) * 100 )
			: 0;
	const counts = active ? computeCounts( state ) : null;

	return (
		<>
			<p>
				<button
					type="button"
					className="button moap-batch-start"
					disabled={ starting }
					onClick={ start }
				>
					{ startLabel }
				</button>
			</p>
			<div
				className="moap-batch"
				data-moap-batch={ slug }
				data-moap-summary={ showSummary ? 'on' : 'off' }
			>
				<div
					className={ `moap-batch-progress${
						active ? ' is-active' : ''
					}` }
				>
					<progress
						className="moap-batch-progress__bar"
						max="100"
						value={ pct }
					/>{ ' ' }
					<span
						className="moap-batch-progress__text"
						aria-live="polite"
					>
						{ active ? computeProgressText( state ) : '' }
					</span>{ ' ' }
					{ state && state.status === 'running' && (
						<button
							type="button"
							className="button moap-batch-progress__cancel"
							onClick={ cancel }
						>
							{ __( 'Cancel', 'media-on-autopilot' ) }
						</button>
					) }
				</div>
				{ active && (
					<p className="moap-batch-note">
						{ __(
							'You can safely leave this page; the process keeps running in the background.',
							'media-on-autopilot'
						) }
					</p>
				) }
				<p className="moap-batch-summary">
					{ ! active && showSummary && state
						? computeSummaryText( state.lastRun )
						: '' }
				</p>
				<p className="moap-batch-counts">
					{ active && counts
						? `✓ ${ counts.written } done  ✗ ${ counts.failed } failed  ⤼ ${ counts.skipped } skipped`
						: '' }
				</p>
			</div>
		</>
	);
}
