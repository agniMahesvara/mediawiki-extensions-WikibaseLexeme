<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiPageSubEntityMetaDataAccessor;
use Wikibase\Lexeme\DataAccess\Store\NullLabelDescriptionLookup;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\GrammaticalFeatureItemIdsExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LanguageItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexicalCategoryItemIdExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Storage\SenseLabelDescriptionLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lexeme\MediaWiki\Content\LexemeHandler;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LanguageChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LemmaChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexemeChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\LexicalCategoryChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseListChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Presentation\Diff\ItemReferenceDifferenceVisualizer;
use Wikibase\Lexeme\Presentation\Diff\LexemeDiffVisualizer;
use Wikibase\Lexeme\Presentation\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Rdf\LexemeRdfBuilder;
use Wikibase\Lexeme\Presentation\View\LexemeMetaTagsCreator;
use Wikibase\Lexeme\Presentation\View\LexemeViewFactory;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

return [
	'lexeme' => [
		'storage-serializer-factory-callback' => function ( SerializerFactory $serializerFactory ) {
			return new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			);
		},
		'view-factory-callback' => function (
			Language $language,
			LanguageFallbackChain $fallbackChain,
			EntityDocument $entity,
			EntityInfo $entityInfo
		) {
			$saveMessageKey =
				( MediaWikiServices::getInstance()->getMainConfig()->get( 'EditSubmitButtonLabelPublish' ) )
					? 'wikibase-publish' : 'wikibase-save';

			$factory = new LexemeViewFactory(
				$language,
				$fallbackChain,
				$entityInfo,
				$saveMessageKey
			);

			return $factory->newLexemeView();
		},
		'meta-tags-creator-callback' => function () {
			return new LexemeMetaTagsCreator(
				RequestContext::getMain()
					->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
					->escaped(),
				WikibaseRepo::getDefaultInstance()
					->getLanguageFallbackLabelDescriptionLookupFactory()
					->newLabelDescriptionLookup( \Language::factory( 'en' ) )
			);
		},
		'content-model-id' => LexemeContent::CONTENT_MODEL_ID,
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		'entity-factory-callback' => function () {
			return new Lexeme();
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$statementChangeOpDeserializer = new ClaimsChangeOpDeserializer(
				$wikibaseRepo->getExternalFormatStatementDeserializer(),
				$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
			);
			$itemValidator = new EntityExistsValidator( $wikibaseRepo->getEntityLookup(), 'item' );
			$lexemeChangeOpDeserializer = new LexemeChangeOpDeserializer(
				new LemmaChangeOpDeserializer(
				// TODO: WikibaseRepo should probably provide this validator?
				// TODO: WikibaseRepo::getTermsLanguage is not necessarily the list of language codes
				// that should be allowed as "languages" of lemma terms
					new LexemeTermSerializationValidator(
						new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
					),
					// TODO: move to setting, at least change to some reasonable hard-coded value
					new LemmaTermValidator( 1000 ),
					$wikibaseRepo->getStringNormalizer()
				),
				new LexicalCategoryChangeOpDeserializer(
					$itemValidator,
					$wikibaseRepo->getStringNormalizer()
				),
				new LanguageChangeOpDeserializer(
					$itemValidator,
					$wikibaseRepo->getStringNormalizer()
				),
				$statementChangeOpDeserializer,
				new FormListChangeOpDeserializer(
					new FormIdDeserializer( $wikibaseRepo->getEntityIdParser() ),
					new FormChangeOpDeserializer(
						$wikibaseRepo->getEntityLookup(),
						$wikibaseRepo->getEntityIdParser(),
						WikibaseLexemeServices::getEditFormChangeOpDeserializer()
					)
				),
				new SenseListChangeOpDeserializer(
					new SenseIdDeserializer( $wikibaseRepo->getEntityIdParser() ),
					new SenseChangeOpDeserializer(
						$wikibaseRepo->getEntityLookup(),
						$wikibaseRepo->getEntityIdParser(),
						new EditSenseChangeOpDeserializer(
							new GlossesChangeOpDeserializer(
								new TermDeserializer(),
								$wikibaseRepo->getStringNormalizer(),
								new LexemeTermSerializationValidator(
									new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
								)
							)
						)
					)
				)
			);
			$lexemeChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $lexemeChangeOpDeserializer;
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
		},
		'entity-diff-visualizer-callback' => function (
			MessageLocalizer $messageLocalizer,
			ClaimDiffer $claimDiffer,
			ClaimDifferenceVisualizer $claimDiffView,
			SiteLookup $siteLookup,
			EntityIdFormatter $entityIdFormatter
		) {
			$basicEntityDiffVisualizer = new BasicEntityDiffVisualizer(
				$messageLocalizer,
				$claimDiffer,
				$claimDiffView,
				$siteLookup,
				$entityIdFormatter
			);

			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
				->getEntityIdFormatter( $wikibaseRepo->getUserLanguage() );

			return new LexemeDiffVisualizer(
				$messageLocalizer,
				$basicEntityDiffVisualizer,
				$claimDiffer,
				$claimDiffView,
				new ItemReferenceDifferenceVisualizer(
					$entityIdFormatter
				)
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$repo->getEntityLookup(),
				$repo->getEntityIdParser(),
				new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
					$repo->getTermLookup(),
					$repo->getLanguageFallbackChainFactory()->newFromLanguage( $repo->getUserLanguage() )
				),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'link-formatter-callback' => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();

			return new LexemeLinkFormatter(
				$repo->getEntityLookup(),
				new DefaultEntityLinkFormatter( $language ),
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
						->escaped()
				),
				$language
			);
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LexemeIdHtmlFormatter(
				$repo->getEntityLookup(),
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				new MediaWikiLocalizedTextProvider( $language )
			);
		},
		'entity-reference-extractor-callback' => function () {
			$statementEntityReferenceExtractor = new StatementEntityReferenceExtractor(
				WikibaseRepo::getDefaultInstance()->getLocalItemUriParser()
			);
			return new EntityReferenceExtractorCollection( [
				new LanguageItemIdExtractor(),
				new LexicalCategoryItemIdExtractor(),
				new GrammaticalFeatureItemIdsExtractor(),
				$statementEntityReferenceExtractor,
				new FormsStatementEntityReferenceExtractor( $statementEntityReferenceExtractor ),
				new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor ),
			] );
		},
	],
	'form' => [
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$repo->getEntityLookup(),
				$repo->getEntityIdParser(),
				new NullLabelDescriptionLookup(),
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$formChangeOpDeserializer = new FormChangeOpDeserializer(
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getEntityIdParser(),
				WikibaseLexemeServices::getEditFormChangeOpDeserializer()
			);
			$formChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $formChangeOpDeserializer;
		},
		'entity-factory-callback' => function () {
			return new BlankForm();
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
		},
		'link-formatter-callback' => function ( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$requestContext = RequestContext::getMain();

			return new FormLinkFormatter(
				$repo->getEntityLookup(),
				new DefaultEntityLinkFormatter( $language ),
				new LexemeTermFormatter(
					$requestContext
						->msg( 'wikibaselexeme-formidformatter-separator-multiple-representation' )
						->escaped()
				),
				$language
			);
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$titleLookup = $repo->getEntityTitleLookup();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new FormIdHtmlFormatter(
				$repo->getEntityRevisionLookup(),
				$languageLabelLookup,
				$titleLookup,
				new MediaWikiLocalizedTextProvider( $language ),
				new RedirectedLexemeSubEntityIdHtmlFormatter( $titleLookup )
			);
		},
		'entity-metadata-accessor-callback' => function ( $dbName, $repoName ) {
			$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
			$entityQuery = new EntityIdLocalPartPageTableEntityQuery(
				$entityNamespaceLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			);
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getDefaultInstance()->getLocalRepoWikiPageMetaDataAccessor()
			);
		},
	],
	'sense' => [
		// TODO lexemes and forms have identical content-handler-factory-callback, extract
		'content-handler-factory-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$config = MediaWikiServices::getInstance()->getMainConfig();
			if ( $config->has( 'LexemeLanguageCodePropertyId' ) ) {
				$lcID = $config->get( 'LexemeLanguageCodePropertyId' );
			} else {
				$lcID = null;
			}

			return new LexemeHandler(
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityConstraintProvider(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdLookup(),
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseRepo->getFieldDefinitionsByType( Lexeme::ENTITY_TYPE )
			);
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			$repo = WikibaseRepo::getDefaultInstance();
			$entityLookup = $repo->getEntityLookup();
			$userLanguage = $repo->getUserLanguage();
			$senseLabelDescriptionLookup = new SenseLabelDescriptionLookup(
				$entityLookup,
				$repo->getLanguageFallbackChainFactory()->newFromLanguage( $userLanguage ),
				new MediaWikiLocalizedTextProvider( $userLanguage )
			);

			return new Wikibase\Repo\Api\EntityIdSearchHelper(
				$entityLookup,
				$repo->getEntityIdParser(),
				$senseLabelDescriptionLookup,
				$repo->getEntityTypeToRepositoryMapping()
			);
		},
		'changeop-deserializer-callback' => function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$senseChangeOpDeserializer = new SenseChangeOpDeserializer(
				$wikibaseRepo->getEntityLookup(),
				$wikibaseRepo->getEntityIdParser(),
				new EditSenseChangeOpDeserializer(
					new GlossesChangeOpDeserializer(
						new TermDeserializer(),
						$wikibaseRepo->getStringNormalizer(),
						new LexemeTermSerializationValidator(
							new LexemeTermLanguageValidator( WikibaseLexemeServices::getTermLanguages() )
						)
					)
				)
			);
			$senseChangeOpDeserializer->setContext(
				ValidationContext::create( EditEntity::PARAM_DATA )
			);
			return $senseChangeOpDeserializer;
		},
		'entity-factory-callback' => function () {
			return new BlankSense();
		},
		'rdf-builder-factory-callback' => function (
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) {
			$rdfBuilder = new LexemeRdfBuilder(
				$vocabulary,
				$writer,
				$tracker
			);
			$rdfBuilder->addPrefixes();
			return $rdfBuilder;
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new SenseIdHtmlFormatter(
				$repo->getEntityTitleLookup(),
				$repo->getEntityRevisionLookup(),
				new MediaWikiLocalizedTextProvider( $language ),
				$repo->getLanguageFallbackChainFactory()->newFromLanguage( $language ),
				new LanguageFallbackIndicator( $repo->getLanguageNameLookup() )
			);
		},
		'entity-metadata-accessor-callback' => function ( $dbName, $repoName ) {
			$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
			$entityQuery = new EntityIdLocalPartPageTableEntityQuery(
				$entityNamespaceLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			);
			return new MediaWikiPageSubEntityMetaDataAccessor(
				WikibaseRepo::getDefaultInstance()->getLocalRepoWikiPageMetaDataAccessor()
			);
		},
	],
];
