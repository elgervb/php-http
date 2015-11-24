<?php
namespace http;

/**
 * Http Headers
 *
 * @see http://www.faqs.org/rfcs/rfc2616 for more information
 *     
 * @package web
 * @subpackage http
 */
class HttpHeaders
{

    /**
     * The headers
     *
     * @var ArrayObject
     */
    private $values;

    /**
     *
     * @var int the http status code
     */
    private $httpStatusCode;

    /**
     *
     * @var array The cookie information in key => value pairs
     */
    private $cookie;

    /**
     * Creates a new HttpHeaders
     *
     * @param array $aHeaders            
     * @param int $aHttpStatusCode            
     */
    public function __construct(array $aHeaders, $aHttpStatusCode, array $aCookie = null)
    {
        assert('is_int($aHttpStatusCode)');
        
        $this->values = new \ArrayObject($aHeaders);
        $this->httpStatusCode = $aHttpStatusCode;
        $this->cookie = $aCookie;
    }

    /**
     * Clears the cookie information
     */
    final public function clearCookies()
    {
        $this->cookie = null;
    }

    /**
     * Returns the cache control
     *
     * @return string the cache control
     */
    final public function getCacheControl()
    {
        return $this->getValue('cache-control');
    }

    /**
     * Returns the content length
     *
     * @return int The content length in bytes
     */
    final public function getContentLength()
    {
        return (int) $this->getValue('content-length');
    }

    /**
     * Returns the content type
     *
     * @return string the content type
     */
    final public function getContentType()
    {
        return $this->getValue('content-type');
    }

    /**
     * Returns the cookie string
     *
     * @return string The cookie string or an empty string when no cookie was set
     */
    final public function getCookie()
    {
        $result = "";
        if ($this->cookie !== null && count($this->cookie) > 0) {
            $result = "Cookie: ";
            
            foreach ($this->cookie as $cookie) {
                $result .= $cookie . "; ";
            }
            
            $result .= "\r\n";
        }
        
        return $result;
    }

    final public function getDate()
    {
        return $this->getValue('date');
    }

    /**
     * Returns the http status: HTTP/1.1 200 OK
     *
     * @return String
     */
    final public function getHttpStatus()
    {
        return $this->getValue('http_status');
    }

    /**
     * Returns the Http status code
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for more information on http 1.1 status codes
     *     
     * @return int
     */
    final public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    final public function getServer()
    {
        return $this->getValue('server');
    }

    /**
     * Get the location (redirect)
     *
     * @return String
     */
    final public function getLocation()
    {
        return $this->getValue('location');
    }

    /**
     * Returns the Http status eg 200, 301, 404
     *
     * @return string
     */
    final public function getStatus()
    {
        return $this->httpStatusCode;
    }

    /**
     * Returns the transfer encoding
     *
     * @return String
     */
    final public function getTransferEncoding()
    {
        return $this->getValue('transfer-encoding');
    }

    /**
     * Returns the value from the header.
     * If the value is empty, then this method returns an empty string
     *
     * @param String $aValue            
     * @return String
     */
    final public function getValue($aKey)
    {
        if ($this->values->offsetExists($aKey)) {
            return $this->values->offsetGet($aKey);
        }
        
        return "";
    }

    /**
     * Check for redirect
     *
     * @return boolean
     */
    final public function hasRedirect()
    {
        $location = $this->getValue('location');
        return ! empty($location);
    }

    /**
     * Parse HTTP response headers and returns a new HttpHeaders object
     *
     * @param String $string
     *            HTTP response headers collected from a HEAD request
     *            
     * @return HttpHeaders
     */
    final public static function parseHeaders($aHeaderString)
    {
        $aHeaderString = trim($aHeaderString);
        $headers = array();
        
        $lines = explode("\r\n", $aHeaderString);
        
        $headers['http_status'] = $lines[0];
        
        /* read HTTP status in first line */
        $m = null;
        preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $lines[0], $m);
        $statusCode = (int) $m[2];
        
        array_splice($lines, 0, 1); /* remove first line */
        
        // the cookie string
        $cookie = Array();
        
        foreach ($lines as $line) {
            list ($key, $val) = explode(': ', $line);
            
            $key = strtolower($key);
            $val = trim($val);
            
            if ($key === "set-cookie") {
                $cookie[] = rtrim($val, stristr($val, "; path"));
            } else {
                $headers[$key] = $val;
            }
        }
        
        $httpheaders = new HttpHeaders($headers, $statusCode, $cookie);
        
        return $httpheaders;
    }

    /**
     * To String
     *
     * @return string
     */
    final public function toString()
    {
        $result = "";
        
        foreach ($this->values as $key => $value) {
            $value = $this->values->get($key);
            $result .= $key . ": " . $value . "\r\n";
        }
        
        // add cookies
        $result .= $this->getCookie();
        
        return $result;
    }
}