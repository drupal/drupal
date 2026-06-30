<?php

/**
 * @file
 * Post update functions for Content Translation.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Field\FieldConfigInterface;

/**
 * Update content_translated configuration for base_field_override entities.
 */
function content_translation_post_update_resave_base_field_overrides(array &$sandbox): void {
  /** @var \Drupal\Core\Config\Entity\ConfigEntityUpdater $config_updater */
  $config_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_updater->update($sandbox, 'base_field_override', function (FieldConfigInterface $field) {
    // Resave base field overrides that have content translation enabled.
    return (bool) $field->getThirdPartySettings('content_translation');
  }, TRUE);
}
