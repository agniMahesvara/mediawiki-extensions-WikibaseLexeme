/**
 * @license GPL-2.0-or-later
 */
describe( 'LanguageAndLexicalCategoryWidget', function () {
	global.mw = { // eslint-disable-line no-restricted-globals
		config: {
			get: function () {
				return '';
			}
		}
	};

	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var newLanguageAndLexicalCategoryWidget = require( './../../resources/widgets/LanguageAndLexicalCategoryWidget.js' );

	it( 'shows the language and the lexical category', function () {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			widget = newWidget( language, lexicalCategory );

		expect( widget.$el.textContent, 'to contain', 'Link for ' + language );
		expect( widget.$el.textContent, 'to contain', 'Link for ' + lexicalCategory );

		expect( widget.$el, 'to contain elements matching', '.language-link' );
		expect( widget.$el, 'to contain elements matching', '.lexical-category-link' );
	} );

	it( 'switches to edit mode and back', function ( done ) {
		var widget = newWidget( 'Q123', 'Q234' );

		expect( widget, 'not to be in edit mode' );

		widget.inEditMode = true;
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );

			widget.inEditMode = false;
			widget.$nextTick( function () {
				expect( widget, 'not to be in edit mode' );
				done();
			} );
		} );
	} );

	expect.addAssertion( '<object> [not] to be in edit mode', function ( expect, widget ) {
		expect.errorMode = 'nested';

		expect( widget.inEditMode, '[not] to be true' );
		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', 'input' );
	} );

	function newWidget( language, lexicalCategory ) {
		var LanguageAndLexicalCategoryWidget = Vue.extend( newLanguageAndLexicalCategoryWidget( getTemplate(), {}, {
			get: function ( key ) {
				return key;
			}
		} ) );

		return new LanguageAndLexicalCategoryWidget( {
			store: {
				state: {
					languageLink: '<a href="#" class="language-link">Link for ' + language + '</a>',
					lexicalCategoryLink: '<a href="#" class="lexical-category-link">Link for ' + lexicalCategory + '</a>'
				}
			},
			propsData: {
				language: language,
				lexicalCategory: lexicalCategory,
				inEditMode: false,
				isSaving: false
			}
		} ).$mount();
	}

	function getTemplate() {
		return '<div class="language-lexical-category-widget">'
			+ '<div v-if="!inEditMode">'
			+ '<div>'
			+ '<span>{{\'wikibaselexeme-field-language-label\'|message}}</span>'
			+ '<span v-html="formattedLanguage"></span>'
			+ '</div>'
			+ '<div>'
			+ '<span>{{\'wikibaselexeme-field-lexical-category-label\'|message}}</span>'
			+ '<span v-html="formattedLexicalCategory"></span>'
			+ '</div>'
			+ '</div>'
			+ '<div v-else>'
			+ '<div>'
			+ '<label for="lexeme-language">{{\'wikibaselexeme-field-language-label\'|message}}</label>'
			+ '<input id="lexeme-language" v-bind:value="language" @input="$emit(\'update:language\', $event.target.value)">'
			+ '</div>'
			+ '<div>'
			+ '<label for="lexeme-lexical-category">{{\'wikibaselexeme-field-lexical-category-label\'|message}}</label>'
			+ '<input id="lexeme-lexical-category" v-bind:value="lexicalCategory" @input="$emit(\'update:lexicalCategory\', $event.target.value)">'
			+ '</div>'
			+ '</div>'
			+ '</div>';
	}
} );
