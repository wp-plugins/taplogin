<?php
/**
 * Confirms authentication by querying TeddyID server
 *
 * @author tony
 */
class AuthConfirmation extends MatrixCommunication
{
	const MATRIX_AUTH_CONFIRMATION_PATH = "/confirm_auth.php";
	const MATRIX_NODE_ID = 1;

	/**
	 * @param int $auth_token_id
	 * @param string $auth_token
	 * @param string $url
	 * @param string $ip
	 * @throws AuthConfirmationException in case of any failure to do its job
	 */
	public static function confirm($auth_token_id, $auth_token, $url, $ip)
	{
		$arrParams = array(
			'node_id' => get_option('taplogin_node_id'),
			'token' => TokenManager::getMyToken(self::MATRIX_NODE_ID),
			'auth_token_id' => $auth_token_id,
			'auth_token' => $auth_token,
			'url' => $url,
			'ip' => $ip,
		);
		$confirm_url = self::getMatrixServerUrl().self::MATRIX_AUTH_CONFIRMATION_PATH;
		$response = self::post($confirm_url, $arrParams);
		if (!$response)
			throw new AuthConfirmationException("no response");
		$objResponse = json_decode($response);
		if (!$objResponse)
			throw new AuthConfirmationException("json_decode failed for response: ".$response);
		if (isset($objResponse->error))
			throw new AuthConfirmationException("error from teddy: ".$objResponse->error);
		
		return $objResponse;
	}

}

class AuthConfirmationException extends Exception{
}

?>