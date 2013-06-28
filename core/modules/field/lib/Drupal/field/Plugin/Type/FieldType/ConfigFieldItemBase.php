<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase.
 */

namespace Drupal\field\Plugin\Type\FieldType;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Base class for 'configurable field type' plugin implementations.
 */
abstract class ConfigFieldItemBase extends FieldItemBase implements ConfigFieldItemInterface {

  /**
   * The Field instance definition.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  public $instance;

  /**
   * {@inheritdoc}
   */
  public function getInstance() {
    if (!isset($this->instance) && $parent = $this->getParent()) {
      $this->instance = $parent->getInstance();
    }
    return $this->instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    return array();
  }

}
