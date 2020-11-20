<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserScriptTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserScriptTrait {
  /**
   * Evaluates a script on the browser
   * @param $script
   * @return mixed
   */
  public function evaluate($script) {
    return $this->command('evaluate', $script);
  }

  /**
   * Executes a script on the browser
   * @param $script
   * @return mixed
   */
  public function execute($script) {
    return $this->command('execute', $script);
  }

  /**
   * Add desired extensions to phantomjs
   * @param $extensions
   * @return bool
   */
  public function extensions($extensions) {
    //TODO: add error control for when extensions do not exist physically
    foreach ($extensions as $extensionName) {
      $this->command('add_extension', $extensionName);
    }
    return true;
  }

}
