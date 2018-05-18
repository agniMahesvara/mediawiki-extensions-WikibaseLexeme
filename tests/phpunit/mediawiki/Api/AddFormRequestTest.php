<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequest
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testReturnsChangeOpThatAddsForm() {
		$request = new AddFormRequest(
			new LexemeId( 'L1' ),
			new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [ new ItemId( 'Q1' ) ] )
			] )
		);

		$changeOp = $request->getChangeOp();

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$forms = $lexeme->getForms()->toArray();

		$this->assertCount( 1, $forms );
		$this->assertEquals( [ 'en' => 'goat' ], $forms[0]->getRepresentations()->toTextArray() );
		$this->assertEquals( [ new ItemId( 'Q1' ) ], $forms[0]->getGrammaticalFeatures() );
	}

	public function testGetLexemeId() {
		$lexemeId = new LexemeId( 'L1' );

		$request = new AddFormRequest(
			$lexemeId,
			new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [] )
			] )
		);

		$this->assertSame( $lexemeId, $request->getLexemeId() );
	}

}
