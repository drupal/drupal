<?php

namespace Drupal\Core\Form;

/**
 * Implements ::getEditableConfigNames() for forms using #config_target.
 */
trait RedundantEditableConfigNamesTrait {
  use ConfigFormBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This form uses #config_target instead.
    return [];
  }

}
