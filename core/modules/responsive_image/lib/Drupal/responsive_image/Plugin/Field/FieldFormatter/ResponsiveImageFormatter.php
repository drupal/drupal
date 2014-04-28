<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Plugin\field\formatter\ResponsiveImageFormatter.
 */

namespace Drupal\responsive_image\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
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
  public function settingsForm(array $form, array &$form_state) {
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

    $breakpoint_styles = array();
    $fallback_image_style = '';

    $responsive_image_mapping = entity_load('responsive_image_mapping', $this->getSetting('responsive_image_mapping'));
    if ($responsive_image_mapping) {
      foreach ($responsive_image_mapping->getMappings() as $breakpoint_name => $multipliers) {
        // Make sure there are multipliers.
        if (!empty($multipliers)) {
          // Make sure that the breakpoint exists and is enabled.
          // @todo add the following when breakpoint->status is added again:
          // $responsive_image_mapping->breakpointGroup->breakpoints[$breakpoint_name]->status
          $breakpoint = $responsive_image_mapping->getBreakpointGroup()->getBreakpointById($breakpoint_name);
          if ($breakpoint) {
            // Determine the enabled multipliers.
            $multipliers = array_intersect_key($multipliers, $breakpoint->multipliers);
            foreach ($multipliers as $multiplier => $image_style) {
              // Make sure the multiplier still exists.
              if (!empty($image_style)) {
                // First mapping found is used as fallback.
                if (empty($fallback_image_style)) {
                  $fallback_image_style = $image_style;
                }
                if (!isset($breakpoint_styles[$breakpoint_name])) {
                  $breakpoint_styles[$breakpoint_name] = array();
                }
                $breakpoint_styles[$breakpoint_name][$multiplier] = $image_style;
              }
            }
          }
        }
      }
    }

    // Check if the user defined a custom fallback image style.
    if ($this->getSetting('fallback_image_style')) {
      $fallback_image_style = $this->getSetting('fallback_image_style');
    }

    // Collect cache tags to be added for each item in the field.
    $all_cache_tags = array();
    if ($responsive_image_mapping) {
      $all_cache_tags[] = $responsive_image_mapping->getCacheTag();
      foreach ($breakpoint_styles as $breakpoint_name => $style_per_multiplier) {
        foreach ($style_per_multiplier as $multiplier => $image_style_name) {
          $image_style = entity_load('image_style', $image_style_name);
          $all_cache_tags[] = $image_style->getCacheTag();
        }
      }
    }
    if ($fallback_image_style) {
      $image_style = entity_load('image_style', $fallback_image_style);
      $all_cache_tags[] = $image_style->getCacheTag();
    }
    $cache_tags = NestedArray::mergeDeepArray($all_cache_tags);

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
        '#breakpoints' => $breakpoint_styles,
        '#path' => isset($uri) ? $uri : '',
        '#cache' => array(
          'tags' => $cache_tags,
        )
      );
    }

    return $elements;
  }
}
