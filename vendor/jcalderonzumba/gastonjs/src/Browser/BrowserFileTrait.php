<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserFileTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserFileTrait {
  /**
   * Selects a file to send to the browser to a given page
   * @param $pageId
   * @param $elementId
   * @param $value
   * @return mixed
   */
  public function selectFile($pageId, $elementId, $value) {
    return $this->command('select_file', $pageId, $elementId, $value);
  }
}
