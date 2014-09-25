<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Plugin\field\formatter\ResponsiveImageFormatter.
 */

namespace Drupal\responsive_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_image",
 *   label = @Translation("Responsive image"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ResponsiveImageFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'responsive_image_mapping' => '',
      'fallback_image_style' => '',
      'image_link' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_options = array();
    $responsive_image_mappings = entity_load_multiple('responsive_image_mapping');
    if ($responsive_image_mappings && !empty($responsive_image_mappings)) {
      foreach ($responsive_image_mappings as $machine_name => $responsive_image_mapping) {
        if ($responsive_image_mapping->hasMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_mapping->label();
        }
      }
    }

    $elements['responsive_image_mapping'] = array(
      '#title' => t('Responsive image mapping'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_mapping'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
    );

    $image_styles = image_style_options(FALSE);
    $elements['fallback_image_style'] = array(
      '#title' => t('Fallback image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('fallback_image_style'),
      '#empty_option' => t('Automatic'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $elements['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $responsive_image_mapping = entity_load('responsive_image_mapping', $this->getSetting('responsive_image_mapping'));
    if ($responsive_image_mapping) {
      $summary[] = t('Responsive image mapping: @responsive_image_mapping', array('@responsive_image_mapping' => $responsive_image_mapping->label()));

      $image_styles = image_style_options(FALSE);
      unset($image_styles['']);
      if (isset($image_styles[$this->getSetting('fallback_image_style')])) {
        $summary[] = t('Fallback Image style: @style', array('@style' => $image_styles[$this->getSetting('fallback_image_style')]));
      }
      else {
        $summary[] = t('Automatic fallback');
      }

      $link_types = array(
        'content' => t('Linked to content'),
        'file' => t('Linked to file'),
      );
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('image_link')])) {
        $summary[] = $link_types[$this->getSetting('image_link')];
      }
    }
    else {
      $summary[] = t('Select a responsive image mapping.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    // Check if the formatter involves a link.
    if ($this->getSetting('image_link') == 'content') {
      $uri = $items->getEntity()->urlInfo();
      // @todo Remove when theme_responsive_image_formatter() has support for route name.
      $uri['path'] = $items->getEntity()->getSystemPath();
    }
    elseif ($this->getSetting('image_link') == 'file') {
      $link_file = TRUE;
    }

    $fallback_image_style = '';

    // Check if the user defined a custom fallback image style.
    if ($this->getSetting('fallback_image_style')) {
      $fallback_image_style = $this->getSetting('fallback_image_style');
    }

    // Collect cache tags to be added for each item in the field.
    $responsive_image_mapping = entity_load('responsive_image_mapping', $this->getSetting('responsive_image_mapping'));
    $image_styles_to_load = array();
    if ($fallback_image_style) {
      $image_styles_to_load[] = $fallback_image_style;
    }
    $cache_tags = [];
    if ($responsive_image_mapping) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_mapping->getCacheTag());
      foreach ($responsive_image_mapping->getMappings() as $mapping) {
        // First mapping found is used as fallback.
        if (empty($fallback_image_style)) {
          $fallback_image_style = $mapping['image_style'];
        }
        $image_styles_to_load[] = $mapping['image_style'];
      }
    }
    $image_styles = entity_load_multiple('image_style', $image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTag());
    }

    foreach ($items as $delta => $item) {
      if (isset($link_file)) {
        $uri = array(
          'path' => file_create_url($item->entity->getFileUri()),
          'options' => array(),
        );
      }
      $elements[$delta] = array(
        '#theme' => 'responsive_image_formatter',
        '#attached' => array(
          'library' => array(
            'core/picturefill',
          )
        ),
        '#item' => $item,
        '#image_style' => $fallback_image_style,
        '#mapping_id' => $responsive_image_mapping ? $responsive_image_mapping->id() : '',
        '#path' => isset($uri) ? $uri : '',
        '#cache' => array(
          'tags' => $cache_tags,
        )
      );
    }

    return $elements;
  }
}
