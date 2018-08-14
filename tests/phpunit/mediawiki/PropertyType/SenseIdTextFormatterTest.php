<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\PropertyType;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\PropertyType\SenseIdTextFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\PropertyType\SenseIdTextFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdTextFormatterTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testFormatId_nonExisting() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturn( null );
		$formatter = new SenseIdTextFormatter( $lookup, new DummyLocalizedTextProvider() );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( 'L10-S20', $result );
	}

	public function testFormatId_oneLemma_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter( $lookup, new DummyLocalizedTextProvider() );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: lemma, gloss)';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_threeLemmas_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withLemma( 'de', 'Lemma' )
					->withLemma( 'el', 'λεμμα' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter( $lookup, new DummyLocalizedTextProvider() );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: ' .
			'lemma' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'Lemma' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'λεμμα' .
			', gloss)';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_oneLemma_fallbackGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'en', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter( $lookup, new DummyLocalizedTextProvider() );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: lemma, TODO)';
		$this->assertSame( $expected, $result );
	}

}
