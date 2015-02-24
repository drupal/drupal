<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\DriverInterface;

abstract class AbstractConfig
{
    /**
     * Creates driver instance.
     *
     * @return DriverInterface
     */
    abstract public function createDriver();

    /**
     * Map remote file path.
     *
     * @param string $file File path.
     *
     * @return string
     */
    public function mapRemoteFilePath($file)
    {
        if (!isset($_SERVER['TEST_MACHINE_BASE_PATH']) || !isset($_SERVER['DRIVER_MACHINE_BASE_PATH'])) {
            return $file;
        }

        $pattern = '/^'.preg_quote($_SERVER['TEST_MACHINE_BASE_PATH'], '/').'/';
        $basePath = $_SERVER['DRIVER_MACHINE_BASE_PATH'];

        return preg_replace($pattern, $basePath, $file, 1);
    }

    /**
     * Gets the base url to the fixture folder
     *
     * @return string
     */
    public function getWebFixturesUrl()
    {
        return $_SERVER['WEB_FIXTURES_HOST'];
    }

    /**
     * @param string $testCase The name of the TestCase class
     * @param string $test     The name of the test method
     *
     * @return string|null A message explaining why the test should be skipped, or null to run the test.
     */
    public function skipMessage($testCase, $test)
    {
        if (!$this->supportsCss() && 0 === strpos($testCase, 'Behat\Mink\Tests\Driver\Css\\')) {
            return 'This driver does not support CSS.';
        }

        if (!$this->supportsJs() && 0 === strpos($testCase, 'Behat\Mink\Tests\Driver\Js\\')) {
            return 'This driver does not support JavaScript.';
        }

        return null;
    }

    /**
     * Whether the JS tests should run or no.
     *
     * @return bool
     */
    protected function supportsJs()
    {
        return true;
    }

    /**
     * Whether the CSS tests should run or no.
     *
     * @return bool
     */
    protected function supportsCss()
    {
        return false;
    }
}
