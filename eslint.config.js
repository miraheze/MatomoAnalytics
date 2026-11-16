'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );

module.exports = defineConfig( [
	globalIgnores( [
		'vendor/',
		'modules/ext.matomoanalytics.charts.js'
	] ),
	{
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki' ) ],
		languageOptions: {
			ecmaVersion: 2024,
			globals: {
				require: 'readonly',
				module: 'readonly'
			}
		},
		rules: {
			'one-var': 'off',
			'no-implicit-globals': 'off',
			'es/no-object-fromentries': 'off',
			'es/no-object-entries': 'off',
			'no-jquery/no-global-selector': 'off',
			'no-jquery/no-sizzle': 'off',
			'max-len': 'off',
			'mediawiki/class-doc': 'off'
		}
	}
] );
