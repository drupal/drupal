<?php

namespace Drupal\simpletest;

use Behat\Mink\WebAssert as MinkWebAssert;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Defines a class with methods for asserting presence of elements during tests.
 */
class WebAssert extends MinkWebAssert {

  /**
   * Checks that specific button exists on the current page.
   *
   * @param string $button
   *   One of id|name|label|value for the button.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function buttonExists($button, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findButton($button);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session, 'button', 'id|name|label|value', $button);
    }

    return $node;
  }

  /**
   * Checks that specific select field exists on the current page.
   *
   * @param string $select
   *   One of id|name|label|value for the select field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function selectExists($select, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->find('named', array(
      'select',
      $this->session->getSelectorsHandler()->xpathLiteral($select),
    ));

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session, 'select', 'id|name|label|value', $select);
    }

    return $node;
  }

}
