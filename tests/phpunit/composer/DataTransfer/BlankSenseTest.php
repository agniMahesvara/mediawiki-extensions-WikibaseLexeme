<?php

namespace Wikibase\Lexeme\Tests\DataTransfer;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataTransfer\BlankSense;
use Wikibase\Lexeme\DataTransfer\DummySenseId;
use Wikibase\Lexeme\DataTransfer\NullSenseId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\DataTransfer\BlankSense
 *
 * @license GPL-2.0-or-later
 */
class BlankSenseTest extends TestCase {

	public function testGetIdWithoutConnectedLexeme_yieldsNullSenseId() {
		$blankSense = new BlankSense();
		$this->assertInstanceOf( NullSenseId::class, $blankSense->getId() );
	}

	public function testGetIdWithConnectedLexeme_yieldsDummySenseId() {
		$lexemeId = new LexemeId( 'L7' );
		$blankSense = new BlankSense();
		$blankSense->setLexeme( NewLexeme::havingId( $lexemeId )->build() );

		$id = $blankSense->getId();
		$this->assertInstanceOf( DummySenseId::class, $id );
		$this->assertSame( $lexemeId, $id->getLexemeId() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterAssertionException
	 * @expectedExceptionMessage Sense must have at least one gloss
	 */
	public function testGetRealSenseOnIncompleteData_throwsSenseConstructionExceptions() {
		$this->markTestSkipped( 'Sense constructor does not yet verify this' ); // TODO
		$blankSense = new BlankSense();
		$blankSense->getRealSense( new SenseId( 'L1-S4' ) );
	}

	public function testGetRealSenseOnMinimalData_yieldsSenseWithData() {
		$this->markTestSkipped( 'Sense::setGlossList() does not exist yet' ); // TODO
		/*
		$glossList = new TermList( [ new Term( 'de', 'Tier' ) ] );

		$blankSense = new BlankSense();
		$blankSense->setGlossList( $glossList );

		$sense = $blankSense->getRealSense( new SenseId( 'L1-F4' ) );

		$this->assertInstanceOf( Sense::class, $sense );
		$this->assertSame( $glossList, $sense->getGlosses() );
		$this->assertEquals( new StatementList(), $sense->getStatements() );
		*/
	}

}