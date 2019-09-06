<?php

namespace TailgateWeb\Session;

use ArrayAccess;
use Countable;

class Session implements ArrayAccess, Countable
{
    // holds the class instance
    private static $instance;

    // holds the $_SESSION
    private $session;

    // session options
    private $options = [
        'name' => 'Tailgate',
        'lifetime' => 3600, // lifetime of the cookie in seconds
        'path' => null, // path where information is stored
        'domain' => null, // domain of the cookie
        'secure' => false, // cookie should only be sent over secure connection
        'httponly' => true, // cookie can only be accessed through the HTTP protocol
        'cache_limiter' => 'nocache',
    ];

    private function __construct($options = [])
    {   
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new \Exception("Session already started when it should not have.");
        }

        // overwrite options
        $keys = array_keys($this->options);
        foreach ($keys as $key) {
            if (array_key_exists($key, $options)) {
                $this->options[$key] = $options[$key];
            }
        }

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
        session_start();

        $this->session = &$_SESSION;
    }

    /**
     * [startSession description]
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    public static function startSession($options = [])
    {
        if (null === static::$instance) {
            static::$instance = new static($options);
        }

        return static::$instance;
    }

    /**
     * [get description]
     * @param  [type] $key     [description]
     * @param  [type] $default [description]
     * @return [type]          [description]
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->session)) {
            return $this->session[$key];
        }
        return $default;
    }

    /**
     * [set description]
     * @param [type] $key   [description]
     * @param [type] $value [description]
     */
    public function set($key, $value)
    {
        $this->session[$key] = $value;
    }

    /**
     * [delete description]
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function delete($key)
    {
        if (array_key_exists($key, $this->session)) {
            unset($this->session[$key]);
        }
    }

    /**
     * [clearAll description]
     * @return [type] [description]
     */
    public function clearAll()
    {
        $this->session = [];
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->session);
    }

    public function __unset($key)
    {
        $this->delete($key);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetExists($offset) {
        return isset($this->session[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->session[$offset]);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function count($key = null)
    {
        if (isset($key)) {
            return count($this->get($key));
        }
        return count($this->session);
    }

    public function destroy()
    {
        $this->session = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
