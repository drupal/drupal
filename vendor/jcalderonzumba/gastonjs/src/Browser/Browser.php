<?php

namespace Zumba\GastonJS\Browser;

/**
 * Class Browser
 * @package Zumba\GastonJS
 */
class Browser extends BrowserBase {

  use BrowserAuthenticationTrait;
  use BrowserConfigurationTrait;
  use BrowserCookieTrait;
  use BrowserFileTrait;
  use BrowserFrameTrait;
  use BrowserHeadersTrait;
  use BrowserMouseEventTrait;
  use BrowserNavigateTrait;
  use BrowserNetworkTrait;
  use BrowserPageElementTrait;
  use BrowserPageTrait;
  use BrowserRenderTrait;
  use BrowserScriptTrait;
  use BrowserWindowTrait;

  /**
   * @param string $phantomJSHost
   * @param mixed  $logger
   */
  public function __construct($phantomJSHost, $logger = null) {
    $this->phantomJSHost = $phantomJSHost;
    $this->logger = $logger;
    $this->debug = false;
    $this->createApiClient();
  }

  /**
   * Returns the value of a given element in a page
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function value($pageId, $elementId) {
    return $this->command('value', $pageId, $elementId);
  }

  /**
   * Sets a value to a given element in a given page
   * @param $pageId
   * @param $elementId
   * @param $value
   * @return mixed
   */
  public function set($pageId, $elementId, $value) {
    return $this->command('set', $pageId, $elementId, $value);
  }

  /**
   * Tells whether an element on a page is visible or not
   * @param $pageId
   * @param $elementId
   * @return bool
   */
  public function isVisible($pageId, $elementId) {
    return $this->command('visible', $pageId, $elementId);
  }

  /**
   * @param $pageId
   * @param $elementId
   * @return bool
   */
  public function isDisabled($pageId, $elementId) {
    return $this->command('disabled', $pageId, $elementId);
  }

  /**
   * Drag an element to a another in a given page
   * @param $pageId
   * @param $fromId
   * @param $toId
   * @return mixed
   */
  public function drag($pageId, $fromId, $toId) {
    return $this->command('drag', $pageId, $fromId, $toId);
  }

  /**
   * Selects a value in the given element and page
   * @param $pageId
   * @param $elementId
   * @param $value
   * @return mixed
   */
  public function select($pageId, $elementId, $value) {
    return $this->command('select', $pageId, $elementId, $value);
  }

  /**
   * Triggers an event to a given element on the given page
   * @param $pageId
   * @param $elementId
   * @param $event
   * @return mixed
   */
  public function trigger($pageId, $elementId, $event) {
    return $this->command('trigger', $pageId, $elementId, $event);
  }

  /**
   * TODO: not sure what this does, needs to do normalizeKeys
   * @param int   $pageId
   * @param int   $elementId
   * @param array $keys
   * @return mixed
   */
  public function sendKeys($pageId, $elementId, $keys) {
    return $this->command('send_keys', $pageId, $elementId, $this->normalizeKeys($keys));
  }
}
