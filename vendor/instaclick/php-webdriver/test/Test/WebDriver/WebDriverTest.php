<?php
/**
 * Copyright 2014-2017 Anthon Pang. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package WebDriver
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 * @author Damian Mooyman <damian@silverstripe.com>
 */

namespace Test\WebDriver;

use WebDriver\ServiceFactory;
use WebDriver\WebDriver;

/**
 * Test WebDriver\WebDriver class
 *
 * @package WebDriver
 *
 * @group Functional
 */
class WebDriverTest extends \PHPUnit_Framework_TestCase
{
    private $driver;
    private $session;
    private $testDocumentRootUrl = 'http://localhost';
    private $testSeleniumRootUrl = 'http://localhost:4444/wd/hub';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        ServiceFactory::getInstance()->setServiceClass('service.curl', '\\WebDriver\\Service\\CurlService');

        if ($url = getenv('ROOT_URL')) {
            $this->testDocumentRootUrl = $url;
        }

        if ($url = getenv('SELENIUM_URL')) {
            $this->testSeleniumRootUrl = $url;
        }

        $this->driver  = new WebDriver($this->getTestSeleniumRootUrl());
        $this->session = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if ($this->session) {
            $this->session->close();
        }
    }

    /**
     * Returns the full url to the test site (corresponding to the root dir of the library).
     * You can set this via env var ROOT_URL
     *
     * @return string
     */
    protected function getTestDocumentRootUrl()
    {
        return $this->testDocumentRootUrl;
    }

    /**
     * Returns the full url to the Selenium server used for functional tests
     *
     * @return string
     *
     * @todo make this configurable via env var
     */
    protected function getTestSeleniumRootUrl()
    {
        return $this->testSeleniumRootUrl;
    }

    /**
     * Is Selenium down?
     *
     * @param \Exception $exception
     *
     * @return boolean
     */
    protected function isSeleniumDown($exception)
    {
        return preg_match('/Failed to connect to .* Connection refused/', $exception->getMessage()) != false
            || strpos($exception->getMessage(), 'couldn\'t connect to host') !== false
            || strpos($exception->getMessage(), 'Unable to connect to host') !== false;
    }

    /**
     * Test driver sessions
     */
    public function testSessions()
    {
        try {
            $this->assertCount(0, $this->driver->sessions());

            $this->session = $this->driver->session();
        } catch (\Exception $e) {
            if ($this->isSeleniumDown($e)) {
                $this->markTestSkipped('selenium server not running');

                return;
            }

            throw $e;
        }

        $this->assertCount(1, $this->driver->sessions());
        $this->assertEquals($this->getTestSeleniumRootUrl(), $this->driver->getUrl());
    }

    /**
     * Test driver status
     */
    public function testStatus()
    {
        try {
            $status = $this->driver->status();
        } catch (\Exception $e) {
            if ($this->isSeleniumDown($e)) {
                $this->markTestSkipped('selenium server not running');

                return;
            }

            throw $e;
        }

        $this->assertCount(3, $status);
        $this->assertTrue(isset($status['java']));
        $this->assertTrue(isset($status['os']));
        $this->assertTrue(isset($status['build']));
    }

    /**
     * Checks that an error connecting to Selenium gives back the expected exception
     */
    public function testSeleniumError()
    {
        try {
            $this->driver = new WebDriver($this->getTestSeleniumRootUrl() . '/../invalidurl');

            $status = $this->driver->status();

            $this->fail('Exception not thrown while connecting to invalid Selenium url');
        } catch (\Exception $e) {
            if ($this->isSeleniumDown($e)) {
                $this->markTestSkipped('selenium server not running');

                return;
            }

            $this->assertEquals('WebDriver\Exception\CurlExec', get_class($e));
        }
    }

    /**
     * Checks that a successful command to Selenium which returns an http error response gives back the expected exception
     */
    public function testSeleniumErrorResponse()
    {
        try {
            $status = $this->driver->status();
        } catch (\Exception $e) {
            if ($this->isSeleniumDown($e)) {
                $this->markTestSkipped('selenium server not running');

                return;
            }

            throw $e;
        }

        try {
            $this->session = $this->driver->session();
            $this->session->open($this->getTestDocumentRootUrl().'/test/Assets/index.html');

            $element = $this->session->element('id', 'a-quite-unlikely-html-element-id');

            $this->fail('Exception not thrown while looking for missing element in page');
        } catch (\Exception $e) {
            $this->assertEquals('WebDriver\Exception\NoSuchElement', get_class($e));
        }
    }

    /**
     * Checks that a successful command to Selenium which returns 'nothing' according to spec does not raise an error
     */
    public function testSeleniumNoResponse()
    {
        try {
            $status = $this->driver->status();
        } catch (\Exception $e) {
            if ($this->isSeleniumDown($e)) {
                $this->markTestSkipped('selenium server not running');

                return;
            }

            throw $e;
        }

        $this->session = $this->driver->session();
        $timeouts = $this->session->timeouts();
        $out = $timeouts->async_script(array('type' => 'implicit', 'ms' => 1000));

        $this->assertEquals(null, $out);
    }

    /**
     * Assert that empty response does not trigger exception, but invalid JSON does
     */
    public function testNonJsonResponse()
    {
        $mockCurlService = $this->createMock('WebDriver\Service\CurlService');
        $mockCurlService->expects($this->once())
            ->method('execute')
            ->will($this->returnCallback(function ($requestMethod, $url) {
                $info = array(
                    'url' => $url,
                    'request_method' => $requestMethod,
                    'http_code' => 200,
                );

                $result = preg_match('#.*session$#', $url)
                    ? $result = 'some invalid json'
                    : $result = '';

                return array($result, $info);
            }));

        ServiceFactory::getInstance()->setService('service.curl', $mockCurlService);

        $result = $this->driver->status();

        $this->assertNull($result);

        // Test /session should error
        $this->setExpectedException(
            'WebDriver\Exception\CurlExec',
            'Payload received from webdriver is not valid json: some invalid json'
        );

        $result = $this->driver->session();

        $this->assertNull($result);
    }
}
