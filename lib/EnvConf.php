<?php
/**
 * describes environment where the script runs.
 * 
 * EnvConf.php must not be put under CVS, please copy and edit EnvConf.sample.php 
 * in your working copy
 *
 * @author tony
 */
class EnvConf
{
	const ENV = 'prod'; // (dev|prod)
	const PROTOCOL = 'https'; // (http|https)
	const DEV_TLD = 'local'; // in dev environment, all TLDs are changed to this value
	
	public static function getTld($prod_tld)
	{
		return (self::ENV == 'dev') ? self::DEV_TLD : $prod_tld;
	}
}

?>