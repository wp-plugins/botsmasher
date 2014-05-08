<?php

class botsmasherClient {

    /**
     * class constructor
     * @param   string  $apiURL the URL to send the query to
     * @param   string  $apiKey the key for the API request
     */
    public function __construct($apiURL, $apiKey) {
        $this -> apiURL = $apiURL;
        $this -> apiKey = $apiKey;
    }

    /**
     *  Set the query parameters for request
     * @param   array   $opts array of options for the request
     */
    public function setOpts($opts) {
        foreach ($opts AS $key => $val) {
            $this -> opts[$key] = $val;
        }
    }

	    /**
     * Checks the submitted information to make sure we will get
     * a good response from the API
     * @return bool
     */
    protected function validateOpts() {
	
        // 'key' is absolutely required by botsmasher
        if (FALSE == array_key_exists('key', $this -> opts)) {
            return FALSE;
        }

        // one of 'ip', 'email', or 'name' have to be set
        if ((!isset($this -> opts['ip'])) && (!isset($this -> opts['email'])) && (!isset($this -> opts['name']))) {
            return FALSE;
        }

        // action not set
        if (FALSE == array_key_exists('action', $this -> opts)){
            return FALSE;
        }		
        // action is required and must be 'check', 'submit', or 'clear'
        $actionVals = array('check', 'submit', 'clear');
        if ( !in_array( strtolower( $this -> opts['action'] ), $actionVals ) ) {
            return FALSE;
        }

        // all API keys are 64 characters
        if (strlen(trim($this -> opts['key'])) != 64) {
            return FALSE;
        }

        // if we got this far we're GTG
        return TRUE;

    }
	
    /**
     * Submits the request to the API
     * @param   bool    $printInfo  whether or not to print the output (usually for debugging only)
     * @return  string  the results, formatted as JSON
     */
    public function query($printInfo = false) {
	
		$this -> opts['key'] = $this -> apiKey;

        if ( TRUE == $printInfo ) {
            echo '<pre>';
            var_dump($this -> opts);
            echo '</pre>';
        }

        if ( FALSE == $this -> validateOpts() ) {
            if (TRUE == $printInfo) {
                echo 'INVALID OPTS';
            }
            return FALSE;
        }
		
		$args = array( 'method'=>'POST', 'body' => $this -> opts, 'headers' => '', 'sslverify' => false, 'timeout' => 30 );
		$result = wp_remote_post( $this -> apiURL, $args );
		
		if (TRUE == $printInfo) {
			echo "<pre>";
			print_r($result);
			echo "</pre>";
		}

		// Success?
		if ( !is_wp_error($result) ) {
			if ( $result['response']['code'] == 200 ) {
				$body = $result['body'];
				// some server errors return correct data, but with appended errors.
				// If so, remove appended error so data is parseable.
				$parts = explode( '}}}}', $body );
				$body = $parts[0].'}}}}';
				if ( !json_decode( $body ) ) {
					$body = $parts[0];
				}
			} 
		} else {
			bs_handle_exception( $result, 'is_wp_error' );
			$body = false;
		}
        //the results
        $this -> response = $body;
    }

    /**
     *
     * Just takes the JSON, makes sure everything was OK with it, and turns it into an array
     * @return array
     */
    public function decode() {
        // if there's no response then there's nothing to decode
        if ( ( FALSE == $this->response ) || ( !isset( $this->response ) ) ) {
            return FALSE;
        }
		
        $array = json_decode( $this->response, TRUE );
		
		if ( function_exists( 'json_last_error' ) ) {
			try {
				if ( is_null( $array ) ) {
					switch (json_last_error()) {
						case JSON_ERROR_DEPTH :
							$msg = 'Maximum stack depth exceeded';
							break;
						case JSON_ERROR_STATE_MISMATCH :
							$msg = 'Underflow or the modes mismatch';
							break;
						case JSON_ERROR_CTRL_CHAR :
							$msg = 'Unexpected control character found';
							break;
						case JSON_ERROR_SYNTAX :
							$msg = 'Syntax error, malformed JSON';
							break;
						case JSON_ERROR_UTF8 :
							$msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
							break;
						default :
							$msg = 'Unknown error';
							break;
					}
					throw new Exception($msg);
				}
			} catch (Exception $e) {
				bs_handle_exception( $e, $this->response );
				return FALSE;
			}
		}
        return $array;
    }

    /**
     * used for validation.
     * @return  bool    TRUE if bad guys were found, FALSE if all is well.
     */
    public function smash() {
        try {
            $array = $this -> decode();
            if (FALSE == $array) {
				if ( !$this -> apiKey ) {
					throw new Exception('No valid API key');
				} else {
					//throw new Exception('ERROR: NOT ABLE TO DECODE THE RESPONSE');
				}
            } else {
                if ($array['response']['summary']['code'] == 'failure') {
                    throw new Exception('BAD REQUEST: ' . $array['response']['summary']['description']);
                } else if ($array['response']['summary']['code'] == 'success') {
					if ( $array['response']['summary']['requesttype'] != 'check' ) {
						return true;
					} else {
						if ($array['response']['summary']['badguys'] == 'true') {
							return TRUE;
						} else {
							return FALSE;
						}
					}
                }
            }
        } catch (Exception $e) {
			bs_handle_exception( $e, $this->response );
			return null;
        }
    }	
}