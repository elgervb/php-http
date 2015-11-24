<?php
namespace http;

/**
 * 
 * @author eaboxt
 *
 */
class HttpCookieManager
{

    /**
     * Stores all cookies of this request.
     * If we do not store the cookie now,
     * then a getCookie will still get the cookie set by the previous request.
     *
     * @var array
     */
    private $storedCookies = Array();

    /**
     *
     * @var HttpCookieManager
     */
    private static $instance;

    /**
     * Creates a new HttpCookieManager
     */
    public function __construct()
    {
        if (self::$instance !== null) {
            throw new \Exception($this);
        }
    }

    /**
     * Creates a new cookie which will expire in 10 years
     *
     * @param string $aName            
     * @param string $aValue            
     *
     * @return HttpCookie
     */
    public static function createCookieNoExpire($aName, $aValue)
    {
        return self::createCookie($aName, $aValue, 60 * 60 * 24 * 365 * 10/*10 years*/);
    }

    /**
     * Create a new cookie
     *
     * @param string $aName            
     * @param string $aValue            
     * @param int $aExpire
     *            Example: use 60*60*24*30 = 30 days
     *            
     * @return HttpCookie
     */
    public static function createCookie($aName, $aValue, $aExpire)
    {
        return new HttpCookie($aName, $aValue, $aExpire);
    }

    /**
     * Delete a cookie
     *
     * When deleting a cookie you should assure that the expiration date is in the past,
     * to trigger the removal mechanism in your browser.
     */
    public function deleteCookie(HttpCookie $aCookie)
    {
        $aCookie->setExpire(time() - 3600);
        $this->setCookie($aCookie);
    }

    /**
     * Get a cookie.
     *
     * @param string $aName            
     *
     * @return HttpCookie The cookie found or null when cookie has not been set
     */
    public function getCookie($aName)
    {
        $result = null;
        
        if (isset($this->storedCookies[$aName])) {
            $result = $this->storedCookies[$aName];
            assert('$result instanceof HttpCookie');
        } elseif (isset($_COOKIE[$aName])) {
            $result = new HttpCookie($aName, self::decode($_COOKIE[$aName]));
        }
        return $result;
    }

    /**
     *
     * @return HttpCookieManager
     */
    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = new HttpCookieManager();
        }
        
        return self::$instance;
    }

    /**
     * Check if a cookie has been set
     *
     * @param string $aName            
     * @return boolean true when cookie has been set, false when not
     */
    public function hasCookie($aName)
    {
        return isset($_COOKIE[$aName]) || isset($this->storedCookies[$aName]);
    }

    /**
     * Set a cookie
     *
     * @param HttpCookie $aCookie
     *            The cookie to set
     *            
     * @return boolean
     */
    public function setCookie(HttpCookie $aCookie)
    {
        assert('$aCookie->getPath() !== null && $aCookie->getPath() !== ""');
        
        $result = setcookie($aCookie->getName(), self::encode($aCookie->getValue()), $aCookie->getExpire(), $aCookie->getPath(), $aCookie->getDomain(), $aCookie->getSecure(), $aCookie->getHttponly());
        
        if ($result === true) {
            $this->storedCookies[$aCookie->getName()] = $aCookie;
        }
        return $result;
    }

    /**
     * Encodes a cookie
     *
     * @param string $aString            
     * @return string
     */
    public static function encode($aString)
    {
        return base64_encode(serialize($aString));
    }

    /**
     * Decodes a cookie
     *
     * @param string $aString            
     * @return string
     */
    public static function decode($aString)
    {
        return unserialize(base64_decode(($aString)));
    }
}