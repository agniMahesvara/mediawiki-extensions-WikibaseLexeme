<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\Lexeme\DataModel\FormId;

class ChangeFormDiffOp implements DiffOp {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var Diff
	 */
	private $diffOps;

	public function __construct( FormId $formId, Diff $diffOps ) {
		$this->formId = $formId;
		$this->diffOps = $diffOps;
	}

	/**
	 * @return Diff
	 */
	public function getRepresentationDiffOps() {
		return isset( $this->diffOps['representations'] ) ?
			$this->diffOps['representations']
			: new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getGrammaticalFeaturesDiffOps() {
		return isset( $this->diffOps['grammaticalFeatures'] ) ?
			$this->diffOps['grammaticalFeatures']
			: new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getStatementsDiffOps() {
		return isset( $this->diffOps['claim'] ) ?
			$this->diffOps['claim']
			: new Diff( [] );
	}

	public function serialize() {
	}

	public function unserialize( $serialized ) {
	}

	public function getType() {
//		return 'diff/lexeme/form';
		return 'diff';
	}

	public function isAtomic() {
		return false;
	}

	public function toArray( $valueConverter = null ) {
		throw new \LogicException( "toArray() is not implemented" );
	}

	public function count() {
		throw new \LogicException( "count() is not implemented" );
	}

}