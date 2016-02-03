<?php

/**
 * Class DefaultSettings
 *
 * Rename this file and class to Settings and file out your individual settings
 */
class DefaultSettings
{
    //The name of the service to monitor, as it would be used with the service command (e.g. openvpn)
    static $service = '';
    //Exact output of calling: (prompt)# service {service} status
    static $expected_response = '';

    //email settings
    static $from_email = "no-reply@localhost";
    static $from_name = "System";
    //Email to send alarms to
    static $to = '';
    static $subject = '{service} error';
    static $message = '{service} failed to restart. See error log below.<br/><br/>';
    static $smtp_server = '';
    static $smtp_port = 587;
    static $smtp_username = '';
    static $smtp_password = '';
    static $smtp_debug_level = 0; //See PHPMailer documentation to learn more

    //File path used as a lock to know watchdog failed to prevent it from running util you fix the service
    static $lock_file = "/tmp/watchdog.lock";

    /**
     * What to do when the service stops running
     */
    static public function on_failure() {}

    /**
     * What to do if watchdog successfully restart your service
     */
    static public function on_success() {}

    /**
     * What to do when resetting after a failure
     */
    static public function reset() {}
}
