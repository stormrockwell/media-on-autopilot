import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Per-guide step definitions, ported verbatim from assets/src/admin/lightbox.js.
 * Each step has a heading (h) and a body paragraph (b).
 */
const GUIDES = {
	cloudflare: {
		title: 'Set up Cloudflare Images',
		steps: [
			{
				h: '1. Create an API token',
				b: 'In Cloudflare, create an API token with the Images permission set to Read & Edit (Account API tokens → Create token). This requires a Cloudflare Images subscription.',
				img: 'cf-api-token.png',
				link: {
					href: 'https://www.cloudflare.com/products/images/',
					label: 'About Cloudflare Images (paid subscription required) ↗',
				},
			},
			{
				h: '2. Add your credentials',
				b: 'Paste your Account ID and the API token into the Cloudflare Images fields on the Configuration tab, then Save. Leave Account hash blank to auto-detect on first upload, and leave "Serve images through CDN" off for now.',
				img: 'cf-settings.png',
			},
			{
				h: '3. Run the delivery test',
				b: 'On the Test tab, run the end-to-end delivery test. For Cloudflare it uploads a sample image, fetches a resized variant, then deletes the test image — confirm each step passes before going live.',
				img: 'cf-test.png',
			},
			{
				h: '4. Go live',
				b: 'Once the test passes, turn on "Serve images through CDN" on the Configuration tab and Save. Local originals are always kept; use the Tools tab to offload your existing library to Cloudflare.',
				img: 'cdn-serve.png',
			},
		],
	},
	bunny: {
		title: 'Set up BunnyCDN',
		steps: [
			{
				h: '1. Create a Pull Zone',
				b: 'In the Bunny dashboard, add a Pull Zone and pick "Origin URL" as the origin type. Set the Origin URL to your site:',
				code: 'homeUrl',
				img: 'bunny-pull-zone.png',
				link: {
					href: 'https://dash.bunny.net/cdn/add',
					label: 'Open Bunny → Add Pull Zone ↗',
				},
			},
			{
				h: '2. Turn on the Optimizer',
				b: 'Open the Optimizer tab for your Pull Zone and click "Turn on Bunny Optimizer." This enables the on-the-fly resizing and WebP/AVIF conversion the plugin relies on.',
				img: 'bunny-optimizer.png',
			},
			{
				h: '3. Set Smart Image Optimization',
				b: 'Enable or disable Smart Image Optimization to suit your project. When on, Bunny auto-resizes and compresses to your chosen desktop/mobile widths and quality.',
				img: 'bunny-smart-resize.png',
			},
			{
				h: '4. Add the hostname in the plugin',
				b: 'Copy your Pull Zone hostname (ends in .b-cdn.net) and paste it into the Pull-zone hostname field on the Configuration tab, then Save. Leave "Serve images through CDN" off for now.',
				img: 'bunny-set-pull-zone.png',
			},
			{
				h: '5. Run the delivery test',
				b: 'On the Test tab, run the end-to-end delivery test. It requests a resized sample image through your pull zone and reports each step — confirm it passes before going live.',
				img: 'bunny-test.png',
			},
			{
				h: '6. Go live',
				b: 'Once the test passes, turn on "Serve images through CDN" on the Configuration tab and Save. Your images now serve through BunnyCDN.',
				img: 'cdn-serve.png',
			},
		],
	},
	ai: {
		title: 'Set up AI tagging',
		steps: [
			{
				h: '1. Connect an AI provider',
				b: "Media on Autopilot uses your site's built-in AI connector. In Settings → Connectors, install a provider (OpenAI, Anthropic, or Google) and paste its API key",
				img: 'ai-connect.png',
				link: {
					to: 'connectorsUrl',
					label: 'Open Settings → Connectors ↗',
				},
			},
			{
				h: '2. Test the model',
				b: 'On the Test tab, click “Run AI test.” It sends one sample image and shows the alt text and tags the model returns — a quick way to confirm your connector works. Tip: turn on “Auto-tag on upload” (Configuration tab) so new uploads are tagged automatically.',
				img: 'ai-test.png',
			},
			{
				h: '3. Retag existing media',
				b: 'To tag images you already have, open the Tools tab, choose which fields to write (alt text, tags, focal points), and click “Retag existing media.” It runs in the background and keeps existing values unless you choose to overwrite.',
				img: 'ai-retag.png',
			},
		],
	},
};

