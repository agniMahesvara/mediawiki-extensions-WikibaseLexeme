<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Title;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\DataAccess\Store\FormTitleStoreLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\FormTitleStoreLookup
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormTitleStoreLookupTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenLexemeId_getTitleForIdFails() {
		$instance = new FormTitleStoreLookup( $this->getMock( EntityTitleStoreLookup::class ) );

		$this->expectException( ParameterTypeException::class );
		$instance->getTitleForId( new LexemeId( 'L1' ) );
	}

	public function testGivenFormId_getTitleForIdCallsParentServiceWithLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$title = $this->createMock( Title::class );
		$title->method( 'setFragment' )
			->with( '#' . $formId->getIdSuffix() );

		$parentLookup = $this->createMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->with( $lexemeId )
			->willReturn( $title );

		$instance = new FormTitleStoreLookup( $parentLookup );

		$result = $instance->getTitleForId( $formId );
		$this->assertSame( $title, $result );
	}

	public function testGivenNoTitleForLexeme_getTitleForIdReturnsNull() {
		$parentLookup = $this->createMock( EntityTitleStoreLookup::class );
		$parentLookup->method( 'getTitleForId' )
			->willReturn( null );

		$lookup = new FormTitleStoreLookup( $parentLookup );

		$this->assertNull( $lookup->getTitleForId( new FormId( 'L66-F1' ) ) );
	}

}
