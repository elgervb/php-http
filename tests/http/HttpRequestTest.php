<?php
namespace http;

/**
 * Test class for HttpRequest.
 * Generated by PHPUnit on 2014-11-19 at 14:41:13.
 */
class HttpRequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var HttpRequest
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new HttpRequest();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $_POST = array();
    }

    private function mockPost($key, $value)
    {
        $_POST[$key] = $value;
    }
    
    private function mockServer($key, $value)
    {
        $_SERVER[$key] = $value;
    }
    
    public function testgetPost()
    {
        // Mock
        $this->mockPost('test', "testValue");
        
        // Test
        $this->assertEquals("testValue", $this->object->getPost('test'));
    }
    
    public function testGetPostArray(){
        // Mock
        $this->mockPost('test', array("testValue", "testValue2"));
    
        $this->assertEquals('testValue', $this->object->getPost('test', 0));
        $this->assertEquals('testValue2', $this->object->getPost('test', 1));
    }
    
    public function testGetPostNamedArray(){
        // Mock
        $this->mockPost('test', array("one" => "testValue", "two" => "testValue2"));
    
        $this->assertEquals('testValue', $this->object->getPost('test', "one"));
        $this->assertEquals('testValue2', $this->object->getPost('test', "two"));
    }
    
    
    public function testGetSchemeHttp() {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        
        $result = $this->object->getScheme();
        $this->assertEquals('http', $result);
    }
    
    public function testGetSchemeHttp10() {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
    
        $result = $this->object->getScheme();
        $this->assertEquals('http', $result);
    }
    
    public function testGetSchemeHttps() {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTPS/1.1';
    
        $result = $this->object->getScheme();
        $this->assertEquals('https', $result);
    }
    
    public function testGetSchemeHttps10() {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTPS/1.0';
    
        $result = $this->object->getScheme();
        $this->assertEquals('https', $result);
    }
    

    public function testHasPost()
    {
        // Mock
        $this->mockPost('test', "testValue");
    
        // Test
        $this->assertTrue($this->object->hasPost());
    }
    
    public function testHasPostWithVar()
    {
        // Mock
        $this->mockPost('test', "testValue");
        
        // Test
        $this->assertTrue($this->object->hasPost('test'));
    }
    
    public function testHasPostFail()
    {
        // Test
        $this->assertFalse($this->object->hasPost('test'));
    }

    public function testHasPostFailWithVar()
    {
        // Mock
        $this->mockPost('test2', "testValue");
        
        // Test
        $this->assertFalse($this->object->hasPost('test'));
    }
    
    public function testIsType() {
    	
    	$this->mockServer("REQUEST_METHOD", "POST");
    	
    	$this->assertTrue($this->object->isType(HttpMethod::METHOD_POST), "Looks like this isn't a POST request");
    	$this->assertFalse($this->object->isType(HttpMethod::METHOD_GET));
    }
}
