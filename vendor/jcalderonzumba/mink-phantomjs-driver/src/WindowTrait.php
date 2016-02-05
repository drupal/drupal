<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Class WindowTrait
 * @package Zumba\Mink\Driver
 */
trait WindowTrait {
  /**
   * Returns the current page window name
   * @return string
   */
  public function getWindowName() {
    return $this->browser->windowName();
  }

  /**
   * Return all the window handles currently present in phantomjs
   * @return array
   */
  public function getWindowNames() {
    return $this->browser->windowHandles();
  }

  /**
   * Switches to window by name if possible
   * @param $name
   * @throws DriverException
   */
  public function switchToWindow($name = null) {
    $handles = $this->browser->windowHandles();
    if ($name === null) {
      //null means back to the main window
      return $this->browser->switchToWindow($handles[0]);
    }

    $windowHandle = $this->browser->windowHandle($name);
    if (!empty($windowHandle)) {
      $this->browser->switchToWindow($windowHandle);
    } else {
      throw new DriverException("Could not find window handle by a given window name: $name");
    }

  }

  /**
   * Resizing a window with specified size
   * @param int    $width
   * @param int    $height
   * @param string $name
   * @throws DriverException
   */
  public function resizeWindow($width, $height, $name = null) {
    if ($name !== null) {
      //TODO: add this on the phantomjs stuff
      throw new DriverException("Resizing other window than the main one is not supported yet");
    }
    $this->browser->resize($width, $height);
  }

}
