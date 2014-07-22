<?php

/**
 * Description of MatrixCommunication
 *
 * @author tony
 */
class MatrixCommunication extends Stateless
{
	const RESPONSE_TIMOUT = 30;
	const CONNECT_TIMOUT = 100;
	
    public static function getMatrixServerUrl() {
		switch (EnvConf::MATRIX_LOCATION){
			case 'prod': return 'https://www.teddyid.com';
			case 'dev': return 'https://teddyid.matrixdev.ru';
			case 'same': return EnvConf::PROTOCOL . '://www.teddyid.' . EnvConf::getTld('com');
			default: throw new Exception("unrecognized MATRIX_LOCATION: ".EnvConf::MATRIX_LOCATION);
		}
    }
	
	protected static function post($url, array $rgPostFields)
	{
		$objCurl = curl_init($url);
		curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($objCurl, CURLOPT_TIMEOUT, static::RESPONSE_TIMOUT);
		curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, static::CONNECT_TIMOUT);
		curl_setopt($objCurl, CURLOPT_HEADER, 0);
		curl_setopt($objCurl, CURLOPT_POST, 1);
		curl_setopt($objCurl, CURLOPT_POSTFIELDS, http_build_query($rgPostFields));
		if (EnvConf::ENV == 'dev'){
			curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($objCurl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$strResponse = curl_exec($objCurl);
		curl_close($objCurl);
		return $strResponse;
	}

}

?>