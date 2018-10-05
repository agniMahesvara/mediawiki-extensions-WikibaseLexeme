<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\LexemeSubEntityId;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Lexeme\DataModel\LexemeSubEntityId
 *
 * @license GPL-2.0-or-later
 */
class LexemeSubEntityIdTest extends TestCase {

	public function testFormatSerialization() {
		$this->assertSame(
			'L1-F7',
			LexemeSubEntityId::formatSerialization( new LexemeId( 'L1' ), 'F', 7 )
		);
		$this->assertSame(
			'L1-S1',
			LexemeSubEntityId::formatSerialization( new LexemeId( 'L1' ), 'S', 1 )
		);
	}

}