/**
 * Guide lightbox component. Renders a multi-step walkthrough dialog for a
 * named guide key ('ai', 'bunny', 'cloudflare'). Renders nothing when guideKey
 * is null.
 *
 * @param {Object}      props
 * @param {string|null} props.guideKey The guide to display, or null to hide.
 * @param {Function}    props.onClose  Called when the dialog should close.
 */
export function Guide( { guideKey, onClose } ) {
	const [ step, setStep ] = useState( 0 );

	// Reset to step 0 when the guide key changes.
	useEffect( () => {
		setStep( 0 );
	}, [ guideKey ] );

	// Close on Escape key.
	useEffect( () => {
		if ( ! guideKey ) {
			return undefined;
		}
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ guideKey, onClose ] );

	if ( ! guideKey || ! GUIDES[ guideKey ] ) {
		return null;
	}

	const guide = GUIDES[ guideKey ];
	const config = window.moapAdmin || {};
	const steps = guide.steps;
	const current = steps[ step ];
	const isFirst = step === 0;
	const isLast = step === steps.length - 1;
	const linkHref =
		current.link &&
		( current.link.to ? config[ current.link.to ] : current.link.href );
	const codeValue = current.code && config[ current.code ];

	return (
		// eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
		<div
			id="moap-lightbox"
			role="dialog"
			aria-modal="true"
			onClick={ ( e ) => {
				if ( e.target.id === 'moap-lightbox' ) {
					onClose();
				}
			} }
			onKeyDown={ ( e ) => {
				if ( e.key === 'Escape' && e.target.id === 'moap-lightbox' ) {
					onClose();
				}
			} }
		>
			<div className="moap-lightbox__inner">
				<header className="moap-lightbox__header">
					<h3 className="moap-lightbox__title">{ guide.title }</h3>
					<button
						className="moap-lightbox__close"
						onClick={ onClose }
					>
						×
					</button>
				</header>
				<div className="moap-lightbox__step">
					{ current.img && (
						<div className="moap-lightbox__stepimg">
							<img
								src={ `${ config.pluginUrl }assets/guide/${ current.img }` }
								alt=""
							/>
						</div>
					) }
					<h4>{ current.h }</h4>
					<p className="moap-lightbox__stepdesc">{ current.b }</p>
					{ codeValue && (
						<p className="moap-lightbox__stepcode">
							<code>{ codeValue }</code>
						</p>
					) }
					{ current.link && linkHref && (
						<p className="moap-lightbox__steplink">
							<a
								href={ linkHref }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ current.link.label }
							</a>
						</p>
					) }
				</div>
				<footer className="moap-lightbox__footer">
					<div className="moap-lightbox__dots">
						{ steps.map( ( _, i ) => (
							<span
								key={ i }
								className={ `moap-lightbox__dot${
									i === step ? ' moap-lightbox__dot--on' : ''
								}` }
							/>
						) ) }
					</div>
					<div>
						<button
							className="button moap-lightbox__back"
							disabled={ isFirst }
							onClick={ () =>
								setStep( ( s ) => Math.max( 0, s - 1 ) )
							}
						>
							{ __( 'Back', 'media-on-autopilot' ) }
						</button>{ ' ' }
						<button
							className="button button-primary moap-lightbox__next"
							onClick={ () =>
								isLast ? onClose() : setStep( ( s ) => s + 1 )
							}
						>
							{ isLast
								? __( 'Done', 'media-on-autopilot' )
								: __( 'Next', 'media-on-autopilot' ) }
						</button>
					</div>
				</footer>
			</div>
		</div>
	);
}
