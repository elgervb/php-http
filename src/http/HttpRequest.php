<?php
namespace http;

use http\filter\ITextFilter;
use http\filter\impl\InputFilterXss;

/**
 * A Http request received from the browser.
 * It can also handle POST & GET vars
 */
class HttpRequest
{

    const METHOD_DELETE = 'DELETE';

    const METHOD_GET = 'GET';

    const METHOD_HEAD = 'HEAD';

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';
    
    const METHOD_PATCH = 'PATCH';

    /**
     *
     * @var \ArrayObject
     */
    private $filters;

    private $data;

    private $requestHeaders;

    /**
     * Creates a new HttpRequest
     */
    public function __construct()
    {
        $this->filters = new \ArrayObject();
        $this->filters->append(new InputFilterXss());
        
        // support other HTTP methods (like PUT or patch) and other content types
        if ( preg_match('/application\/json/i', $this->getContentType()) ) {
            $this->data = json_decode(file_get_contents("php://input"), true);
        } else {
            parse_str(file_get_contents("php://input"), $data);
            $this->data = $data;
        }

        if (is_array($this->data) && count($this->data) > 0) {
            foreach ($this->data as $key => $value) {
                if (! isset($_POST[$key]))
                    $_POST[$key] = $value;
            }
        }
    }

    /**
     * Adds a new filter to filter post and get request variables
     *
     * @param $aFilter ITextFilter            
     */
    public function addInputFilter(ITextFilter $aFilter)
    {
        $this->filters->append($aFilter);
    }

    /**
     * Returns the number of post items of the same key
     *
     * @param $aPostKey string
     *            The post key
     *            
     * @return int
     */
    public function countPost($aPostKey)
    {
        if (! $this->hasPost($aPostKey)) {
            return 0;
        }
        
        $postValue = $_POST[$aPostKey];
        if (is_array($postValue)) {
            return count($postValue);
        }
        
        return 1;
    }

    /**
     * Checks if there are multiple values for the same key
     *
     * @param $aPostKey string            
     * @return boolean
     */
    public function hasMultiplePostForKey($aPostKey)
    {
        if (! $this->hasPost($aPostKey)) {
            return false;
        }
        
        $postValue = $_POST[$aPostKey];
        if (is_array($postValue)) {
            return true;
        }
        
        return false;
    }

    /**
     * Use all registered filters to filter the string
     *
     * @param $aString string|array            
     *
     * @return string the filtered string
     */
    private function filter($aParam)
    {
        $result = null;
        
        /* @var $filter ITextFilter */
        foreach ($this->filters->getIterator() as $filter) {
            if (is_array($aParam)) {
                $result = array();
                foreach ($aParam as $key => $str) {
                    $result[$key] = $filter->filter($str);
                }
            } else {
                $result = $filter->filter($aParam);
            }
        }
        
        // when no filters registered, then return the original param
        return ($result) ? $result : $aParam;
    }

    /**
     *
     * @return array user browser capabilities
     *        
     * @see get_browser
     */
    public function getBrowser()
    {
        return get_browser();
    }

    /**
     * Returns the Content type
     *
     * @return string
     */
    public function getContentType()
    {
        $result = $this->server("CONTENT_TYPE");
        if (!$result){
        	$result = $this->server("HTTP_CONTENT_TYPE");
        }
        if (stristr($result, ";")) {
            $parts = explode(";", $result);
            return $parts[0];
        }
        return $result;
    }

    /**
     * Returns the data from the request.
     * List post data from the POST request
     */
    public function getData($aKey = null)
    {
        if ($aKey === null) {
            return $this->data;
        }
        return (is_array($this->data) && isset($this->data[$aKey])) ? $this->filter($this->dataT[$aKey]) : null;
    }

    /**
     * Returns the request header by name
     *
     * @param $aName string            
     *
     * @return string the value of the request header
     */
    public function getHeader($aName)
    {
        if ($this->requestHeaders === null) {
            $this->requestHeaders = new \ArrayObject($this->parseRequestHeaders());
        }
        
        if (isset($this->requestHeaders[$aName])) {
            return $this->requestHeaders[$aName];
        }
        return null;
    }

