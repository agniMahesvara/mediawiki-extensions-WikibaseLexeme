<?php

namespace Wikibase\Lexeme\Merge;

use Exception;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Merge\Exceptions\ConflictingLemmaValueException;
use Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLanguagesException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLexicalCategoriesException;
use Wikibase\Lexeme\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Merge\Exceptions\ModificationFailedException;
use Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Merge\Validator\NoConflictingTermListValues;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements;

/**
 * @license GPL-2.0-or-later
 */
class LexemeMerger {

	/**
	 * @var StatementsMerger
	 */
	private $statementsMerger;

	/**
	 * @var LexemeFormsMerger
	 */
	private $formsMerger;

	/**
	 * @var TermListMerger
	 */
	private $termListMerger;

	public function __construct(
		TermListMerger $termListMerger,
		StatementsMerger $statementsMerger,
		LexemeFormsMerger $formsMerger
	) {
		$this->termListMerger = $termListMerger;
		$this->statementsMerger = $statementsMerger;
		$this->formsMerger = $formsMerger;
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target Will be modified by reference
	 */
	public function merge( Lexeme $source, Lexeme $target ) {
		$this->validate( $source, $target );

		try {
			$this->termListMerger->merge( $source->getLemmas(), $target->getLemmas() );
			$this->formsMerger->merge( $source, $target );
			$this->statementsMerger->merge( $source, $target );
		} catch ( MergingException $e ) {
			throw $e;
		} catch ( Exception $e ) {
			throw new ModificationFailedException( '', 0, $e );
		}
	}

	private function validate( Lexeme $source, Lexeme $target ) {
		if ( $source->getId()->equals( $target->getId() ) ) {
			throw new ReferenceSameLexemeException();
		}

		if ( !$source->getLanguage()->equals( $target->getLanguage() ) ) {
			throw new DifferentLanguagesException();
		}

		if ( !$source->getLexicalCategory()->equals( $target->getLexicalCategory() ) ) {
			throw new DifferentLexicalCategoriesException();
		}

		$conflictingTermListValues = new NoConflictingTermListValues();
		if ( !$conflictingTermListValues->validate( $source->getLemmas(), $target->getLemmas() ) ) {
			throw new ConflictingLemmaValueException();
		}

		$crossReferencingStatements = new NoCrossReferencingStatements();
		if ( !$crossReferencingStatements->validate( $source, $target ) ) {
			throw new CrossReferencingException();
		}
	}

}