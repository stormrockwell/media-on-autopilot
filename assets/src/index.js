import apiFetch from '@wordpress/api-fetch';
import { observePickers } from './focal-point/picker';
import './focal-point/style.css';

observePickers( { apiFetch } );
