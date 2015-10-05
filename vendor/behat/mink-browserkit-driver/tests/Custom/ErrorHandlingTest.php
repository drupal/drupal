<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Response;

class ErrorHandlingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestClient
     */
    private $client;

    protected function setUp()
    {
        $this->client = new TestClient();
    }

    public function testGetClient()
    {
        $this->assertSame($this->client, $this->getDriver()->getClient());
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage Unable to access the response before visiting a page
     */
    public function testGetResponseHeaderWithoutVisit()
    {
        $this->getDriver()->getResponseHeaders();
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage Unable to access the response content before visiting a page
     */
    public function testFindWithoutVisit()
    {
        $this->getDriver()->find('//html');
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage Unable to access the request before visiting a page
     */
    public function testGetCurrentUrlWithoutVisit()
    {
        $this->getDriver()->getCurrentUrl();
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage The selected node has an invalid form attribute (foo)
     */
    public function testNotMatchingHtml5FormId()
    {
        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input name="test" value="foo" form="foo">
        <input type="submit">
    </form>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage The selected node has an invalid form attribute (foo)
     */
    public function testInvalidHtml5FormId()
    {
        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input name="test" value="foo" form="foo">
        <input type="submit">
    </form>
    <div id="foo"></div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage The selected node does not have a form ancestor.
     */
    public function testManipulateInputWithoutForm()
    {
        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input type="submit">
    </form>
    <div id="foo">
        <input name="test" value="foo">
    </div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage Behat\Mink\Driver\BrowserKitDriver supports clicking on links and submit or reset buttons only. But "div" provided
     */
    public function testClickOnUnsupportedElement()
    {
        $html = <<<'HTML'
<html>
<body>
    <div></div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->click('//div');
    }

    private function getDriver()
    {
        return new BrowserKitDriver($this->client);
    }
}

class TestClient extends Client
{
    protected $nextResponse = null;
    protected $nextScript = null;

    public function setNextResponse(Response $response)
    {
        $this->nextResponse = $response;
    }

    public function setNextScript($script)
    {
        $this->nextScript = $script;
    }

    protected function doRequest($request)
    {
        if (null === $this->nextResponse) {
            return new Response();
        }

        $response = $this->nextResponse;
        $this->nextResponse = null;

        return $response;
    }
}
