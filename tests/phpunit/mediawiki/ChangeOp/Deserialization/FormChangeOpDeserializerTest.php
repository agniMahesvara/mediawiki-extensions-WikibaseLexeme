<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\NullChangeOp;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\FormChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class FormChangeOpDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testRequestWithoutLexemeId_yieldsPureEditFormChangeOp() {
		$request = [ 'something' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$deserializer = new FormChangeOpDeserializer(
			$this->getMock( EntityLookup::class ),
			$this->getMock( EntityIdParser::class ),
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		$this->assertSame(
			$editFormChangeOp,
			$deserializer->createEntityChangeOp( $request )
		);
	}

	public function testRequestWithLexemeId_yieldsAddFormToLexemeChangeOp() {
		$request = [ 'lexemeId' => 'L4711', 'something' => 'else' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup
			->expects( $this->once() )
			->method( 'getEntity' )
			->willReturn( NewLexeme::havingId( 'L4711' )->build() );

		$idParser = $this->getMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturnCallback(
				function ( $id ) {
					return new LexemeId( $id );
				} );

		$deserializer = new FormChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( ValidationContext::create( 'data' ) );

		$addFormChangeOp = $deserializer->createEntityChangeOp( $request );

		// TODO Assert that correct lexeme is passed
		$this->assertInstanceOf( AddFormToLexemeChangeOp::class, $addFormChangeOp );
	}

	public function testRequestWithInvalidLexemeId_addsViolation() {
		$request = [ 'lexemeId' => 'foo', 'something' => 'else' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$entityLookup = $this->getMock( EntityLookup::class );

		$idParser = $this->getMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willThrowException( new EntityIdParsingException() );

		$idContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotLexemeId( 'foo' ) );

		$dataContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new FormChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

	public function testRequestWithNonLexemeId_addsViolation() {
		$request = [ 'lexemeId' => 'Q2', 'something' => 'else' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$entityLookup = $this->getMock( EntityLookup::class );

		$idParser = $this->getMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturn( new ItemId( 'Q2' ) );

		$idContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotLexemeId( 'Q2' ) );

		$dataContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new FormChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

	public function testRequestWithIdOfNotExistingLexeme_addsViolation() {
		$request = [ 'lexemeId' => 'L3000', 'something' => 'else' ];

		$repr = $this->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();

		$editFormChangeOp = $this->getMock( ChangeOp::class );

		$repr->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $request )
			->willReturn( $editFormChangeOp );

		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )
			->willReturn( null );

		$idParser = $this->getMock( EntityIdParser::class );
		$idParser->method( 'parse' )
			->willReturnCallback(
				function ( $id ) {
					return new LexemeId( $id );
				} );

		$idContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$idContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new LexemeNotFound( new LexemeId( 'L3000' ) ) );

		$dataContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$dataContext->expects( $this->once() )
			->method( 'at' )
			->with( 'lexemeId' )
			->willReturn( $idContext );

		$deserializer = new FormChangeOpDeserializer(
			$entityLookup,
			$idParser,
			$repr
		);

		$deserializer->setContext( $dataContext );

		$changeOp = $deserializer->createEntityChangeOp( $request );

		$this->assertInstanceOf( NullChangeOp::class, $changeOp );
	}

}
