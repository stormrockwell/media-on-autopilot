import { createRoot } from '@wordpress/element';
import { SettingsProvider } from './state/SettingsContext';
import { App } from './App';
import './style.css';

const root = document.getElementById( 'moap-settings-root' );
if ( root ) {
	createRoot( root ).render(
		<SettingsProvider>
			<App />
		</SettingsProvider>
	);
}
