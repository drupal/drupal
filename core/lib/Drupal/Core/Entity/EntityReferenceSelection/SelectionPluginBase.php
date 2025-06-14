<?php

namespace Drupal\Core\Entity\EntityReferenceSelection;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ConfigurablePluginBase;

/**
 * Provides a base class for configurable selection handlers.
 */
abstract class SelectionPluginBase extends ConfigurablePluginBase implements SelectionInterface, DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'target_type' => NULL,
      'entity' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {}

}
