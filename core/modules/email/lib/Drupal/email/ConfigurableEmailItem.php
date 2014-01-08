<?php

/**
 * @file
 * Contains \Drupal\email\ConfigurableEmailItem.
 */

namespace Drupal\email;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\Core\Field\ConfigFieldItemInterface;

/**
 * Alternative plugin implementation for the 'email' entity field type.
 *
 * Replaces the default implementation and supports configurable fields.
 */
class ConfigurableEmailItem extends EmailItem implements ConfigFieldItemInterface {

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
