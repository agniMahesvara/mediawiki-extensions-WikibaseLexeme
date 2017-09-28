<?php

namespace Wikibase\Lexeme\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpAddForm;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\DataModel\Serialization\FormSerializer;
use Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer;
use Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\EntitySavingHelper;
use Wikibase\Summary;

class AddForm extends ApiBase {

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var AddFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ExternalLexemeSerializer
	 */
	private $lexemeSerializer;

	/**
	 * @var FormSerializer
	 */
	private $formSerializer;

	/**
	 * @return AddForm
	 */
	public static function newFromGlobalState( \ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$formSerializer = new FormSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new AddForm(
			$mainModule,
			$moduleName,
			new AddFormRequestParser( $wikibaseRepo->getEntityIdParser() ),
			$formSerializer,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			}
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		callable $entitySavingHelperInstantiator,
		callable $errorReporterInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->formSerializer = $formSerializer;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		//FIXME: Error reporting
		//FIXME: Response structure? - Added form
		//TODO: Documenting response structure. Is it possible?

		$parserResult = $this->requestParser->parse( $this->extractRequestParams() );

		if ( $parserResult->hasErrors() ) {
			$errorMessage = $parserResult->getErrors()[0];
			if ( is_array( $errorMessage ) ) {
				$errorMessage[0] = 'wikibase-lexeme-api-addform-' . $errorMessage[0];
			} elseif ( is_string( $errorMessage ) ) {
				$errorMessage = 'wikibase-lexeme-api-addform-' . $errorMessage;
			}

			$this->errorReporter->dieWithError( $errorMessage, 'invalid-param' );
		}

		$request = $parserResult->getRequest();

		$lexeme = $this->entitySavingHelper->loadEntity( $request->getLexemeId() );
		$newForm = $request->addFormTo( $lexeme );
		//FIXME: Handle failure
		//FIXME: ACHTUNG! attemptSaveEntity() uses 'baserevid' internally which should not be used!
		$status = $this->entitySavingHelper->attemptSaveEntity( $lexeme, new Summary() );

		$apiResult = $this->getResult();

		$serializedForm = $this->formSerializer->serialize( $newForm );

		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'form', $serializedForm );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				'lexemeId' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'data' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				],
				'token' => null,
				'bot' => [
					self::PARAM_TYPE => 'boolean',
					self::PARAM_DFLT => false,
				]
			]
		);
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return true;
	}

}
