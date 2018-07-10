<?php


namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use Deserializers\Deserializer;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Serializers\Serializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\Serialization\SenseDeserializer;
use Wikibase\Lexeme\DataModel\Serialization\SenseSerializer;

/**
 * @covers \Wikibase\Lexeme\DataModel\Serialization\SenseDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SenseDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * Provides pairs of serializations and a Sense they are expected to deserialize to.
	 * The last serialization returned is in the current format.
	 */
	public function provideSerializations() {
		$statementId = 'L2-S3$6b2eb64d-2069-43ba-8020-51068985aa8a';
		yield [
			[
				'id' => 'L2-S3',
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'small furry animal' ],
					'de' => [ 'language' => 'de', 'value' => 'kleines pelziges Tier' ],
				],
				'claims' => [
					'P31' => [
						[
							'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P31' ],
							'type' => 'statement',
							'id' => $statementId,
							'rank' => 'normal',
						],
					],
				],
			],
			new Sense(
				new SenseId( 'L2-S3' ),
				new TermList( [
					new Term( 'en', 'small furry animal' ),
					new Term( 'de', 'kleines pelziges Tier' ),
				] ),
				new StatementList( [
					new Statement(
						new PropertyNoValueSnak( new PropertyId( 'P31' ) ),
						null,
						null,
						$statementId
					),
				] )
			)
		];
	}

	/**
	 * @dataProvider provideSerializations
	 */
	public function testDeserialize( array $serialization, Sense $expected ) {
		$deserializer = new SenseDeserializer(
			new TermListDeserializer( new TermDeserializer() ),
			new StatementListDeserializer( new StatementDeserializer(
				new SnakDeserializer( $this->createMock( Deserializer::class ) ),
				$this->createMock( Deserializer::class ),
				$this->createMock( Deserializer::class )
			) )
		);

		$actual = $deserializer->deserialize( $serialization );

		$this->assertEquals( $expected, $actual );
	}

	public function testRoundTrip() {
		$lastSerialization = null;
		foreach ( $this->provideSerializations() as list( $serialization, $sense ) ) {
			$lastSerialization = $serialization;
		}
		$deserializer = new SenseDeserializer(
			new TermListDeserializer( new TermDeserializer() ),
			new StatementListDeserializer( new StatementDeserializer(
				new SnakDeserializer( $this->createMock( Deserializer::class ) ),
				$this->createMock( Deserializer::class ),
				$this->createMock( Deserializer::class )
			) )
		);
		$serializer = new SenseSerializer(
			new TermListSerializer( new TermSerializer(), false ),
			new StatementListSerializer(
				new StatementSerializer(
					new SnakSerializer( $this->createMock( Serializer::class ), false ),
					$this->createMock( Serializer::class ),
					$this->createMock( Serializer::class )
				),
				false
			)
		);

		$sense = $deserializer->deserialize( $lastSerialization );
		$serialization = $serializer->serialize( $sense );

		$this->assertSame( $lastSerialization, $serialization );
	}

}
