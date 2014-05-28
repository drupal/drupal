<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\field\formatter\ImageFormatter.
 */

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'image_link' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        // @todo Remove when theme_image_formatter() has support for route name.
        $uri['path'] = $entity->getSystemPath();
        $uri['options'] = $entity->urlInfo()->getOptions();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = entity_load('image_style', $image_style_setting);
      $cache_tags = $image_style->getCacheTag();
    }

    foreach ($items as $delta => $item) {
      if ($item->entity) {
        if (isset($link_file)) {
          $image_uri = $item->entity->getFileUri();
          $uri = array(
            'path' => file_create_url($image_uri),
            'options' => array(),
          );
        }

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[$delta] = array(
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $image_style_setting,
          '#path' => isset($uri) ? $uri : '',
          '#cache' => array(
            'tags' => $cache_tags,
          ),
        );
      }
    }

    return $elements;
  }

}
