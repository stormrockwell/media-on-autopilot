// Stub for @wordpress/i18n — returns the string as-is (no translation in tests).
module.exports = {
	__: ( str ) => str,
	_n: ( single, plural, count ) => ( count === 1 ? single : plural ),
	_x: ( str ) => str,
	sprintf: ( fmt, ...args ) =>
		args.reduce( ( s, a ) => s.replace( /%s/, a ), fmt ),
};
