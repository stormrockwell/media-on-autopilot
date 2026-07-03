import apiFetch from '@wordpress/api-fetch';
import { observeButtons } from './ai-tagging/button';
import { tagAttachment } from './ai-tagging/api';
import './ai-tagging/style.css';

observeButtons( { apiFetch, tagAttachment } );
