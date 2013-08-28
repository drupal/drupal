<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\field\widget\ImageWidget.
 */

namespace Drupal\image\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\file\Plugin\field\widget\FileWidget;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'image_image' widget.
 *
 * @FieldWidget(
 *   id = "image_image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "progress_indicator" = "throbber",
 *     "preview_image_style" = "thumbnail",
 *   }
 * )
 */
class ImageWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['preview_image_style'] = array(
      '#title' => t('Preview image style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#empty_option' => '<' . t('no preview') . '>',
      '#default_value' => $this->getSetting('preview_image_style'),
      '#description' => t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('preview_image_style');
    if (isset($image_styles[$image_style_setting])) {
      $preview_image_style = t('Preview image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $preview_image_style = t('Original image');
    }

    array_unshift($summary, $preview_image_style);

    return $summary;
  }

  /**
   * Overrides \Drupal\file\Plugin\field\widget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(EntityInterface $entity, FieldInterface $items, $langcode, array &$form, array &$form_state) {
    $elements = parent::formMultipleElements($entity, $items, $langcode, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldCardinality();
    $file_upload_help = array(
      '#theme' => 'file_upload_help',
      '#upload_validators' => $elements[0]['#upload_validators'],
      '#cardinality' => $cardinality,
    );
    if ($cardinality == 1) {
      // If there's only one field, return it as delta 0.
      if (empty($elements[0]['#default_value']['fids'])) {
        $file_upload_help['#description'] = $this->fieldDefinition->getFieldDescription();
        $elements[0]['#description'] = drupal_render($file_upload_help);
      }
    }
    else {
      $elements['#file_upload_description'] = drupal_render($file_upload_help);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldInterface $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $langcode, $form, $form_state);

    $field_settings = $this->getFieldSettings();

    // Add upload resolution validation.
    if ($field_settings['max_resolution'] || $field_settings['min_resolution']) {
      $element['#upload_validators']['file_validate_image_resolution'] = array($field_settings['max_resolution'], $field_settings['min_resolution']);
    }

    // If not using custom extension validation, ensure this is an image.
    $supported_extensions = array('png', 'gif', 'jpg', 'jpeg');
    $extensions = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : implode(' ', $supported_extensions);
    $extensions = array_intersect(explode(' ', $extensions), $supported_extensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add all extra functionality provided by the image widget.
    $element['#process'][] = 'image_field_widget_process';
    // Add properties needed by image_field_widget_process().
    $element['#preview_image_style'] = $this->getSetting('preview_image_style');
    $element['#title_field'] = $field_settings['title_field'];
    $element['#alt_field'] = $field_settings['alt_field'];
    $element['#alt_field_required'] = $field_settings['alt_field_required'];

    return $element;
  }

}
