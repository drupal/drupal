<?php

namespace Drupal\FunctionalJavascriptTests;

/**
 * Provides functions for simulating sort changes.
 *
 * Selenium uses ChromeDriver for FunctionalJavascript tests, but it does not
 * currently support HTML5 drag and drop. These methods manipulate the DOM.
 * This trait should be deprecated when the Chromium bug is fixed.
 *
 * @see https://www.drupal.org/project/drupal/issues/3078152
 */
trait SortableTestTrait {

  /**
   * Define to provide any necessary callback following layout change.
   *
   * @param string $item
   *   The HTML selector for the element to be moved.
   * @param string $from
   *   The HTML selector for the previous container element.
   * @param null|string $to
   *   The HTML selector for the target container.
   */
  abstract protected function sortableUpdate($item, $from, $to = NULL);

  /**
   * Simulates a drag on an element from one container to another.
   *
   * @param string $item
   *   The HTML selector for the element to be moved.
   * @param string $from
   *   The HTML selector for the previous container element.
   * @param null|string $to
   *   The HTML selector for the target container.
   */
  protected function sortableTo($item, $from, $to) {
    $item = addslashes($item);
    $from = addslashes($from);
    $to   = addslashes($to);

    $script = <<<JS
(function (src, to) {
  var sourceElement = document.querySelector(src);
  var toElement = document.querySelector(to);

  toElement.insertBefore(sourceElement, toElement.firstChild);
})('{$item}', '{$to}')

JS;

    $options = [
      'script' => $script,
      'args'   => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
    $this->sortableUpdate($item, $from, $to);
  }

  /**
   * Simulates a drag moving an element after its sibling in the same container.
   *
   * @param string $item
   *   The HTML selector for the element to be moved.
   * @param string $target
   *   The HTML selector for the sibling element.
   * @param string $from
   *   The HTML selector for the element container.
   */
  protected function sortableAfter($item, $target, $from) {
    $item   = addslashes($item);
    $target = addslashes($target);
    $from   = addslashes($from);

    $script = <<<JS
(function (src, to) {
  var sourceElement = document.querySelector(src);
  var toElement = document.querySelector(to);

  toElement.insertAdjacentElement('afterend', sourceElement);
})('{$item}', '{$target}')

JS;

    $options = [
      'script' => $script,
      'args'   => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
    $this->sortableUpdate($item, $from);
  }

}
