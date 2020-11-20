<?php
/**
 * Copyright 2004-2017 Facebook. All Rights Reserved.
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
 * @author Justin Bishop <jubishop@gmail.com>
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 * @author Fabrizio Branca <mail@fabrizio-branca.de>
 * @author Tsz Ming Wong <tszming@gmail.com>
 */

namespace WebDriver;

use WebDriver\Exception as WebDriverException;

/**
 * Abstract WebDriver\AbstractWebDriver class
 *
 * @package WebDriver
 */
abstract class AbstractWebDriver
{
    /**
     * URL
     *
     * @var string
     */
    protected $url;

    /**
     * Return array of supported method names and corresponding HTTP request methods
     *
     * @return array
     */
    abstract protected function methods();

    /**
     * Return array of obsolete method names and corresponding HTTP request methods
     *
     * @return array
     */
    protected function obsoleteMethods()
    {
        return array();
    }

    /**
     * Constructor
     *
     * @param string $url URL to Selenium server
     */
    public function __construct($url = 'http://localhost:4444/wd/hub')
    {
        $this->url = $url;
    }

    /**
     * Magic method which returns URL to Selenium server
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Returns URL to Selenium server
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Curl request to webdriver server.
     *
     * @param string $requestMethod HTTP request method, e.g., 'GET', 'POST', or 'DELETE'
     * @param string $command       If not defined in methods() this function will throw.
     * @param array  $parameters    If an array(), they will be posted as JSON parameters
     *                              If a number or string, "/$params" is appended to url
     * @param array  $extraOptions  key=>value pairs of curl options to pass to curl_setopt()
     *
     * @return array array('value' => ..., 'info' => ...)
     *
     * @throws \WebDriver\Exception if error
     */
    protected function curl($requestMethod, $command, $parameters = null, $extraOptions = array())
    {
        if ($parameters && is_array($parameters) && $requestMethod !== 'POST') {
            throw WebDriverException::factory(
                WebDriverException::NO_PARAMETERS_EXPECTED,
                sprintf(
                    'The http request method called for %s is %s but it has to be POST if you want to pass the JSON parameters %s',
                    $command,
                    $requestMethod,
                    json_encode($parameters)
                )
            );
        }

        $url = sprintf('%s%s', $this->url, $command);

        if ($parameters && (is_int($parameters) || is_string($parameters))) {
            $url .= '/' . $parameters;
        }

        list($rawResult, $info) = ServiceFactory::getInstance()->getService('service.curl')->execute($requestMethod, $url, $parameters, $extraOptions);

        $httpCode = $info['http_code'];

        // According to https://w3c.github.io/webdriver/webdriver-spec.html all 4xx responses are to be considered
        // an error and return plaintext, while 5xx responses are json encoded
        if ($httpCode >= 400 && $httpCode <= 499) {
            throw WebDriverException::factory(
                WebDriverException::CURL_EXEC,
                'Webdriver http error: ' . $httpCode . ', payload :' . substr($rawResult, 0, 1000)
            );
        }

        $result = json_decode($rawResult, true);

        if (!empty($rawResult) && $result === null && json_last_error() != JSON_ERROR_NONE) {
            throw WebDriverException::factory(
                WebDriverException::CURL_EXEC,
                'Payload received from webdriver is not valid json: ' . substr($rawResult, 0, 1000)
            );
        }

        if (is_array($result) && !array_key_exists('status', $result)) {
            throw WebDriverException::factory(
                WebDriverException::CURL_EXEC,
                'Payload received from webdriver is valid but unexpected json: ' . substr($rawResult, 0, 1000)
            );
        }

        $value   = (is_array($result) && array_key_exists('value', $result)) ? $result['value'] : null;
        $message = (is_array($value) && array_key_exists('message', $value)) ? $value['message'] : null;

        // if not success, throw exception
        if ((int) $result['status'] !== 0) {
            throw WebDriverException::factory($result['status'], $message);
        }

        $sessionId = isset($result['sessionId'])
           ? $result['sessionId']
           : (isset($value['webdriver.remote.sessionid'])
               ? $value['webdriver.remote.sessionid']
               : null
           );

        return array(
            'value'      => $value,
            'info'       => $info,
            'sessionId'  => $sessionId,
            'sessionUrl' => $sessionId ? $this->url . '/session/' . $sessionId : $info['url'],
        );
    }

    /**
     * Magic method that maps calls to class methods to execute WebDriver commands
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     *
     * @return mixed
     *
     * @throws \WebDriver\Exception if invalid WebDriver command
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) > 1) {
            throw WebDriverException::factory(
                WebDriverException::JSON_PARAMETERS_EXPECTED,
                'Commands should have at most only one parameter, which should be the JSON Parameter object'
            );
        }

        if (preg_match('/^(get|post|delete)/', $name, $matches)) {
            $requestMethod = strtoupper($matches[0]);
            $webdriverCommand = strtolower(substr($name, strlen($requestMethod)));
        } else {
            $webdriverCommand = $name;
            $requestMethod = $this->getRequestMethod($webdriverCommand);
        }

        $methods = $this->methods();

        if (!in_array($requestMethod, (array) $methods[$webdriverCommand])) {
            throw WebDriverException::factory(
                WebDriverException::INVALID_REQUEST,
                sprintf(
                    '%s is not an available http request method for the command %s.',
                    $requestMethod,
                    $webdriverCommand
                )
            );
        }

        $result = $this->curl(
            $requestMethod,
            '/' . $webdriverCommand,
            array_shift($arguments)
        );

        return $result['value'];
    }

    /**
     * Get default HTTP request method for a given WebDriver command
     *
     * @param string $webdriverCommand
     *
     * @return string
     *
     * @throws \WebDriver\Exception if invalid WebDriver command
     */
    private function getRequestMethod($webdriverCommand)
    {
        if (!array_key_exists($webdriverCommand, $this->methods())) {
            throw WebDriverException::factory(
                array_key_exists($webdriverCommand, $this->obsoleteMethods())
                ? WebDriverException::OBSOLETE_COMMAND : WebDriverException::UNKNOWN_COMMAND,
                sprintf('%s is not a valid WebDriver command.', $webdriverCommand)
            );
        }

        $methods = $this->methods();
        $requestMethods = (array) $methods[$webdriverCommand];

        return array_shift($requestMethods);
    }
}
