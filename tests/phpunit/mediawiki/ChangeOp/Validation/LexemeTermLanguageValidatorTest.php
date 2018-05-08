<?php

namespace Wikibase\Lexeme\Tests\ChangeOp\Validation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Api\Error\InvalidItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\LexemeTermLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\UnknownLanguage;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguageValidatorTest extends TestCase {

	/**
	 * @dataProvider notAStringProvider
	 */
	public function testGivenLanguageNotAString_addsViolation( $language ) {
		$validator = new LexemeTermLanguageValidator( $this->newContentLanguages() );
		$context = $this->getValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'string', gettype( $language ) ) );

		$validator->validate( $language, $context );
	}

	public function testGivenEmptyLanguage_addsViolation() {
		$validator = new LexemeTermLanguageValidator( $this->newContentLanguages() );
		$context = $this->getValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new LexemeTermLanguageCanNotBeEmpty() );

		$validator->validate( '', $context );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenUnknownLanguage_addsViolation( $language ) {
		$validator = new LexemeTermLanguageValidator( $this->newContentLanguages() );
		$context = $this->getValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new UnknownLanguage( $language ) );

		$validator->validate( $language, $context );
	}

	/**
	 * @dataProvider invalidItemProvider
	 */
	public function testGivenInvalidItem_addsViolation( $language, $invalidItem ) {
		$validator = new LexemeTermLanguageValidator( $this->newContentLanguages() );
		$context = $this->getValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new InvalidItemId( $invalidItem ) );

		$validator->validate( $language, $context );
	}

	/**
	 * @dataProvider validLanguageProvider
	 */
	public function testGivenValidLanguage_addsNoViolations( $language ) {
		$validator = new LexemeTermLanguageValidator( $this->newContentLanguages() );
		$context = $this->getValidationContext();
		$context->expects( $this->never() )
			->method( 'addViolation' );

		$validator->validate( $language, $context );
	}

	/**
	 * @return ContentLanguages
	 */
	private function newContentLanguages() {
		return new StaticContentLanguages( [ 'en', 'qqq' ] );
	}

	/**
	 * @return MockObject|ValidationContext
	 */
	private function getValidationContext() {
		return $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function notAStringProvider() {
		return [
			[ 1 ],
			[ true ],
			[ null ],
			[ [] ],
		];
	}

	public function validLanguageProvider() {
		return [
			[ 'en' ],
			[ 'qqq' ],
			[ 'en-x-Q123' ],
		];
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ 'foo' ],
			[ 'en-us' ],
			[ 'en-Q123' ],
		];
	}

	public function invalidItemProvider() {
		return [
			[ 'en-x-foo', 'foo' ],
			[ 'en-x-123', '123' ],
			[ 'en-x-Q123-foo', 'Q123-foo' ],
			[ 'en-x-Q2-x-Q3', 'Q2-x-Q3' ],
		];
	}

}
