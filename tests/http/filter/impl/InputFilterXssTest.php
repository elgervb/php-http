<?php
namespace http\filter\impl;

/**
 * Test class for InputFilterXss.
 * Generated by PHPUnit on 2014-11-19 at 14:34:58.
 */
class InputFilterXssTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var InputFilterXss
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new InputFilterXss();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {}

    /**
     * @covers compact\http\filter\impl\InputFilterXss::setRemoveReplacementString
     *
     * @todo Implement testSetRemoveReplacementString().
     */
    public function testSetRemoveReplacementString()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::compactExplodedWordsCallback
     *
     * @todo Implement testCompactExplodedWordsCallback().
     */
    public function testCompactExplodedWordsCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::convertAttributeCallback
     *
     * @todo Implement testConvertAttributeCallback().
     */
    public function testConvertAttributeCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::entityDecode
     *
     * @todo Implement testEntityDecode().
     */
    public function testEntityDecode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::filter
     *
     * @todo Implement testFilter().
     */
    public function testFilterString()
    {
        $str = "'';!--\"<XSS>=&{()}";
        
        $result = $this->object->filter($str);
        $expected = "'';!--\"[removed]=&{()}";
        
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::filter
     *
     * @todo Implement testFilter().
     */
    public function testFilterWithReplacement()
    {
        $str = "'';!--\"<XSS>=&{()}";
        $this->object->setRemoveReplacementString("[[rem]]");
        
        $result = $this->object->filter($str);
        $expected = "'';!--\"[[rem]]=&{()}";
        
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::jsImgRemovalCallback
     *
     * @todo Implement testJsImgRemovalCallback().
     */
    public function testJsImgRemovalCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::jsLinkRemovalCallback
     *
     * @todo Implement testJsLinkRemovalCallback().
     */
    public function testJsLinkRemovalCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::xssClean
     *
     * @todo Implement testXssClean().
     */
    public function testXssClean()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::sanitizeFilename
     *
     * @todo Implement testSanitizeFilename().
     */
    public function testSanitizeFilename()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers compact\http\filter\impl\InputFilterXss::sanitizeNaughtyHtmlCallback
     *
     * @todo Implement testSanitizeNaughtyHtmlCallback().
     */
    public function testSanitizeNaughtyHtmlCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
?>
