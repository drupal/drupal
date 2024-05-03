<?php

namespace Drupal\layout_builder_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Layout plugin without a label configuration.
 */
#[Layout(
  id: 'layout_without_label',
  label: new TranslatableMarkup('Layout Without Label'),
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
  ],
)]
class LayoutWithoutLabel extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
