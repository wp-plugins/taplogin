<?php
/**
 * Confirms authentication by querying AZid server
 *
 * @author tony
 */
class AuthConfirmation
{
	const MATRIX_AUTH_CONFIRMATION_PATH = "/confirm_auth.php";
	const MATRIX_NODE_ID = 1;

	/**
	 * @param int $employee_id
	 * @param string $auth_token
	 * @param string $url
	 * @param string $ip
	 * @throws AuthConfirmationException in case of any failure to do its job
	 */
	public static function confirm($employee_id, $auth_token, $url, $ip)
	{
		$arrParams = array(
			'node_id' => get_option('taplogin_node_id'),
			'token' => TokenManager::getMyToken(self::MATRIX_NODE_ID),
			'employee_id' => $employee_id,
			'auth_token' => $auth_token,
			'url' => $url,
			'ip' => $ip,
		);
		$confirm_url = EnvConf::PROTOCOL.'://www.azid.'.EnvConf::getTld('ru').self::MATRIX_AUTH_CONFIRMATION_PATH;
		$response = self::post($confirm_url, $arrParams);
		if (!$response)
			throw new AuthConfirmationException("no response");
		$objResponse = json_decode($response);
		if (!$objResponse)
			throw new AuthConfirmationException("json_decode failed for response: ".$response);
		if (isset($objResponse->error))
			throw new AuthConfirmationException("error from azid: ".$objResponse->error);
		
		return $objResponse;
	}
	
	
	private static function post($url, array $arrParams)
	{
		$objCurl = curl_init($url);
		curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($objCurl, CURLOPT_TIMEOUT, 30);
		curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 100);
		curl_setopt($objCurl, CURLOPT_HEADER, 0);
		curl_setopt($objCurl, CURLOPT_POST, 1);
		curl_setopt($objCurl, CURLOPT_POSTFIELDS, http_build_query($arrParams));
		if (EnvConf::ENV == 'dev'){
			curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($objCurl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$strResponse = curl_exec($objCurl);
		curl_close($objCurl);
		return $strResponse;
	}
}

class AuthConfirmationException extends Exception{
}

?>