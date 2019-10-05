<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddSenseToLexemeChangeOp;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\AddSenseToLexemeChangeOp
 *
 * @license GPL-2.0-or-later
 */
class AddSenseToLexemeChangeOpTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonBlankSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->validate( NewSense::havingId( 'S1' )->build() );
	}

	public function testValidateBlankSense_yieldsSuccess() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$result = $changeOp->validate( new BlankSense() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonBlankSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->apply( NewSense::havingId( 'S1' )->build() );
	}

	public function testApply_connectsLexemeToSense() {
		$lexeme = NewLexeme::create()->build();
		$blankSense = $this->createMock( BlankSense::class );
		$blankSense->expects( $this->once() )
			->method( 'setLexeme' )
			->with( $lexeme );

		$changeOp = new AddSenseToLexemeChangeOp( $lexeme );
		$changeOp->apply( $blankSense );
	}

}
