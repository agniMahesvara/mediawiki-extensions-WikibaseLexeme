<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoDataTypes.
 * It defines the formatters used by the repo to display data values of different types.
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @note: 'validator-factory-callback' fields delegate to a global instance of
 * ValidatorsBuilders
 *
 * @see ValidatorsBuilders
 * @see docs/datatypes.wiki in the Wikibase.git repository for documentation
 *
 * @license GPL-2.0-or-later
 */

use ValueFormatters\FormatterOptions;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\PropertyType\FormIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\FormIdTextFormatter;
use Wikibase\Lexeme\PropertyType\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\SenseIdHtmlFormatter;
use Wikibase\Lexeme\PropertyType\SenseIdTextFormatter;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;

return [
	'PT:wikibase-lexeme' => [
		'expert-module' => 'wikibase.experts.Lexeme',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Lexeme::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
			if ( $format === SnakFormatter::FORMAT_HTML ||
				 $format === SnakFormatter::FORMAT_HTML_VERBOSE ||
				 $format === SnakFormatter::FORMAT_HTML_DIFF
			) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$userLanguage = $wikibaseRepo->getUserLanguage();

				// TODO: Use LanguageFallbackLabelDescriptionLookupFactory instead?
				$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
					$wikibaseRepo->getTermLookup(),
					$wikibaseRepo->getLanguageFallbackChainFactory()
						->newFromLanguage( $userLanguage )
				);

				return new EntityIdValueFormatter(
					new LexemeIdHtmlFormatter(
						$wikibaseRepo->getEntityLookup(),
						$labelDescriptionLookup,
						$wikibaseRepo->getEntityTitleLookup(),
						new MediaWikiLocalizedTextProvider( $userLanguage->getCode() )
					)
				);
			}

			return WikibaseRepo::getDefaultValueFormatterBuilders()
				->newEntityIdFormatter( $format, $options );
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-form' => [
		'expert-module' => 'wikibase.experts.Form',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Form::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$userLanguage = $wikibaseRepo->getUserLanguage();
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$textProvider = new MediaWikiLocalizedTextProvider( $userLanguage->getCode() );
			if (
				$format === SnakFormatter::FORMAT_HTML ||
				$format === SnakFormatter::FORMAT_HTML_VERBOSE ||
				$format === SnakFormatter::FORMAT_HTML_DIFF
			) {
				$titleLookup = $wikibaseRepo->getEntityTitleLookup();
				$baseFormatter = new FormIdHtmlFormatter(
					$revisionLookup,
					$titleLookup,
					$textProvider,
					new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup )
				);
			} else {
				$baseFormatter = new FormIdTextFormatter(
					$revisionLookup,
					$textProvider
				);
			}
			return new EntityIdValueFormatter(
				$baseFormatter
			);
		},
		'value-type' => 'wikibase-entityid',
	],
	'PT:wikibase-sense' => [
		'expert-module' => 'wikibase.experts.Sense',
		'validator-factory-callback' => function() {
			$factory = WikibaseRepo::getDefaultValidatorBuilders();
			return $factory->getEntityValidators( Sense::ENTITY_TYPE );
		},
		'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup();
			$languageCode = $wikibaseRepo->getUserLanguage()->getCode();

			$localizedTextProvider = new MediaWikiLocalizedTextProvider( $languageCode );

			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$fallbackChain = $languageFallbackChainFactory->newFromLanguageCode( $languageCode );

			if (
				$format === SnakFormatter::FORMAT_HTML ||
				$format === SnakFormatter::FORMAT_HTML_VERBOSE ||
				$format === SnakFormatter::FORMAT_HTML_DIFF
			) {
				$titleLookup = $wikibaseRepo->getEntityTitleLookup();

				return new EntityIdValueFormatter(
					new SenseIdHtmlFormatter(
						$titleLookup,
						$revisionLookup,
						$localizedTextProvider,
						$fallbackChain,
						new LanguageFallbackIndicator( $wikibaseRepo->getLanguageNameLookup() )
					)
				);
			}

			return new EntityIdValueFormatter(
					new SenseIdTextFormatter(
					$revisionLookup,
					$localizedTextProvider
				)
			);
		},
		'value-type' => 'wikibase-entityid',
	],
];
