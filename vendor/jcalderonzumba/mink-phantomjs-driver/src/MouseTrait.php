<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Class MouseTrait
 * @package Zumba\Mink\Driver
 */
trait MouseTrait {

  /**
   * Generates a mouseover event on the given element by xpath
   * @param string $xpath
   * @throws DriverException
   */
  public function mouseOver($xpath) {
    $element = $this->findElement($xpath, 1);
    $this->browser->hover($element["page_id"], $element["ids"][0]);
  }

  /**
   * Clicks if possible on an element given by xpath
   * @param string $xpath
   * @return mixed
   * @throws DriverException
   */
  public function click($xpath) {
    $elements = $this->findElement($xpath, 1);
    $this->browser->click($elements["page_id"], $elements["ids"][0]);
  }

  /**
   * {@inheritdoc}
   */
  /**
   * Double click on element found via xpath
   * @param string $xpath
   * @throws DriverException
   */
  public function doubleClick($xpath) {
    $elements = $this->findElement($xpath, 1);
    $this->browser->doubleClick($elements["page_id"], $elements["ids"][0]);
  }

  /**
   * Right click on element found via xpath
   * @param string $xpath
   * @throws DriverException
   */
  public function rightClick($xpath) {
    $elements = $this->findElement($xpath, 1);
    $this->browser->rightClick($elements["page_id"], $elements["ids"][0]);
  }

}
