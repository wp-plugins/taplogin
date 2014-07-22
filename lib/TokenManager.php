<?php
/**
 * Generate and validate tokens for node-to-node interaction
 *
 * @author tony
 */
class TokenManager extends Stateless
{
	public static function calculateToken($from_node_id, $to_node_id, $from_secret_key)
	{
		// any deterministic hash will do
		return sha1("from=".$from_node_id.";to=".$to_node_id.";".$from_secret_key);
	}
	
	public static function getMyToken($to_node_id)
	{
		return self::calculateToken(get_option('taplogin_node_id'), $to_node_id, get_option('taplogin_secret_key'));
	}
	
}

?>