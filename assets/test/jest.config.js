module.exports = {
	...require( '@wordpress/scripts/config/jest-unit.config.js' ),
	rootDir: '../../',
	testMatch: [ '<rootDir>/assets/test/**/*.test.js' ],
	moduleNameMapper: {
		'@wordpress/api-fetch': '<rootDir>/assets/test/__mocks__/api-fetch.js',
		'@wordpress/i18n': '<rootDir>/assets/test/__mocks__/i18n.js',
	},
	setupFilesAfterEnv: [ '<rootDir>/assets/test/jest.setup.js' ],
};
