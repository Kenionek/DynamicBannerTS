<?php
/**
 * ServerQuery login. Copy this file to secret.php and fill it in.
 * secret.php is ignored by git and blocked from the web by .htaccess:
 * never commit it or put it anywhere it can be downloaded.
 * Tip: create a separate query login with only the rights the banner needs.
 */
return [
    'host'       => '127.0.0.1',   // your TeamSpeak server
    'query_port' => 10011,         // ServerQuery (raw) port
    'voice_port' => 9987,          // the virtual server people connect to
    'username'   => 'serveradmin',
    'password'   => 'CHANGE_ME',
];
