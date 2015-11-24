<?php
namespace http;

/**
 *
 * @author elger
 */
class HttpCookie
{

    const HOUR = 360;

    const DAY = 86400;

    const WEEK = 604800;

    const MONTH = 2678400;

    const YEAR = 31536000;

    /**
     *
     * @var String The name of the cookie.
     */
    private $name;

    /**
     *
     * @var mixed The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
     */
    private $value;

    /**
     *
     * @var int The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
     *      In other words, you'll most likely set this with the time() function
     *      plus the number of seconds before you want it to expire.
     *      time()+3600 = 1 hour
     *      time()+60*60*24*30 = 30 days
     */
    private $expire;

    /**
     * If set to '/', the cookie will be available within the entire domain.
     * If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     * The default value is the current directory that the cookie is being set in.
     *
     * @var String The path on the server in which the cookie will be available on.
     */
    private $path = '/';

    /**
     *
     * @var String The domain that the cookie is available.
     *      To make the cookie available on all subdomains of example.com then you'd set it to '.example.com'.
     *      The dot (.) is not required but makes it compatible with more browsers.
     */
    private $domain;

    /**
     *
     * @var boolean Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     *      When set to TRUE, the cookie will only be set if a secure connection exists. The default is FALSE.
     */
    private $secure = false;

    /**
     *
     * @var boolean When TRUE the cookie will be made accessible only through the HTTP protocol.
     *      This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     */
    private $httponly = false;

    /**
     * Creates a new cookie
     *
     * @param string $aName            
     * @param string $aValue            
     * @param int $aExpire            
     */
    public function __construct($aName = null, $aValue = null, $aExpire = null)
    {
        $this->name = $aName;
        $this->value = $aValue;
        $this->expire = $aExpire;
    }

    /**
     *
     * @return String
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     *
     * @return int
     */
    public function getExpire()
    {
        if ($this->expire === null) {
            $this->expire = time() + 3600;
        }
        
        return time() + $this->expire;
    }

    /**
     *
     * @return boolean
     */
    public function getHttponly()
    {
        return $this->httponly;
    }

    /**
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return String
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     *
     * @return boolean
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     *
     * @return unknown
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the name of the cookie
     *
     * @param string $aName            
     */
    public function setName($aName)
    {
        $this->name = $aName;
    }

    /**
     * Sets the value of the cookie
     *
     * @param string $aValue            
     */
    public function setValue($aValue)
    {
        $this->value = $aValue;
    }

    /**
     * Sets the expire time in seconds, use a Unix timestamp
     *
     * @param int $aExpire            
     */
    public function setExpire($aExpire)
    {
        assert('is_numeric($aExpire)');
        
        $this->expire = $aExpire;
    }

    /**
     * Sets the path for the cookie
     *
     * @param string $path            
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set a secure cookie
     *
     * @param boolean $secure            
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
    }

    /**
     * Set the Http only flag
     *
     * @param boolean $httponly            
     */
    public function setHttponly($httponly)
    {
        $this->httponly = $httponly;
    }
}