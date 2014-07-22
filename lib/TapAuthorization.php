<?php
/**
 * Authorize actions
 *
 * @author tony
 */
class TapAuthorization extends MatrixCommunication
{
	const MATRIX_AUTHORIZATION_PATH = "/authorize.php";
	const MATRIX_AUTHORIZATION_RESPONSE_PATH = "/get_authorization_response.php";
	const MATRIX_NODE_ID = 1;

	/**
	 * @param array $arrUser contains either employee_id or remote_id
	 * @param string $question
	 * @param string $callback_url
	 * @return Object that in case of success contains request_id
	 * @throws TapAuthorizationException in case of any failure to do its job
	 */
	public static function authorize(array $arrUser, $question, $callback_url = '')
	{
		return self::authorizeOrRequestData($arrUser, $question, null, null, $callback_url);
	}

	/**
	 * @param array $arrUser contains either employee_id or remote_id
	 * @param array $arrRequestedFields
	 * @param string $callback_url
	 * @return Object that in case of success contains request_id
	 * @throws TapAuthorizationException in case of any failure to do its job
	 */
	public static function requestData(array $arrUser, array $arrRequestedFields, $domain, $callback_url = '')
	{
		return self::authorizeOrRequestData($arrUser, null, join(',', $arrRequestedFields), $domain, $callback_url);
	}

	/**
	 * @param array $arrUser contains either employee_id or remote_id
	 * @param string $question
	 * @param string $requested_fields
	 * @param string $callback_url
	 * @return Object that in case of success contains request_id
	 * @throws TapAuthorizationException in case of any failure to do its job
	 */
	private static function authorizeOrRequestData(array $arrUser, $question, $requested_fields, $domain, $callback_url = '')
	{
		if ($callback_url && EnvConf::ENV == 'prod' && !preg_match('/^https:\/\//i', $callback_url))
			throw new Exception("callback url must be https!");
		$arrParams = array(
			'node_id' => get_option('taplogin_node_id'),
			'token' => TokenManager::getMyToken(self::MATRIX_NODE_ID),
			'callback_url' => $callback_url,
		);
		
		if ($question)
			$arrParams['question'] = $question;
		elseif ($requested_fields && $domain){
			$arrParams['requested_fields'] = $requested_fields;
			$arrParams['domain'] = $domain;
		}
		else
			throw new Exception("neither question nor fields");
		
		if (isset($arrUser['employee_id']))
			$arrParams['employee_id'] = $arrUser['employee_id'];
		elseif (isset($arrUser['remote_id']))
			$arrParams['remote_id'] = $arrUser['remote_id'];
		else
			throw new Exception("neither employee_id nor remote_id");
		
		$url = self::getMatrixServerUrl().self::MATRIX_AUTHORIZATION_PATH;
		$response = self::post($url, $arrParams);
		if (!$response)
			return self::genRecoverableErrorResponse("no response");
		$objResponse = json_decode($response);
		if (!$objResponse)
			return self::genRecoverableErrorResponse("json_decode failed for response: ".$response);
		
		return $objResponse;
	}
	
	/**
	 * 
	 * @param int $request_id
	 * @return string 'Y' or 'N' or null
	 * @throws Exception
	 */
	public static function getAuthorizationResponse($request_id)
	{
		$arrParams = array(
			'node_id' => get_option('taplogin_node_id'),
			'token' => TokenManager::getMyToken(self::MATRIX_NODE_ID),
			'request_id' => $request_id,
		);
		$url = self::getMatrixServerUrl().self::MATRIX_AUTHORIZATION_RESPONSE_PATH;
		$response = self::post($url, $arrParams);
		if (!$response)
			return self::genRecoverableErrorResponse("no response");
		$objResponse = json_decode($response);
		if (!$objResponse)
			return self::genRecoverableErrorResponse("json_decode failed for response: ".$response);
		if ($objResponse->result != 'ok')
			throw new Exception("failed to get authorization response: ".$objResponse->error);
		return $objResponse;
	}


	private static function genRecoverableErrorResponse($error)
	{
		$objResponse = new stdClass();
		$objResponse->error = $error;
		$objResponse->error_code = '_COMMS';
		$objResponse->error_type = 'recoverable';
		$objResponse->result = 'error';
		return $objResponse;
	}

}

class TapAuthorizationException extends Exception{
}

?>