<?php

namespace TailgateWeb\Session;

class SessionStarter
{
    // session options
    private $options = [
        'name' => 'session_name',
        'lifetime' => 3600, // lifetime of the cookie in seconds
        'path' => '/', // path on the domain where the cookie will work
        'domain' => null, // domain of the cookie
        'secure' => false, // cookie should only be sent over secure connection
        'httponly' => true, // cookie can only be accessed through HTTP
        'cache_limiter' => 'nocache',
        'session_path' => null, // current session save path
    ];

    public function __construct($options = [])
    {   
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new \Exception("Session already started when it should not have.");
        }

        // overwrite options
        $this->options = array_merge($this->options, $options);

        $options = $this->options;
        $current = session_get_cookie_params();

        $lifetime = (int)($options['lifetime'] ?: $current['lifetime']);
        $path     = $options['path'] ?: $current['path'];
        $domain   = $options['domain'] ?: $current['domain'];
        $secure   = (bool)$options['secure'];
        $httponly = (bool)$options['httponly'];

        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        session_name($options['name']);
        session_cache_limiter($options['cache_limiter']);

        if (is_string($options['session_path'])) {
            session_save_path($options['session_path']);
        }

        ini_set('session.gc_maxlifetime', $lifetime);

        session_start();
    }
}
