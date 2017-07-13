<?php
function wp_check_password($password, $hash, $user_id = '') {
    global $wp_hasher;
 
    // If the hash is still md5...
    if ( strlen($hash) <= 32 ) {
    	if(!function_exists('hash_equals'))
    	{
    		if(strlen($str1) != strlen($str2))
    		{
    			return false;
            }
            else
            {
				$res = $str1 ^ $str2;
				$ret = 0;
				for($i = strlen($res) - 1; $i >= 0; $i--)
				{
					$ret |= ord($res[$i]);
				}
				$check = !$ret;
            }
        }
        else{
        	$check = hash_equals( $hash, md5( $password ) );
        }
        if ( $check && $user_id ) {
            // Rehash using new hash.
            wp_set_password($password, $user_id);
            $hash = wp_hash_password($password);
        }
 
        /**
         * Filters whether the plaintext password matches the encrypted password.
         *
         * @since 2.5.0
         *
         * @param bool       $check    Whether the passwords match.
         * @param string     $password The plaintext password.
         * @param string     $hash     The hashed password.
         * @param string|int $user_id  User ID. Can be empty.
         */
        return apply_filters( 'check_password', $check, $password, $hash, $user_id );
    }
 
    // If the stored hash is longer than an MD5, presume the
    // new style phpass portable hash.
    if ( empty($wp_hasher) ) {
        require_once('class-phpass.php');
        // By default, use the portable hash from phpass
        $wp_hasher = new PasswordHash(8, true);
    }
 
    $check = $wp_hasher->CheckPassword($password, $hash);
 
    /** This filter is documented in wp-includes/pluggable.php */
    return apply_filters( 'check_password', $check, $password, $hash, $user_id );
}

function apply_filters( $tag, $value ) {
    global $wp_filter, $wp_current_filter;
 
    $args = array();
 
    // Do 'all' actions first.
    if ( isset($wp_filter['all']) ) {
        $wp_current_filter[] = $tag;
        $args = func_get_args();
        _wp_call_all_hook($args);
    }
 
    if ( !isset($wp_filter[$tag]) ) {
        if ( isset($wp_filter['all']) )
            array_pop($wp_current_filter);
        return $value;
    }
 
    if ( !isset($wp_filter['all']) )
        $wp_current_filter[] = $tag;
 
    if ( empty($args) )
        $args = func_get_args();
 
    // don't pass the tag name to WP_Hook
    array_shift( $args );
 
    $filtered = $wp_filter[ $tag ]->apply_filters( $value, $args );
 
    array_pop( $wp_current_filter );
 
    return $filtered;
}