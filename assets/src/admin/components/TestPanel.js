import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import defaultApiFetch from '@wordpress/api-fetch';

const COPY = {
	ai: {
		head: __( 'Test the model', 'media-on-autopilot' ),
		button: __( 'Run AI test', 'media-on-autopilot' ),
		note: __(
			'Sends one sample image — costs a single vision call.',
			'media-on-autopilot'
		),
		path: '/ai/test',
	},
	cdn: {
		head: __( 'End-to-end delivery test', 'media-on-autopilot' ),
		button: __( 'Run test', 'media-on-autopilot' ),
		note: __(
			'Fetches a resized sample image through your CDN.',
			'media-on-autopilot'
		),
		path: '/cdn/test',
	},
};

/**
 * Accurate, provider-specific cost note for the CDN delivery test. BunnyCDN is a
 * pull zone (it resizes the origin image on the fly — nothing is uploaded);
 * Cloudflare Images stores an uploaded copy.
 *
 * @param {string} provider Active CDN provider key (bunny|cloudflare|none).
 * @return {string} The note shown under the run button.
 */
function cdnNote( provider ) {
	if ( provider === 'bunny' ) {
		return __(
			'Fetches a resized sample image through your pull zone — nothing is uploaded. Uses your pull-zone bandwidth.',
			'media-on-autopilot'
		);
	}
	if ( provider === 'cloudflare' ) {
		return __(
			'Uploads a sample image to Cloudflare, fetches a resized variant, then deletes the test image. Uses your Cloudflare Images quota.',
			'media-on-autopilot'
		);
	}
	return COPY.cdn.note;
}

/**
 * Badge component for pass/fail state.
 *
 * @param {Object}  props
 * @param {boolean} props.ok    Whether the badge is a pass or fail.
 * @param {string}  props.label Badge text.
 */
function Badge( { ok, label } ) {
	return (
		<span
			className={
				'moap-badge ' + ( ok ? 'moap-badge--ok' : 'moap-badge--err' )
			}
		>
			<span className="moap-badge__dot" />
			{ label }
		</span>
	);
}

/**
 * AI preview: two-pane layout with sample image and alt text + tag chips.
 * Mirrors the DOM-building logic in test-ai.js buildPreview/render.
 *
 * @param {Object} props
 * @param {Object} props.data   Response from /ai/test.
 * @param {Object} props.config moapAdmin config object.
 */
function AiPreview( { data, config } ) {
	const labels = config.labels || {};
	const ok =
		data.state === 'ok' || ( ! data.state && data.alt !== undefined );

	return (
		<div className="moap-preview">
			<div className="moap-preview__pane">
				{ ok && config.sampleImage ? (
					<img src={ config.sampleImage } alt="" />
				) : (
					'—'
				) }
			</div>
			<div className="moap-preview__result">
				{ ok ? (
					<>
						{ labels.testPass && (
							<Badge ok={ true } label={ labels.testPass } />
						) }
						{ data.alt !== undefined && (
							<p>
								<strong>{ 'Alt: ' }</strong>
								{ data.alt }
							</p>
						) }
						{ Array.isArray( data.tags ) && (
							<div className="moap-chips">
								{ data.tags.map( ( tag ) => (
									<span key={ tag } className="moap-chip">
										{ tag }
									</span>
								) ) }
							</div>
						) }
						{ ( data.model || data.tokens > 0 ) && (
							<p className="moap-desc">
								{ data.model && (
									<>
										<strong>{ 'Model: ' }</strong>
										{ data.model }
									</>
								) }
								{ data.model && data.tokens > 0 && ' · ' }
								{ data.tokens > 0 &&
									`${ data.tokens.toLocaleString() } tokens` }
							</p>
						) }
					</>
				) : (
					<>
						<p>{ data.message }</p>
						{ config.connectorsUrl && (
							<p className="moap-desc">
								<a
									href={ config.connectorsUrl }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __(
										'Open Settings → Connectors ↗',
										'media-on-autopilot'
									) }
								</a>
							</p>
						) }
					</>
				) }
			</div>
		</div>
	);
}

