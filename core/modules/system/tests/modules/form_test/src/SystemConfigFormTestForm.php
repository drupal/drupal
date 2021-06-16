<?php

namespace Drupal\form_test;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Tests the ConfigFormBase class.
 *
 * @internal
 */
class SystemConfigFormTestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_system_config_test_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
