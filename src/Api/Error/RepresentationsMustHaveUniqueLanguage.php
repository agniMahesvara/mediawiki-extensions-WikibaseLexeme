<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
class RepresentationsMustHaveUniqueLanguage implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $languageFieldPath;

	/**
	 * @var string
	 */
	private $language;

	public function __construct(
		$parameterName,
		array $languageFieldPath,
		$language
	) {
		$this->parameterName = $parameterName;
		$this->languageFieldPath = $languageFieldPath;
		$this->language = $language;
	}

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibase-lexeme-api-error-representations-language-not-unique',
			[ $this->language ]
		);
		return new \ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $this->parameterName,
				'fieldPath' => $this->languageFieldPath
			]
		);
	}

}