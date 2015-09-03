<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Base class for file formatters, which allow to link to the file download URL.
 */
abstract class BaseFieldFileFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['link_to_file'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to_file'] = [
      '#title' => $this->t('Link this field to the file download URL'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link_to_file'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];

    $url = NULL;
    // Add support to link to the entity itself.
    if ($this->getSetting('link_to_file')) {
      $url = file_create_url($items->getEntity()->uri->value);
    }

    foreach ($items as $delta => $item) {
      $string = $this->viewValue($item);

      if ($url) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $string,
          '#url' => Url::fromUri($url),
        ];
      }
      else {
        $elements[$delta] = is_array($string) ? $string : ['#markup' => $string];
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return mixed
   *   The textual output generated.
   */
  abstract protected function viewValue(FieldItemInterface $item);

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'file';
  }

}
