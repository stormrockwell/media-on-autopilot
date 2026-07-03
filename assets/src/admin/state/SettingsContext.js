import {
	createContext,
	useContext,
	useState,
	useCallback,
} from '@wordpress/element';
import defaultApiFetch from '@wordpress/api-fetch';

export const SECTION_FIELDS = {
	focal: [ 'moap_focal_point_enabled' ],
	ai: [ 'moap_ai_tagging_auto', 'moap_ai_tagging_target_tag_count_option' ],
	cdn: [
		'moap_cdn_provider',
		'moap_cdn_serve',
		'moap_bunnycdn_hostname',
		'moap_bunnycdn_quality',
		'moap_bunnycdn_format',
		'moap_cloudflare_account_id',
		'moap_cloudflare_api_token',
		'moap_cloudflare_account_hash',
		'moap_cloudflare_quality',
		'moap_cloudflare_format',
	],
};

const SettingsCtx = createContext( null );

export function useSettingsContext() {
	const ctx = useContext( SettingsCtx );
	if ( ! ctx ) {
		throw new Error(
			'useSettingsContext must be used within SettingsProvider'
		);
	}
	return ctx;
}

export function SettingsProvider( {
	children,
	initialState = ( window.moapAdmin && window.moapAdmin.state ) || {},
	apiFetch = defaultApiFetch,
} ) {
	const config = window.moapAdmin || {};
	const [ values, setValues ] = useState( initialState.values || {} );
	const [ status, setStatus ] = useState( initialState.status || {} );
	const [ secretsSet, setSecretsSet ] = useState(
		initialState.secretsSet || {}
	);
	const [ tools, setTools ] = useState( initialState.tools || [] );
	const [ saving, setSaving ] = useState( {} );
	const [ savedAt, setSavedAt ] = useState( {} );

	const setField = useCallback( ( name, value ) => {
		setValues( ( prev ) => ( { ...prev, [ name ]: value } ) );
	}, [] );

	const save = useCallback(
		async ( section ) => {
			const names = SECTION_FIELDS[ section ] || [];
			const data = {};
			names.forEach( ( name ) => {
				data[ name ] = values[ name ];
			} );
			setSaving( ( p ) => ( { ...p, [ section ]: true } ) );
			try {
				const next = await apiFetch( {
					url: `${ config.restBase }/settings/${ section }`,
					method: 'POST',
					headers: { 'X-WP-Nonce': config.nonce },
					data,
				} );
				if ( next.values ) {
					setValues( ( prev ) => ( { ...prev, ...next.values } ) );
				}
				if ( next.status ) {
					setStatus( next.status );
				}
				if ( next.secretsSet ) {
					setSecretsSet( next.secretsSet );
				}
				if ( next.tools ) {
					setTools( next.tools );
				}
				setSavedAt( ( p ) => ( { ...p, [ section ]: Date.now() } ) );
			} finally {
				setSaving( ( p ) => ( { ...p, [ section ]: false } ) );
			}
		},
		[ values, apiFetch, config.restBase, config.nonce ]
	);

	const value = {
		values,
		status,
		secretsSet,
		tools,
		setField,
		save,
		saving,
		savedAt,
	};
	return (
		<SettingsCtx.Provider value={ value }>
			{ children }
		</SettingsCtx.Provider>
	);
}
