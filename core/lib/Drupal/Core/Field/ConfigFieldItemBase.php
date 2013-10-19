<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigFieldItemBase.
 */

namespace Drupal\Core\Field;

/**
 * Base class for 'configurable field type' plugin implementations.
 */
abstract class ConfigFieldItemBase extends FieldItemBase implements ConfigFieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    return array();
  }

}
