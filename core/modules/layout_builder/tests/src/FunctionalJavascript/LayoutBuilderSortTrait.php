<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\SortableTestTrait;

/**
 * LayoutBuilderSortTrait, provides callback for simulated layout change.
 */
trait LayoutBuilderSortTrait {

  use SortableTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function sortableUpdate($item, $from, $to = NULL) {
    // If container does not change, $from and $to are equal.
    $to = $to ?: $from;

    $script = <<<JS
(function (src, from, to) {
  var sourceElement = document.querySelector(src);
  var fromElement = document.querySelector(from);
  var toElement = document.querySelector(to);

  Drupal.layoutBuilderBlockUpdate(sourceElement, fromElement, toElement)

})('{$item}', '{$from}', '{$to}')

JS;

    $options = [
      'script' => $script,
      'args'   => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
  }

}