/**
 * Step glyph map for each status.
 *
 * @type {Object.<string,string>}
 */
const STEP_GLYPH = { ok: '✓', fail: '✗', warn: '⚠' };

/**
 * CDN step-log preview: headline badge, step list, and optional error detail.
 * Replaces the old image-pane layout — the test image is deleted server-side
 * before the response is returned, so there is nothing to preview.
 *
 * @param {Object} props
 * @param {Object} props.data   Response from /cdn/test.
 * @param {Object} props.config moapAdmin config object.
 */
function CdnPreview( { data, config } ) {
	const labels = config.labels || {};
	const ok = data.state === 'ok';
	const badgeLabel = ok
		? labels.testPass || 'Passed'
		: labels.testFail || 'Failed';

	return (
		<div className="moap-cdn-result">
			<Badge ok={ ok } label={ badgeLabel } />{ ' ' }
			{ data.message && <span>{ data.message }</span> }
			{ Array.isArray( data.steps ) && data.steps.length > 0 && (
				<ul className="moap-test-steps">
					{ data.steps.map( ( s, i ) => (
						<li
							key={ i }
							className={ 'moap-test-step is-' + s.status }
						>
							<span className="moap-test-step__glyph">
								{ STEP_GLYPH[ s.status ] || '·' }
							</span>
							{ s.label }
						</li>
					) ) }
				</ul>
			) }
			{ data.detail && <p className="moap-desc">{ data.detail }</p> }
		</div>
	);
}

/**
 * TestPanel runs the AI or CDN connection test and renders the preview result.
 * Ports the DOM logic from test-ai.js and test-cdn.js into React state.
 *
 * @param {Object}   props
 * @param {string}   props.kind     'ai' or 'cdn'.
 * @param {boolean}  props.disabled Whether the run button is disabled.
 * @param {string}   props.hint     Optional hint text shown below the button.
 * @param {string}   props.provider Active CDN provider key (only used when kind is 'cdn').
 * @param {Function} props.apiFetch Injectable apiFetch (default: @wordpress/api-fetch).
 */
export function TestPanel( {
	kind,
	disabled,
	hint,
	provider,
	apiFetch = defaultApiFetch,
} ) {
	const config = window.moapAdmin || {};
	const labels = config.labels || {};
	const copy = COPY[ kind ];
	const note = kind === 'cdn' ? cdnNote( provider ) : copy.note;
	const [ loading, setLoading ] = useState( false );
	const [ result, setResult ] = useState( null );
	const [ error, setError ] = useState( '' );

	async function run() {
		setLoading( true );
		setError( '' );
		setResult( null );
		try {
			const data = await apiFetch( {
				url: config.restBase + copy.path,
				method: 'POST',
				headers: { 'X-WP-Nonce': config.nonce },
			} );
			setResult( data );
		} catch ( e ) {
			setError( ( e && e.message ) || 'Error' );
		} finally {
			setLoading( false );
		}
	}

	return (
		<div className="moap-card">
			<div className="moap-card__head">{ copy.head }</div>
			<div className="moap-card__body">
				<button
					type="button"
					className="button moap-test"
					disabled={ disabled || loading }
					onClick={ run }
				>
					{ loading ? labels.testing || copy.button : copy.button }
				</button>
				{ hint && <span className="moap-test-hint">{ hint }</span> }
				<p className="moap-desc">{ note }</p>
				<div className="moap-preview-region">
					{ loading && (
						<div className="moap-preview">
							<div className="moap-preview__pane">
								<span className="moap-spinner" />
							</div>
							<div className="moap-preview__result">
								<p>{ labels.testing }</p>
							</div>
						</div>
					) }
					{ error && ! loading && (
						<div className="moap-preview">
							<div className="moap-preview__pane">{ '—' }</div>
							<div className="moap-preview__result">
								<p>{ error }</p>
							</div>
						</div>
					) }
					{ result && ! loading && kind === 'ai' && (
						<AiPreview data={ result } config={ config } />
					) }
					{ result && ! loading && kind === 'cdn' && (
						<CdnPreview data={ result } config={ config } />
					) }
				</div>
			</div>
		</div>
	);
}
