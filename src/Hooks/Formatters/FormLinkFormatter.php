<?php

namespace Wikibase\Lexeme\Hooks\Formatters;

use HtmlArmor;
use Language;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Formatters\LexemeTermFormatter;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class FormLinkFormatter implements EntityLinkFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var DefaultEntityLinkFormatter
	 */
	private $linkFormatter;

	/**
	 * @var LexemeTermFormatter
	 */
	private $representationsFormatter;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct(
		EntityLookup $entityLookup,
		DefaultEntityLinkFormatter $linkFormatter,
		LexemeTermFormatter $representationsFormatter,
		Language $language
	) {
		$this->entityLookup = $entityLookup;
		$this->linkFormatter = $linkFormatter;
		$this->language = $language;
		$this->representationsFormatter = $representationsFormatter;
	}

	public function getHtml( EntityId $entityId, array $labelData = null ) {
		Assert::parameterType( FormId::class, $entityId, '$entityId' );

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => new HtmlArmor(
					$this->representationsFormatter->format( $this->getRepresentations( $entityId ) )
				),
			]
		);
	}

	private function getRepresentations( FormId $formId ) : TermList {
		$form = $this->entityLookup->getEntity( $formId );

		/** @var Form $form */
		return $form->getRepresentations();
	}

	public function getTitleAttribute(
		Title $title,
		array $labelData = null,
		array $descriptionData = null
	) {
		// TODO: return the right thing here once defined and technically possible
		return $title->getFragment();
	}

}
