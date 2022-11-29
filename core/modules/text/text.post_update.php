<?php

/**
 * @file
 * Contains post update hooks for the text module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\field\FieldConfigInterface;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * Implements hook_removed_post_updates().
 */
function text_removed_post_updates() {
  return [
    'text_post_update_add_required_summary_flag' => '9.0.0',
    'text_post_update_add_required_summary_flag_form_display' => '10.0.0',
  ];
}

/**
 * Add allowed_formats setting to existing text fields.
 */
function text_post_update_allowed_formats(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'field_config', function (FieldConfigInterface $field_config) {
      $class = get_class($field_config);
      // Deal only with text fields and descendants.
      if (is_a($class, TextItemBase::class, TRUE)) {
        // Get the existing allowed_formats setting.
        $allowed_formats = $field_config->get('settings.allowed_formats');
        if (!is_array($allowed_formats) && empty($allowed_formats)) {
          // Save default value if existing value not present.
          $field_config->set('settings.allowed_formats', []);
          return TRUE;
        }
      }
      return FALSE;
    });
}
