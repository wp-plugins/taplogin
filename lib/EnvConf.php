<?php
/**
 * describes environment where the script runs.
 * 
 * @author tony
 */
class EnvConf extends Stateless
{
	const ENV = 'prod'; // (dev|prod)
	const PROTOCOL = 'https'; // (http|https)
	const DEV_TLD = 'local'; // in dev environment, all TLDs are changed to this value
	const MATRIX_LOCATION = 'prod'; // (prod|dev|same)
	
	public static function getTld($prod_tld)
	{
		return (self::ENV == 'dev') ? self::DEV_TLD : $prod_tld;
	}
}

?>