<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;

/**
 * Class PhantomJSDriver
 * @package Behat\Mink\Driver
 */
class PhantomJSDriver extends BasePhantomJSDriver {

  use SessionTrait;
  use NavigationTrait;
  use CookieTrait;
  use HeadersTrait;
  use JavascriptTrait;
  use MouseTrait;
  use PageContentTrait;
  use KeyboardTrait;
  use FormManipulationTrait;
  use WindowTrait;

  /**
   * Sets the basic auth user and password
   * @param string $user
   * @param string $password
   */
  public function setBasicAuth($user, $password) {
    $this->browser->setHttpAuth($user, $password);
  }

  /**
   * Gets the tag name of a given xpath
   * @param string $xpath
   * @return string
   * @throws DriverException
   */
  public function getTagName($xpath) {
    $elements = $this->findElement($xpath, 1);
    return $this->browser->tagName($elements["page_id"], $elements["ids"][0]);
  }

  /**
   * Gets the attribute value of a given element and name
   * @param string $xpath
   * @param string $name
   * @return string
   * @throws DriverException
   */
  public function getAttribute($xpath, $name) {
    $elements = $this->findElement($xpath, 1);
    return $this->browser->attribute($elements["page_id"], $elements["ids"][0], $name);
  }

  /**
   * Check if element given by xpath is visible or not
   * @param string $xpath
   * @return bool
   * @throws DriverException
   */
  public function isVisible($xpath) {
    $elements = $this->findElement($xpath, 1);
    return $this->browser->isVisible($elements["page_id"], $elements["ids"][0]);
  }

  /**
   * Drags one element to another
   * @param string $sourceXpath
   * @param string $destinationXpath
   * @throws DriverException
   */
  public function dragTo($sourceXpath, $destinationXpath) {
    $sourceElement = $this->findElement($sourceXpath, 1);
    $destinationElement = $this->findElement($destinationXpath, 1);
    $this->browser->drag($sourceElement["page_id"], $sourceElement["ids"][0], $destinationElement["ids"][0]);
  }

  /**
   * Upload a file to the browser
   * @param string $xpath
   * @param string $path
   * @throws DriverException
   */
  public function attachFile($xpath, $path) {
    if (!file_exists($path)) {
      throw new DriverException("Wow there the file does not exist, you can not upload it");
    }

    if (($realPath = realpath($path)) === false) {
      throw new DriverException("Wow there the file does not exist, you can not upload it");
    }

    $element = $this->findElement($xpath, 1);
    $tagName = $this->getTagName($xpath);
    if ($tagName != "input") {
      throw new DriverException("The element is not an input element, you can not attach a file to it");
    }

    $attributes = $this->getBrowser()->attributes($element["page_id"], $element["ids"][0]);
    if (!isset($attributes["type"]) || $attributes["type"] != "file") {
      throw new DriverException("The element is not an input file type element, you can not attach a file to it");
    }

    $this->browser->selectFile($element["page_id"], $element["ids"][0], $realPath);
  }

  /**
   * Puts the browser control inside the IFRAME
   * You own the control, make sure to go back to the parent calling this method with null
   * @param string $name
   */
  public function switchToIFrame($name = null) {
    //TODO: check response of the calls
    if ($name === null) {
      $this->browser->popFrame();
      return;
    } else {
      $this->browser->pushFrame($name);
    }
  }

  /**
   * Focus on an element
   * @param string $xpath
   * @throws DriverException
   */
  public function focus($xpath) {
    $element = $this->findElement($xpath, 1);
    $this->browser->trigger($element["page_id"], $element["ids"][0], "focus");
  }

  /**
   * Blur on element
   * @param string $xpath
   * @throws DriverException
   */
  public function blur($xpath) {
    $element = $this->findElement($xpath, 1);
    $this->browser->trigger($element["page_id"], $element["ids"][0], "blur");
  }

  /**
   * Finds elements with specified XPath query.
   * @param string $xpath
   * @return NodeElement[]
   * @throws DriverException                  When the operation cannot be done
   */
  public function find($xpath) {
    $elements = $this->browser->find("xpath", $xpath);
    $nodeElements = array();

    if (!isset($elements["ids"])) {
      return null;
    }

    foreach ($elements["ids"] as $i => $elementId) {
      $nodeElements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $i + 1), $this->session);
    }
    return $nodeElements;
  }

}
