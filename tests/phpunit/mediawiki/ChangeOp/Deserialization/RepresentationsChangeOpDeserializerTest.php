<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use ApiUsageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Status;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveFormRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lib\StringNormalizer;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class RepresentationsChangeOpDeserializerTest extends TestCase {

	public function testCreateEntityChangeOpEmpty_yieldsZeroChangeOpRepresentationList() {
		$representationDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$deserializer = new RepresentationsChangeOpDeserializer(
			$representationDeserializer,
			new StringNormalizer(),
			$validator
		);
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithValidTerm_yieldsChangeOpRepresentationList() {
		$representationDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'language' => 'en', 'value' => 'smth' ] )
			->willReturn( new Term( 'en', 'smth' ) );

		$deserializer = new RepresentationsChangeOpDeserializer(
			$representationDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'smth' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRepresentation::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithValidTerm_trimsRepresentationsValuesToNFC() {
		$representationDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$representationDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( [ 'language' => 'en', 'value' => 'smth' ] )
			->willReturn( new Term( 'en', 'smth' ) );

		$deserializer = new RepresentationsChangeOpDeserializer(
			$representationDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => " \t smth \v\n " ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRepresentation::class, $changeOps->getChangeOps()[0] );
	}

	public function testCreateEntityChangeOpWithRemoval_yieldsChangeOpRepresentationList() {
		$representationDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();

		$deserializer = new RepresentationsChangeOpDeserializer(
			$representationDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$changeOps = $deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'remove' => '' ]
		] );

		$this->assertInstanceOf( ChangeOpRepresentationList::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
		$this->assertInstanceOf( ChangeOpRemoveFormRepresentation::class, $changeOps->getChangeOps()[0] );
	}

	public function testGivenChangeValidationFails_exceptionIsThrownInsteadOfCreatingChangeOp() {
		$representationDeserializer = $this->createMock( TermDeserializer::class );
		$validator = $this->newDummyValidator();
		$validator->method( 'validate' )
			->willThrowException(
				new ApiUsageException( null, Status::newFatal( 'some-validation-error' ) )
			);

		$deserializer = new RepresentationsChangeOpDeserializer(
			$representationDeserializer,
			new StringNormalizer(),
			$validator
		);

		$languageContext = $this->getContextSpy();
		$representationContext = $this->getContextSpy();
		$representationContext
			->method( 'at' )
			->with( 'en' )
			->willReturn( $languageContext );
		$deserializer->setContext( $representationContext );

		$this->expectException( ApiUsageException::class );
		$deserializer->createEntityChangeOp( [
			'en' => [ 'language' => 'en', 'value' => 'bad things' ]
		] );
	}

	private function getContextSpy() {
		return $this
			->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return LexemeTermSerializationValidator|MockObject
	 */
	private function newDummyValidator() {
		return $this->createMock( LexemeTermSerializationValidator::class );
	}

}
