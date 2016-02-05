<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserMouseEventTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserMouseEventTrait {
  /**
   * Click on a given page and element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function click($pageId, $elementId) {
    return $this->command('click', $pageId, $elementId);
  }

  /**
   * Triggers a right click on a page an element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function rightClick($pageId, $elementId) {
    return $this->command('right_click', $pageId, $elementId);
  }

  /**
   * Triggers a double click in a given page and element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function doubleClick($pageId, $elementId) {
    return $this->command('double_click', $pageId, $elementId);
  }

  /**
   * Hovers over an element in a given page
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function hover($pageId, $elementId) {
    return $this->command('hover', $pageId, $elementId);
  }

  /**
   * Click on given coordinates, THIS DOES NOT depend on the page, it just clicks on where we are right now
   * @param $coordX
   * @param $coordY
   * @return mixed
   */
  public function clickCoordinates($coordX, $coordY) {
    return $this->command('click_coordinates', $coordX, $coordY);
  }

  /**
   * Scrolls the page by a given left and top coordinates
   * @param $left
   * @param $top
   * @return mixed
   */
  public function scrollTo($left, $top) {
    return $this->command('scroll_to', $left, $top);
  }
}
