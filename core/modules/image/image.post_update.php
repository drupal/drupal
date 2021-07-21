<?php

/**
 * @file
 * Post-update functions for Image.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\filter\FilterFormatInterface;

/**
 * Implements hook_removed_post_updates().
 */
function image_removed_post_updates() {
  return [
    'image_post_update_image_style_dependencies' => '9.0.0',
    'image_post_update_scale_and_crop_effect_add_anchor' => '9.0.0',
  ];
}

/**
 * Update filter formats to allow the use of the image style filter.
 */
function image_post_update_enable_filter_image_style(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'filter_format', function (FilterFormatInterface $format): bool {
    /** @var \Drupal\filter\Plugin\FilterInterface $filter */
    if (!($filter = $format->filters('filter_html'))) {
      return FALSE;
    }

    $config = $filter->getConfiguration();
    $allowed_html = !empty($config['settings']['allowed_html']) ? $config['settings']['allowed_html'] : NULL;
    $matches = [];
    if ($allowed_html && preg_match('/<img([^>]*)>/', $allowed_html, $matches)) {
      $attributes = array_filter(preg_split('/\s/', $matches[1]));
      $attributes[] = 'data-image-style';
      $config['settings']['allowed_html'] = preg_replace('/<img([^>]*)>/', '<img ' . implode(' ', array_unique($attributes)) . '>', $allowed_html);
      $format->setFilterConfig('filter_html', $config);
      return TRUE;
    }
    return FALSE;
  });
}
