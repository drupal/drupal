<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Class KeyboardTrait
 * @package Zumba\Mink\Driver
 */
trait KeyboardTrait {

  /**
   * Does some normalization for the char we want to do keyboard events with.
   * @param $char
   * @throws DriverException
   * @return string
   */
  protected function normalizeCharForKeyEvent($char) {
    if (!is_int($char) && !is_string($char)) {
      throw new DriverException("Unsupported key type, can only be integer or string");
    }

    if (is_string($char) && strlen($char) !== 1) {
      throw new DriverException("Key can only have ONE character");
    }

    $key = $char;
    if (is_int($char)) {
      $key = chr($char);
    }
    return $key;
  }

  /**
   * Does some control and normalization for the key event modifier
   * @param $modifier
   * @return string
   * @throws DriverException
   */
  protected function keyEventModifierControl($modifier) {
    if ($modifier === null) {
      $modifier = "none";
    }

    if (!in_array($modifier, array("none", "alt", "ctrl", "shift", "meta"))) {
      throw new DriverException("Unsupported key modifier $modifier");
    }
    return $modifier;
  }

  /**
   * Send a key-down event to the browser element
   * @param        $xpath
   * @param        $char
   * @param string $modifier
   * @throws DriverException
   */
  public function keyDown($xpath, $char, $modifier = null) {
    $element = $this->findElement($xpath, 1);
    $key = $this->normalizeCharForKeyEvent($char);
    $modifier = $this->keyEventModifierControl($modifier);
    return $this->browser->keyEvent($element["page_id"], $element["ids"][0], "keydown", $key, $modifier);
  }

  /**
   * @param string $xpath
   * @param string $char
   * @param string $modifier
   * @throws DriverException
   */
  public function keyPress($xpath, $char, $modifier = null) {
    $element = $this->findElement($xpath, 1);
    $key = $this->normalizeCharForKeyEvent($char);
    $modifier = $this->keyEventModifierControl($modifier);
    return $this->browser->keyEvent($element["page_id"], $element["ids"][0], "keypress", $key, $modifier);
  }

  /**
   * Pressed up specific keyboard key.
   *
   * @param string         $xpath
   * @param string|integer $char could be either char ('b') or char-code (98)
   * @param string         $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
   *
   * @throws DriverException                  When the operation cannot be done
   */
  public function keyUp($xpath, $char, $modifier = null) {
    $this->findElement($xpath, 1);
    $element = $this->findElement($xpath, 1);
    $key = $this->normalizeCharForKeyEvent($char);
    $modifier = $this->keyEventModifierControl($modifier);
    return $this->browser->keyEvent($element["page_id"], $element["ids"][0], "keyup", $key, $modifier);
  }
}
