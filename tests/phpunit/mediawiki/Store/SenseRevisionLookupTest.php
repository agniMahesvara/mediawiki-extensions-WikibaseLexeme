<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\NullSenseId;
use Wikibase\Lexeme\Store\SenseRevisionLookup;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Store\SenseRevisionLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SenseRevisionLookupTest extends TestCase {

	use \PHPUnit4And6Compat;

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var SenseId
	 */
	private $senseId;

	protected function setUp() {
		parent::setUp();

		$this->lexemeId = new LexemeId( 'L1' );
		$this->senseId = new SenseId( 'L1-S1' );
	}

	public function testGivenLexemeId_getEntityRevisionFails() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$instance = new SenseRevisionLookup( $parentService );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getEntityRevision( $this->lexemeId );
	}

	public function testGivenSenseId_getEntityRevisionCallsParentServiceWithLexemeId() {
		$lexeme = $this->newLexeme();
		$revisionId = 23;

		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $this->lexemeId, $revisionId )
			->willReturn( new EntityRevision( $lexeme, $revisionId ) );
		$instance = new SenseRevisionLookup( $parentService );

		$result = $instance->getEntityRevision( $this->senseId, $revisionId );

		$expectedSense = $lexeme->getSenses()->toArray()[0];
		$this->assertEquals( new EntityRevision( $expectedSense, $revisionId ), $result );
	}

	public function testGivenLexemeId_getLatestRevisionIdFails() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$instance = new SenseRevisionLookup( $parentService );

		$this->setExpectedException( ParameterTypeException::class );
		$instance->getLatestRevisionId( $this->lexemeId );
	}

	public function testGivenSenseId_getLatestRevisionIdCallsToParentServiceWithLexemeId() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $this->lexemeId )
			->willReturn( 'fromParentService' );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );
		$instance = new SenseRevisionLookup( $parentService );

		$result = $instance->getLatestRevisionId( $this->senseId );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenNotExistingSenseId_getLatestRevisionIdReturnsFalse() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $this->lexemeId )
			->willReturn( 'fromParentService' );
		$parentService->method( 'getEntityRevision' )
			->with( $this->lexemeId )
			->willReturn( new EntityRevision( $this->newLexeme(), 123 ) );
		$instance = new SenseRevisionLookup( $parentService );

		$this->assertFalse( $instance->getLatestRevisionId( new SenseId( 'L1-S200' ) ) );
	}

	public function testGivenNullSenseId_lookupIsNotPerformedAndNullReturned() {
		$parentService = $this->getMock( EntityRevisionLookup::class );
		$parentService
			->expects( $this->never() )
			->method( 'getEntityRevision' );

		$senseRevisionLookup = new SenseRevisionLookup( $parentService );

		$this->assertNull( $senseRevisionLookup->getEntityRevision( new NullSenseId() ) );
	}

	private function newLexeme() {
		return NewLexeme::havingId( $this->lexemeId )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'gloss' )
			)
			->build();
	}

}