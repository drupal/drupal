<?php

declare(strict_types=1);

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;

/**
 * Provides test media source.
 */
#[MediaSource(
  id: "test",
  label: new TranslatableMarkup("Test source"),
  description: new TranslatableMarkup("Test media source."),
  allowed_field_types: ["string"]
)]
class Test extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    // The metadata attributes are kept in state storage. This allows tests to
    // change the metadata attributes and makes it easier to test different
    // variations.
    $attributes = \Drupal::state()->get('media_source_test_attributes', [
      'attribute_1' => ['label' => 'Attribute 1', 'value' => 'Value 1'],
      'attribute_2' => ['label' => 'Attribute 2', 'value' => 'Value 1'],
    ]);
    return array_map(function ($item) {
      return $item['label'];
    }, $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $attributes = \Drupal::state()->get('media_source_test_attributes', [
      'attribute_1' => ['label' => 'Attribute 1', 'value' => 'Value 1'],
      'attribute_2' => ['label' => 'Attribute 2', 'value' => 'Value 1'],
    ]);

    if (in_array($attribute_name, array_keys($attributes))) {
      return $attributes[$attribute_name]['value'];
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return NestedArray::mergeDeep(
      parent::getPluginDefinition(),
      \Drupal::state()->get('media_source_test_definition', [])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'test_config_value' => 'This is default value.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['test_config_value'] = [
      '#type' => 'textfield',
      '#title' => 'Test config value',
      '#default_value' => $this->configuration['test_config_value'],
    ];

    return $form;
  }

}
