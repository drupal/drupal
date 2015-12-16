<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserRenderTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserRenderTrait {
  /**
   * Check and fix render options
   * @param $options
   * @return mixed
   */
  protected function checkRenderOptions($options) {
    //Default is full and no selection
    if (count($options) === 0) {
      $options["full"] = true;
      $options["selector"] = null;
    }

    if (isset($options["full"]) && isset($options["selector"])) {
      if ($options["full"]) {
        //Whatever it is, full is more powerful than selection
        $options["selector"] = null;
      }
    } else {
      if (!isset($options["full"]) && isset($options["selector"])) {
        $options["full"] = false;
      }
    }
    return $options;
  }

  /**
   * Renders a page or selection to a file given by path
   * @param string $path
   * @param array  $options
   * @return mixed
   */
  public function render($path, $options = array()) {
    $fixedOptions = $this->checkRenderOptions($options);
    return $this->command('render', $path, $fixedOptions["full"], $fixedOptions["selector"]);
  }

  /**
   * Renders base64 a page or selection to a file given by path
   * @param string $imageFormat (PNG, GIF, JPEG)
   * @param array  $options
   * @return mixed
   */
  public function renderBase64($imageFormat, $options = array()) {
    $fixedOptions = $this->checkRenderOptions($options);
    return $this->command('render_base64', $imageFormat, $fixedOptions["full"], $fixedOptions["selector"]);
  }

  /**
   * Sets the paper size, useful when saving to PDF
   * @param $paperSize
   * @return mixed
   */
  public function setPaperSize($paperSize) {
    return $this->command('set_paper_size', $paperSize);
  }
}
