'use strict';

/**
 * See also: http://webdriver.io/guide/testrunner/configurationfile.html
 */
const fs = require( 'fs' ),
	path = require( 'path' ),
	logPath = process.env.LOG_DIR || path.join( __dirname, '/log' ),
	saveScreenshot = require( 'wdio-mediawiki' ).saveScreenshot;

exports.config = {
	// ======
	// Custom WDIO config specific to MediaWiki
	// ======
	// Use in a test as `browser.config.<key>`.
	// Defaults are for convenience with MediaWiki-Vagrant

	// Wiki admin
	mwUser: process.env.MEDIAWIKI_USER || 'Admin',
	mwPwd: process.env.MEDIAWIKI_PASSWORD || 'vagrant',

	// Base for browser.url() and Page#openTitle()
	baseUrl: ( process.env.MW_SERVER || 'http://127.0.0.1:8080' ) + (
		process.env.MW_SCRIPT_PATH || '/w'
	),

	// ==================
	// Test Files
	// ==================
	specs: [
		__dirname + '/specs/*.js',
		__dirname + '/specs/special/*.js'
	],

	// ============
	// Capabilities
	// ============
	capabilities: [ {
		// https://sites.google.com/a/chromium.org/chromedriver/capabilities
		browserName: 'chrome',
		maxInstances: 1,
		'goog:chromeOptions': {
			// If DISPLAY is set, assume developer asked non-headless or CI with Xvfb.
			// Otherwise, use --headless (added in Chrome 59)
			// https://chromium.googlesource.com/chromium/src/+/59.0.3030.0/headless/README.md
			args: [
				...( process.env.DISPLAY ? [] : [ '--headless' ] ),
				// Chrome sandbox does not work in Docker
				...( fs.existsSync( '/.dockerenv' ) ? [ '--no-sandbox' ] : [] )
			]
		}
	} ],

	// ===================
	// Test Configurations
	// ===================

	// Level of verbosity: silent | verbose | command | data | result | error
	logLevel: 'error',

	// Setting this enables automatic screenshots for when a browser command fails
	// It is also used by afterTest for capturig failed assertions.
	screenshotPath: logPath,

	// Default timeout for each waitFor* command.
	waitforTimeout: 10 * 1000,

	// See:
	// https://webdriver.io/docs/dot-reporter
	// https://webdriver.io/docs/junit-reporter
	reporters: [
		'spec',
		[ 'junit', {
			outputDir: logPath,
			outputFileFormat: function () {
				const makeFilenameDate = new Date().toISOString().replace( /[:.]/g, '-' );
				return `WDIO.xunit-${makeFilenameDate}.xml`;
			}
		} ]
	],

	// See also: http://mochajs.org
	mochaOpts: {
		ui: 'bdd',
		timeout: 60 * 1000
	},

	// =====
	// Hooks
	// =====

	/**
	 * Save a screenshot when test fails.
	 *
	 * @param {Object} test Mocha Test object
	 */
	afterTest: function ( test ) {
		if ( !test.passed ) {
			const filePath = saveScreenshot( test.title );
			console.log( '\n\tScreenshot: ' + filePath + '\n' );
		}
	}
};
