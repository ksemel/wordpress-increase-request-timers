<?php
/*
Plugin Name: Increase request timeouts in the admin
Description: Update the default timeouts from 5 to 15 seconds for cURL/http calls.  This is useful in a VM where calls may have to pass through your host machine before connecting, or on cheap hosting when stuff may be laggy.
Author: Katherine Semel
Version: 1.0
Author URI: http://bonsaibudget.com
*/

class Increase_Request_Timeouts {
    static $request_timeout;
    
    /*
        Init hooks
    */
    function Increase_Request_Timeouts() {
        self::$request_timeout = apply_filters( 'increase_request_timeouts_seconds', 15 );

        // Increase the timeouts to 15 seconds from 5
        add_filter( 'http_request_timeout', array( $this, 'update_request_timeout' ) );
        add_filter( 'http_request_args' , array( $this, 'update_request_args' ), 10, 2 );
        
        // Now with extra brute-force assurance of timeout setting!
        add_action( 'http_api_curl', array( $this, 'update_curl_opts' ), 100 );

        // Debugging information
        add_action( 'http_api_debug', array( $this, 'debug_http_call' ), 10, 5 ) ;
    }

    /*
        This updates the default timeout variables using the standard filters, but other plugins may also be changing this
    */
    function update_request_timeout( $timeout ) {
        if ( $timeout < self::$request_timeout ) {
            // don't mess with longer timeouts that may have been set higher already
            $timeout = self::$request_timeout;
        }
        return $timeout;
    }

    /*
        This updates the array of request args, but other plugins may also be changing this
    */
    function update_request_args( $r, $url ) {
        if ( $r['timeout'] < self::$request_timeout ) {
            // don't mess with longer timeouts that may have been set higher already
            $r['timeout'] = self::$request_timeout;
        }
        return $r;
    }

    /*
        This is run right before curl call is executed and overrides everything, so the other filters are unnecessary for curl.
        This runs late so it is your best bet for getting that timeout set
    */
    function update_curl_opts( $handle ) {
        // mess with timeouts without regard at this step
        // everyone gets a self::$request_timeout!
        curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, self::$request_timeout );
        curl_setopt( $handle, CURLOPT_TIMEOUT, self::$request_timeout );
    }

    /*
        So many debugs
        OMG so much data
    */
    function debug_http_call( $response, $string, $class, $args, $url ) {
        if ( ! WP_DEBUG ) {
            return;
        }
        
        if ( apply_filters( 'increase_request_timeouts_debug', false ) ) {
            
            // This gets a lot of data about every wp_remote request
            if ( is_array( $response ) || is_wp_error( $response ) ) {
                error_log( 'HTTP response: '. print_r( $response, true ) );
            } else {
                error_log( 'HTTP response: '. $response );
            }

            error_log( 'HTTP string: '. $string );
            error_log( 'HTTP class: '. $class );
            
            if ( is_array( $args ) ) {
                error_log( 'HTTP args: '. print_r( $args, true ) );
            } else {
                error_log( 'HTTP args: '. $args );
            }
            
            error_log( 'HTTP url: '. $url );
        }
    }
}

if ( is_admin() || apply_filters( 'increase_request_timeouts_locations', false ) ) {
    $Increase_Request_Timeouts = new Increase_Request_Timeouts();
}
