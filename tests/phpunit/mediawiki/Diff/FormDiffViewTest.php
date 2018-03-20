<?php

namespace Wikibase\Lexeme\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
use PHPUnit\Framework\TestCase;
use RawMessage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\DataModel\Services\Diff\FormDiffer;
use Wikibase\Lexeme\Diff\FormDiffView;
use Wikibase\Lexeme\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers Wikibase\Lexeme\Diff\FormDiffView
 *
 * @license GPL-2.0-or-later
 */
class FormDiffViewTest extends TestCase {

	/**
	 * @return ClaimDiffer
	 */
	private function getMockClaimDiffer() {
		return new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) );
	}

	/**
	 * @param string $returnValue
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter( $returnValue = '<i>SNAK</i>' ) {
		$instance = $this->getMock( SnakFormatter::class );
		$instance->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );
		$instance->expects( $this->any() )
			->method( 'canFormatSnak' )
			->will( $this->returnValue( true ) );
		$instance->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $returnValue ) );
		return $instance;
	}

	/**
	 * @return EntityIdFormatter
	 */
	public function newEntityIdLabelFormatter() {
		$instance = $this->getMock( EntityIdFormatter::class );

		$instance->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( '<a>PID</a>' ) );

		return $instance;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		return new ClaimDifferenceVisualizer(
			new DifferencesSnakVisualizer(
				$this->newEntityIdLabelFormatter(),
				$this->newSnakFormatter( '<i>DETAILED SNAK</i>' ),
				$this->newSnakFormatter(),
				'qqx'
			),
			'qqx'
		);
	}

	/**
	 * @return MessageLocalizer
	 */
	private function getMockMessageLocalizer() {
		$mock = $this->getMock( MessageLocalizer::class );

		$mock->method( 'msg' )
			->will( $this->returnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} ) );

		return $mock;
	}

	/**
	 * @param ChangeFormDiffOp $diff
	 *
	 * @return FormDiffView
	 */
	private function getDiffView( ChangeFormDiffOp $diff ) {
		return new FormDiffView(
			[],
			new Diff(
				[ 'form' => new Diff( [ $diff->getFormId()->getSerialization() => $diff ], true ) ],
				true
			),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			$this->getItemRefDiffVisualizer(),
			$this->getMockMessageLocalizer()
		);
	}

	public function testDiffChangedRepresentations() {
		$differ = new FormDiffer();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'cat' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$diff = $differ->diffEntities( $form1, $form2 );

		$formDiffViewHeader = 'form / L1-F1 / (wikibaselexeme-diffview-representation) / en';
		$expected = '<tr><td colspan="2" class="diff-lineno">' . $formDiffViewHeader .
			'</td><td colspan="2" class="diff-lineno">' . $formDiffViewHeader . '</td></tr>' .
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline"><div>' .
			'<del class="diffchange diffchange-inline">cat</del></div></td><td class="diff-marker">' .
			'+</td><td class="diff-addedline"><div><ins class="diffchange diffchange-inline">' .
			'goat</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testDiffAddedRepresentations() {
		$differ = new FormDiffer();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andRepresentation( 'fr', 'fr-value' )
			->build();

		$diff = $differ->diffEntities( $form1, $form2 );

		$formDiffViewHeader = 'form / L1-F1 / (wikibaselexeme-diffview-representation) / fr';
		$expected = '<tr><td colspan="2" class="diff-lineno">' . $formDiffViewHeader .
			'</td><td colspan="2" class="diff-lineno">' . $formDiffViewHeader . '</td></tr>' .
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline">fr-value</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testDiffAddedGrammaticalFeatures() {
		$differ = new FormDiffer();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andGrammaticalFeature( 'Q1' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andGrammaticalFeature( 'Q1' )
			->andGrammaticalFeature( 'Q2' )
			->build();
		$diff = $differ->diffEntities( $form1, $form2 );

		$formDiffViewHeader = 'form / L1-F1 / (wikibaselexeme-diffview-grammatical-feature) / 0';
		$expected = '<tr><td colspan="2" class="diff-lineno">' .
			'</td><td colspan="2" class="diff-lineno">' . $formDiffViewHeader . '</td>' .
			'</tr><tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td>' .
			'<td class="diff-addedline"><div><ins class="diffchange diffchange-inline">'.
			'<span>formatted Q2</span></ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testDiffChangedStatements() {
		$differ = new FormDiffer();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andStatement( $this->someStatement( 'P1', 'guid1' ) )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andStatement( $this->someStatement( 'P1', 'guid1' ) )
			->andStatement( $this->someStatement( 'P2', 'guid2' ) )
			->build();

		$diff = $differ->diffEntities( $form1, $form2 );

		$expected = '<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">' .
			'form / L1-F1 / (wikibase-entity-property) / <a>PID</a></td></tr><tr>' .
			'<td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span><i>DETAILED SNAK</i></span></ins>' .
			'</div></td></tr><tr><td colspan="2" class="diff-lineno"></td><td colspan="2" ' .
			'class="diff-lineno">form / L1-F1 / (wikibase-entity-property) / <a>PID</a>' .
			'(colon-separator)<i>SNAK</i> / (wikibase-diffview-rank)</td></tr><tr><td colspan="2">' .
			'&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline"><div>' .
			'<ins class="diffchange diffchange-inline"><span>(wikibase-diffview-rank-normal)</span>' .
			'</ins></div></td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	public function testPatchRemovedGrammaticalFeature() {
		$differ = new FormDiffer();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andGrammaticalFeature( 'Q1' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->build();

		$diff = $differ->diffEntities( $form1, $form2 );

		$formDiffViewHeader = 'form / L1-F1 / (wikibaselexeme-diffview-grammatical-feature) / 0';
		$expected = '<tr><td colspan="2" class="diff-lineno">' . $formDiffViewHeader . '</td>' .
			'<td colspan="2" class="diff-lineno"></td>' .
			'</tr><tr><td class="diff-marker">-</td><td class="diff-deletedline"><div>' .
			'<del class="diffchange diffchange-inline"><span>formatted Q1</span></del></div></td>' .
			'<td colspan="2">&nbsp;</td></tr>';
		$this->assertSame( $expected, $this->getDiffView( $diff )->getHtml() );
	}

	/**
	 * @return Statement
	 */
	private function someStatement( $propertyId, $guid ) {
		$statement = new Statement(
			new PropertySomeValueSnak( new PropertyId( $propertyId ) )
		);
		$statement->setGuid( $guid );
		return $statement;
	}

	private function getItemRefDiffVisualizer() {
		return new ItemReferenceDifferenceVisualizer( $this->getIdFormatter() );
	}

	private function getIdFormatter() {
		$formatter = $this->getMock( EntityIdFormatter::class );
		$formatter->method( $this->anything() )
			->willReturnCallback( function ( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				return 'formatted ' . $id;
			} );
		return $formatter;
	}

}
