<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\field\formatter\PictureFormatter.
 */

namespace Drupal\picture\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin for picture formatter.
 *
 * @FieldFormatter(
 *   id = "picture",
 *   label = @Translation("Picture"),
 *   field_types = {
 *     "image",
 *   },
 *   settings = {
 *     "picture_mapping" = "",
 *     "fallback_image_style" = "",
 *     "image_link" = "",
 *   }
 * )
 */
class PictureFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $picture_options = array();
    $picture_mappings = entity_load_multiple('picture_mapping');
    if ($picture_mappings && !empty($picture_mappings)) {
      foreach ($picture_mappings as $machine_name => $picture_mapping) {
        if ($picture_mapping->hasMappings()) {
          $picture_options[$machine_name] = $picture_mapping->label();
        }
      }
    }

    $elements['picture_mapping'] = array(
      '#title' => t('Picture mapping'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('picture_mapping'),
      '#required' => TRUE,
      '#options' => $picture_options,
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

    $picture_mapping = entity_load('picture_mapping', $this->getSetting('picture_mapping'));
    if ($picture_mapping) {
      $summary[] = t('Picture mapping: @picture_mapping', array('@picture_mapping' => $picture_mapping->label()));

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
      $summary[] = t('Select a picture mapping.');
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
      $uri = $items->getEntity()->uri();
    }
    elseif ($this->getSetting('image_link') == 'file') {
      $link_file = TRUE;
    }

    $breakpoint_styles = array();
    $fallback_image_style = '';

    $picture_mapping = entity_load('picture_mapping', $this->getSetting('picture_mapping'));
    if ($picture_mapping) {
      foreach ($picture_mapping->mappings as $breakpoint_name => $multipliers) {
        // Make sure there are multipliers.
        if (!empty($multipliers)) {
          // Make sure that the breakpoint exists and is enabled.
          // @todo add the following when breakpoint->status is added again:
          // $picture_mapping->breakpointGroup->breakpoints[$breakpoint_name]->status
          $breakpoint = $picture_mapping->breakpointGroup->getBreakpointById($breakpoint_name);
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

    foreach ($items as $delta => $item) {
      if (isset($link_file)) {
        $uri = array(
          'path' => file_create_url($item->entity->getFileUri()),
          'options' => array(),
        );
      }
      $elements[$delta] = array(
        '#theme' => 'picture_formatter',
        '#attached' => array('library' => array(
          array('picture', 'picturefill'),
        )),
        '#item' => $item->getValue(TRUE),
        '#image_style' => $fallback_image_style,
        '#breakpoints' => $breakpoint_styles,
        '#path' => isset($uri) ? $uri : '',
      );
    }

    return $elements;
  }
}
