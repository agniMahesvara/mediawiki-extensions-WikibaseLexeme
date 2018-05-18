<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermTextCanNotBeEmpty implements ApiError {

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'wikibaselexeme-api-error-lexeme-term-text-cannot-be-empty',
			[]
		);
		return new \ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $parameterName,
				'fieldPath' => $path
			]
		);
	}

}