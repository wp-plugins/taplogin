<?php
/**
 * Generate cryptographically secure random strings
 *
 * @author tony
 */
class SecureRandom
{
	/**
	 * 
	 * @param int $len
	 * @param boolean $bTolerateInsecure
	 * @return string
	 * @throws Exception
	 */
	public static function genRandomBinaryString($len, $bTolerateInsecure = false)
	{
		if (function_exists('openssl_random_pseudo_bytes')){
			$bCryptoStrong = false;
			$bytes = openssl_random_pseudo_bytes($len, $bCryptoStrong);
			if (!$bCryptoStrong)
				throw new Exception("no crypto strong PRNG available");
		}
		elseif (function_exists('mcrypt_create_iv'))
			$bytes = mcrypt_create_iv($len);
		else{
			if (!$bTolerateInsecure)
				throw new Exception("neither openssl nor mcrypt is installed");
			$bytes = substr(sha1(uniqid(@$_SERVER['REMOTE_ADDR'], true). true), 0, $len); // max 20 bytes
		}
		return $bytes;
	}
	
	public static function genRandomBase64String($len = 40, $bTolerateInsecure = false)
	{
		$bin = self::genRandomBinaryString(ceil($len*3.0/4.0), $bTolerateInsecure);
		$b64 = base64_encode($bin);
		$b64 = substr($b64, 0, $len);
		
		// these two replacements make urlencoding unnecessary
		$b64 = str_replace('=', '-', $b64);
		$b64 = str_replace('+', '.', $b64);
		
		return $b64;
	}
	
	public static function genRandomHexString($len = 40, $bTolerateInsecure = false)
	{
		$bin = self::genRandomBinaryString(ceil($len/2.0), $bTolerateInsecure);
		$hex = bin2hex($bin);
		$hex = substr($hex, 0, $len);
		return $hex;
	}
	
	/**
	 * Generate syllable based password that is easier to pronounce and remember
	 *
	 * @param int $length password length
	 * @param boolean $bTolerateInsecure
	 * @return string
	 */
	public static function genRandomSyllables($length = 8, $bTolerateInsecure = false)
	{
		$vowels="aeiouy";
		$consonants="qwrtpsdfghjklzxcvbnm";
		$bytes = self::genRandomBinaryString($length, $bTolerateInsecure);

		$pass='';
		for($i=0;$i<$length;$i++)
		{
			if ($i%2==0)
				$pass.=$consonants[ord($bytes[$i])%20];
			else
				$pass.=$vowels[ord($bytes[$i])%6];
		}

		return $pass;
	}
}


?>