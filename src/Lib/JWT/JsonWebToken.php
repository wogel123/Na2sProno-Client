<?php

namespace App\Lib\JWT;

use UnexpectedValueException;

/**
 * JSON Web UserToken implementation
 *
 * Minimum implementation used by Realtime auth, based on this spec:
 * http://self-issued.info/docs/draft-jones-json-web-token-01.html.
 *
 * @author Neuman Vong <neuman@twilio.com>
 */
class JsonWebToken
{
	/**
	 * @param string      $jwt    The JWT
	 * @param string|null $key    The secret key
	 * @param bool        $verify Don't skip verification process
	 *
	 * @return array The JWT's payload as a PHP object
	 */
	public static function decode($jwt, $key = null, $verify = true)
	{
		$tks = explode('.', $jwt);
		if (count($tks) != 3) {
			throw new UnexpectedValueException('Wrong number of segments');
		}
		list($headb64, $payloadb64, $cryptob64) = $tks;
		if (null === ($header = JsonWebToken::jsonDecode(JsonWebToken::urlsafeB64Decode($headb64)))
		) {
			throw new UnexpectedValueException('Invalid segment encoding');
		}
		if (null === $payload = JsonWebToken::jsonDecode(JsonWebToken::urlsafeB64Decode($payloadb64))
		) {
			throw new UnexpectedValueException('Invalid segment encoding');
		}
		$sig = JsonWebToken::urlsafeB64Decode($cryptob64);
		if ($verify) {
			if (empty($header->alg)) {
				throw new DomainException('Empty algorithm');
			}
			if ($sig != JsonWebToken::sign("$headb64.$payloadb64", $key, $header->alg)) {
				throw new UnexpectedValueException('Signature verification failed');
			}
		}
		return get_object_vars($payload);
	}
	/**
	 * @param object|array $payload PHP object or array
	 * @param string       $key     The secret key
	 * @param string       $algo    The signing algorithm
	 *
	 * @return string A JWT
	 */
	public static function encode($payload, $key, $algo = 'HS512')
	{
		$header = array('typ' => 'JWT', 'alg' => $algo);
		$segments = array();
		$segments[] = JsonWebToken::urlsafeB64Encode(JsonWebToken::jsonEncode($header));
		$segments[] = JsonWebToken::urlsafeB64Encode(JsonWebToken::jsonEncode($payload));
		$signing_input = implode('.', $segments);
		$signature = JsonWebToken::sign($signing_input, $key, $algo);
		$segments[] = JsonWebToken::urlsafeB64Encode($signature);
		return implode('.', $segments);
	}
	/**
	 * @param string $msg    The message to sign
	 * @param string $key    The secret key
	 * @param string $method The signing algorithm
	 *
	 * @return string An encrypted message
	 */
	public static function sign($msg, $key, $method = 'HS512')
	{
		$methods = array(
			'HS256' => 'sha256',
			'HS384' => 'sha384',
			'HS512' => 'sha512',
		);
		if (empty($methods[$method])) {
			throw new DomainException('Algorithm not supported');
		}
		return hash_hmac($methods[$method], $msg, $key, true);
	}
	/**
	 * @param string $input JSON string
	 *
	 * @return object Object representation of JSON string
	 */
	public static function jsonDecode($input)
	{
		$obj = json_decode($input);
		if (function_exists('json_last_error') && $errno = json_last_error()) {
			JsonWebToken::handleJsonError($errno);
		}
		else if ($obj === null && $input !== 'null') {
			throw new DomainException('Null result with non-null input');
		}
		return $obj;
	}
	/**
	 * @param object|array $input A PHP object or array
	 *
	 * @return string JSON representation of the PHP object or array
	 */
	public static function jsonEncode($input)
	{
		$json = json_encode($input);
		if (function_exists('json_last_error') && $errno = json_last_error()) {
			JsonWebToken::handleJsonError($errno);
		}
		else if ($json === 'null' && $input !== null) {
			throw new DomainException('Null result with non-null input');
		}
		return $json;
	}
	/**
	 * @param string $input A base64 encoded string
	 *
	 * @return string A decoded string
	 */
	public static function urlsafeB64Decode($input)
	{
		$remainder = strlen($input) % 4;
		if ($remainder) {
			$padlen = 4 - $remainder;
			$input .= str_repeat('=', $padlen);
		}
		return base64_decode(strtr($input, '-_', '+/'));
	}
	/**
	 * @param string $input Anything really
	 *
	 * @return string The base64 encode of what you passed in
	 */
	public static function urlsafeB64Encode($input)
	{
		return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
	}
	/**
	 * @param int $errno An error number from json_last_error()
	 *
	 * @return void
	 */
	private static function handleJsonError($errno)
	{
		$messages = array(
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
		);
		throw new DomainException(isset($messages[$errno])
			? $messages[$errno]
			: 'Unknown JSON error: ' . $errno
		);
	}
}