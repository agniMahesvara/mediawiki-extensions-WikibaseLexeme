<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer
 *
 * @license GPL-2.0-or-later
 */
class EditSenseChangeOpDeserializerTest extends TestCase {

	public function testCreateEntityChangeOp_yieldsChangeOpSenseEdit() {
		$deserializer = $this->getDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( [] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithOffTypeGlosses_addsViolation() {
		$deserializer = $this->getDeserializer();

		$senseContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$glossesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'glosses' )
			->willReturn( $glossesContext );
		$glossesContext->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', 'string' ) );

		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( [ 'glosses' => 'ff' ] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 0, $changeOps->getChangeOps() );
	}

	public function testCreateEntityChangeOpWithGlosses_callsDownstreamDeserializer() {
		$glossesChangeOpDeserializer = $this->getGlossesChangeOpDeserializer();
		$glossesChangeOpDeserializer
			->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( [ 'some' => 'info' ] );
		$deserializer = $this->getDeserializer( $glossesChangeOpDeserializer );

		$senseContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
		$glossesContext = $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();

		$senseContext->expects( $this->once() )
			->method( 'at' )
			->with( 'glosses' )
			->willReturn( $glossesContext );

		$deserializer->setContext( $senseContext );
		$changeOps = $deserializer->createEntityChangeOp( [
			'glosses' => [ 'some' => 'info' ]
		] );

		$this->assertInstanceOf( ChangeOpSenseEdit::class, $changeOps );
		$this->assertCount( 1, $changeOps->getChangeOps() );
	}

	private function getGlossesChangeOpDeserializer() {
		return $this->getMockBuilder( GlossesChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getItemIdListDeserializer() {
		return $this->getMockBuilder( ItemIdListDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getDeserializer(
		$glossesChangeOpDeserializer = null,
		$itemIdListDeserializer = null
	) {
		if ( $glossesChangeOpDeserializer === null ) {
			$glossesChangeOpDeserializer = $this->getGlossesChangeOpDeserializer();
		}
		if ( $itemIdListDeserializer === null ) {
			$itemIdListDeserializer = $this->getItemIdListDeserializer();
		}

		return new EditSenseChangeOpDeserializer(
			$glossesChangeOpDeserializer,
			$itemIdListDeserializer
		);
	}

}