    /**
     * Parse request headers
     */
    private function parseRequestHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) != 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    /**
     * Returns a HTTP GET variable or optionally get all get requests
     *
     * @param $aGetVar string
     *            The HTTP GET variable to return: $_GET['getKey']
     *            
     * @return string The HTTP GET variable or null when it does not exist
     */
    public function getGet($aGetKey = null)
    {
        if (!$aGetKey){
            return $this->filter($_GET);
        }
        return isset($_GET[$aGetKey]) ? $this->filter($_GET[$aGetKey]) : null;
    }

    /**
     * Returns the Http host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->server("HTTP_HOST");
    }

    /**
     * Returns a HTTP POST variable.
     *
     * @param $aPostKey string
     *            The HTTP POST variable to return: $_POST['postKey']
     * @param $aIndex int
     *            used when posting multiple values (array[])
     *            
     * @return string The HTTP POST variable or null when it does not exist
     */
    public function getPost($aPostKey, $aIndex = null)
    {
        if (! isset($_POST[$aPostKey])) {
            return null;
        }
        if ($aIndex === null) {
            return $this->filter($_POST[$aPostKey]);
        } else {
            return is_array($_POST[$aPostKey]) && isset($_POST[$aPostKey][$aIndex]) ? $_POST[$aPostKey][$aIndex] : null;
        }
    }

    /**
     * Returns an array of files
     *
     * @return ArrayIterator
     */
    public function getUploadedFiles()
    {
        return new \ArrayIterator($_FILES);
    }

    /**
     * Returns the path info
     *
     * @return string
     */
    public function getPathInfo()
    {
        $script = $this->server("SCRIPT_NAME"); // /path/to/index.php
        $uri = $this->server("REQUEST_URI"); // /path/to
        
        if (preg_match("/(.*)\?/", $uri, $matches)) {
            $uri = $matches[1];
        }
        
        $match = preg_match('/.*\/(.*\.php)$/i', $script, $matches);
        if (! $match) {
            throw new \Exception('Could not determine script filename');
        }
        $scriptFile = $matches[1];
        
        $path = str_replace($scriptFile, "", $script);
        
        if ($path && $path != "/")
            $url = "/" . str_replace($path, "", $uri);
        else
            $url = $uri;
            
            // strip off the last /
        if (preg_match("/(.*)\/$/", $url, $matches)) {
            $url = $matches[1];
        }
        
        $url = ($url) ? $url : '/'; // route to root /
        return urldecode(preg_replace("/\/\//", "/", $url));
    }
    
    /**
     * Returns the referrer. The address of the page (if any) which referred the user agent to the current page. This is set by the user agent. Not all user agents will set this, and some provide the ability to modify HTTP_REFERER as a feature. In short, it cannot really be trusted. 
     *
     * @return string the referrer
     */
    public function getReferrer()
    {
        return strtoupper($this->server("HTTP_REFERER"));
    }

    /**
     * Returns the http request method eg.
     * GET, PUT, POST, etc
     *
     * @return string the http request method (always upper case)
     */
    public function getRequestMethod()
    {
        return strtoupper($this->server("REQUEST_METHOD"));
    }

    /**
     * Returns the scheme based on the port number (http or https only)
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->server("SERVER_PORT") == 80 ? "http" : "https";
    }

    /**
     * Returns the user UP address
     *
     * @return string
     */
    public function getUserIP()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $client = $_SERVER['HTTP_CLIENT_IP'];
            if (filter_var($client, FILTER_VALIDATE_IP)) {
                return $client;
            }
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forward = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var($forward, FILTER_VALIDATE_IP)) {
                return $forward;
            }
        }
        
        $remote = $_SERVER['REMOTE_ADDR'];
        return $remote;
    }

    /**
     * Returns the user agent string
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * Check if GET variables are available
     *
     * @return boolean
     */
    public function hasGet($aGetField = null)
    {
        if ($aGetField === null) {
            return count($_GET) > 0;
        } else {
            return isset($_GET[$aGetField]);
        }
    }

    /**
     * Check if POST variables are available
     *
     * @param $aPostField [Optional]
     *            String The variable to check
     *            
     * @return boolean
     */
    public function hasPost($aPostField = null)
    {
        if ($aPostField === null) {
            return count($_POST) > 0;
        } else {
            return isset($_POST[$aPostField]);
        }
    }

    /**
     * Returns if the user uploaded any files
     *
     * @return boolean
     */
    public function hasUploadedFiles()
    {
        $result = false;
        
        if (count($_FILES) > 0) {
            foreach ($_FILES as $upload) {
                if ($upload['tmp_name'] !== null && $upload['tmp_name'] !== "") {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Checks if the current request is a ajax request
     */
    public function isAjaxRequest()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));
    }

    /**
     * Checks if this request is a DELETE request
     *
     * @return boolean
     */
    public function isDeleteRequest()
    {
        return self::METHOD_DELETE === $this->getRequestMethod();
    }

    /**
     * Checks if this request is a GET request
     *
     * @return boolean
     */
    public function isGetRequest()
    {
        return self::METHOD_GET === $this->getRequestMethod();
    }

    /**
     * Checks if this request is a HEAD request
     *
     * @return boolean
     */
    public function isHeadRequest()
    {
        return self::METHOD_HEAD === $this->getRequestMethod();
    }

    /**
     * Checks if this request is a POST request
     *
     * @return boolean
     */
    public function isPostRequest()
    {
        return self::METHOD_POST === $this->getRequestMethod();
    }

    /**
     * Checks if this request is a PUT request
     *
     * @return boolean
     */
    public function isPutRequest()
    {
        return self::METHOD_PUT === $this->getRequestMethod();
    }

    /**
     * Returns the $_SERVER variable
     *
     * @param $aIndices string
     *            The server variable
     *            
     * @return string the $_SERVER var or null when not set
     */
    public function server($aIndice)
    {
        return isset($_SERVER[$aIndice]) ? $_SERVER[$aIndice] : null;
    }
}