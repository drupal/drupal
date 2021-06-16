<?php

namespace Drupal\Tests\ckeditor\Traits;

use Drupal\FunctionalJavascriptTests\SortableTestTrait;

/**
 * Provides callback for simulated CKEditor toolbar configuration change.
 */
trait CKEditorAdminSortTrait {

  use SortableTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function sortableUpdate($item, $from, $to = NULL) {
    $script = <<<JS
(function () {
  // Set backbone model after a DOM change.
  Drupal.ckeditor.models.Model.set('isDirty', true);
})()

JS;

    $options = [
      'script' => $script,
      'args'   => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
  }

}
