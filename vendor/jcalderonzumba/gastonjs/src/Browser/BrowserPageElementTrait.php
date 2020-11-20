<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserPageElementTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserPageElementTrait {
  /**
   * Find elements given a method and a selector
   * @param $method
   * @param $selector
   * @return array
   */
  public function find($method, $selector) {
    $result = $this->command('find', $method, $selector);
    $found["page_id"] = $result["page_id"];
    foreach ($result["ids"] as $id) {
      $found["ids"][] = $id;
    }
    return $found;
  }

  /**
   * Find elements within a page, method and selector
   * @param $pageId
   * @param $elementId
   * @param $method
   * @param $selector
   * @return mixed
   */
  public function findWithin($pageId, $elementId, $method, $selector) {
    return $this->command('find_within', $pageId, $elementId, $method, $selector);
  }

  /**
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function getParents($pageId, $elementId) {
    return $this->command('parents', $pageId, $elementId);
  }

  /**
   * Returns the text of a given page and element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function allText($pageId, $elementId) {
    return $this->command('all_text', $pageId, $elementId);
  }

  /**
   * Returns the inner or outer html of the given page and element
   * @param $pageId
   * @param $elementId
   * @param $type
   * @return mixed
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function allHtml($pageId, $elementId, $type = "inner") {
    return $this->command('all_html', $pageId, $elementId, $type);
  }

  /**
   * Returns ONLY the visible text of a given page and element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function visibleText($pageId, $elementId) {
    return $this->command('visible_text', $pageId, $elementId);
  }

  /**
   * Deletes the text of a given page and element
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function deleteText($pageId, $elementId) {
    return $this->command('delete_text', $pageId, $elementId);
  }

  /**
   * Gets the tag name of a given element and page
   * @param $pageId
   * @param $elementId
   * @return string
   */
  public function tagName($pageId, $elementId) {
    return strtolower($this->command('tag_name', $pageId, $elementId));
  }

  /**
   * Check if two elements are the same on a give
   * @param $pageId
   * @param $firstId
   * @param $secondId
   * @return bool
   */
  public function equals($pageId, $firstId, $secondId) {
    return $this->command('equals', $pageId, $firstId, $secondId);
  }

  /**
   * Returns the attributes of an element in a given page
   * @param $pageId
   * @param $elementId
   * @return mixed
   */
  public function attributes($pageId, $elementId) {
    return $this->command('attributes', $pageId, $elementId);
  }

  /**
   * Returns the attribute of an element by name in a given page
   * @param $pageId
   * @param $elementId
   * @param $name
   * @return mixed
   */
  public function attribute($pageId, $elementId, $name) {
    return $this->command('attribute', $pageId, $elementId, $name);
  }

  /**
   * Set an attribute to the given element in the given page
   * @param $pageId
   * @param $elementId
   * @param $name
   * @param $value
   * @return mixed
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function setAttribute($pageId, $elementId, $name, $value) {
    return $this->command('set_attribute', $pageId, $elementId, $name, $value);
  }

  /**
   * Remove an attribute for a given page and element
   * @param $pageId
   * @param $elementId
   * @param $name
   * @return mixed
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function removeAttribute($pageId, $elementId, $name) {
    return $this->command('remove_attribute', $pageId, $elementId, $name);
  }

  /**
   * Checks if an element is visible or not
   * @param $pageId
   * @param $elementId
   * @return boolean
   */
  public function isVisible($pageId, $elementId) {
    return $this->command("visible", $pageId, $elementId);
  }

  /**
   * Sends the order to execute a key event on a given element
   * @param $pageId
   * @param $elementId
   * @param $keyEvent
   * @param $key
   * @param $modifier
   * @return mixed
   */
  public function keyEvent($pageId, $elementId, $keyEvent, $key, $modifier) {
    return $this->command("key_event", $pageId, $elementId, $keyEvent, $key, $modifier);
  }

  /**
   * Sends the command to select and option given a value
   * @param      $pageId
   * @param      $elementId
   * @param      $value
   * @param bool $multiple
   * @return mixed
   */
  public function selectOption($pageId, $elementId, $value, $multiple = false) {
    return $this->command("select_option", $pageId, $elementId, $value, $multiple);
  }

}
