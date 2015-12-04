<?php
namespace http;

/**
 * HttpContext holds all http related classes
 *
 * @author elger
 */
class HttpContext
{
    private static $instance;
    /**
     *
     * @var \http\HttpRequest
     */
    private $request;

    /**
     *
     * @var \http\HttpResponse
     */
    private $response;

    /**
     *
     * @var \http\HttpSession
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (self::$instance !== null) {
            throw new \Exception('This is a singleton. Use the GET method to retrieve the instance');
        }
    }
    
    /**
     * Returns the singleton 
     * 
     * @return \http\HttpContext
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = new HttpContext();
        }
        
        return self::$instance;
    }

    /**
     * Returns the http request
     *
     * @return \http\HttpRequest
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = new HttpRequest();
        }
        return $this->request;
    }

    /**
     * Reurns the http response
     *
     * @return \http\HttpResponse
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->response = new HttpResponse();
        }
        return $this->response;
    }

    /**
     * Returns the cookie manager
     *
     * @return \http\HttpCookieManager
     */
    public function getCookieManager()
    {
        return HttpCookieManager::get();
    }

    /**
     * Returns the session
     *
     * @return \http\HttpSession
     */
    public function getSession()
    {
        if ($this->session === null) {
            $this->session = HttpSession::getInstance();
        }
        
        return $this->session;
    }
}