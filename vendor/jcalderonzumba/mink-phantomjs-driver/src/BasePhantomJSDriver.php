<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Zumba\GastonJS\Browser\Browser;

/**
 * Class BasePhantomJSDriver
 * @package Zumba\Mink\Driver
 */
class BasePhantomJSDriver extends CoreDriver {

  /** @var  Browser */
  protected $browser;
  /** @var  string */
  protected $phantomHost;
  /** @var  \Twig_Loader_Filesystem */
  protected $templateLoader;
  /** @var  \Twig_Environment */
  protected $templateEnv;

  /**
   * Instantiates the driver
   * @param string $phantomHost browser "api" oriented host
   * @param string $templateCache where we are going to store the templates cache
   */
  public function __construct($phantomHost, $templateCache = null) {
    $this->phantomHost = $phantomHost;
    $this->browser = new Browser($phantomHost);
    $this->templateLoader = new \Twig_Loader_Filesystem(realpath(__DIR__ . '/Resources/Script'));
    $this->templateEnv = new \Twig_Environment($this->templateLoader, array('cache' => $this->templateCacheSetup($templateCache), 'strict_variables' => true));
  }

  /**
   * Sets up the cache template location for the scripts we are going to create with the driver
   * @param $templateCache
   * @return string
   * @throws DriverException
   */
  protected function templateCacheSetup($templateCache) {
    $cacheDir = $templateCache;
    if ($templateCache === null) {
      $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jcalderonzumba" . DIRECTORY_SEPARATOR . "phantomjs";
      if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0777, true);
      }
    }

    if (!file_exists($cacheDir)) {
      throw new DriverException("Template cache $cacheDir directory does not exist");
    }
    return $cacheDir;
  }

  /**
   * Helper to find a node element given an xpath
   * @param string $xpath
   * @param int    $max
   * @returns int
   * @throws DriverException
   */
  protected function findElement($xpath, $max = 1) {
    $elements = $this->browser->find("xpath", $xpath);
    if (!isset($elements["page_id"]) || !isset($elements["ids"]) || count($elements["ids"]) !== $max) {
      throw new DriverException("Failed to get elements with given $xpath");
    }
    return $elements;
  }

  /**
   * @return Browser
   */
  public function getBrowser() {
    return $this->browser;
  }

  /**
   * @return \Twig_Environment
   */
  public function getTemplateEnv() {
    return $this->templateEnv;
  }

  /**
   * Returns a javascript script via twig template engine
   * @param $templateName
   * @param $viewData
   * @return string
   */
  public function javascriptTemplateRender($templateName, $viewData) {
    /** @var $templateEngine \Twig_Environment */
    $templateEngine = $this->getTemplateEnv();
    return $templateEngine->render($templateName, $viewData);
  }

}
