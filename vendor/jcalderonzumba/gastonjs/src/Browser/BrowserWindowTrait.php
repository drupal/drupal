<?php

namespace Zumba\GastonJS\Browser;

/**
 * Class BrowserWindowTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserWindowTrait {
  /**
   * Returns the current window handle name in the browser
   * @param string $name
   * @return mixed
   */
  public function windowHandle($name = null) {
    return $this->command('window_handle', $name);
  }

  /**
   * Returns all the window handles present in the browser
   * @return array
   */
  public function windowHandles() {
    return $this->command('window_handles');
  }

  /**
   * Change the browser focus to another window
   * @param $windowHandleName
   * @return mixed
   */
  public function switchToWindow($windowHandleName) {
    return $this->command('switch_to_window', $windowHandleName);
  }

  /**
   * Opens a new window on the browser
   * @return mixed
   */
  public function openNewWindow() {
    return $this->command('open_new_window');
  }

  /**
   * Closes a window on the browser by a given handler name
   * @param $windowHandleName
   * @return mixed
   */
  public function closeWindow($windowHandleName) {
    return $this->command('close_window', $windowHandleName);
  }

  /**
   * Gets the current request window name
   * @return string
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function windowName() {
    return $this->command('window_name');
  }

  /**
   * Zoom factor for a web page
   * @param $zoomFactor
   * @return mixed
   */
  public function setZoomFactor($zoomFactor) {
    return $this->command('set_zoom_factor', $zoomFactor);
  }

  /**
   * Gets the window size
   * @param $windowHandleName
   * @return mixed
   */
  public function windowSize($windowHandleName) {
    return $this->command('window_size', $windowHandleName);
  }

}
