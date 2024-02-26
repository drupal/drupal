<?php

/**
 * @file
 * Post update functions for Taxonomy.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Implements hook_removed_post_updates().
 */
function taxonomy_removed_post_updates() {
  return [
    'taxonomy_post_update_clear_views_data_cache' => '9.0.0',
    'taxonomy_post_update_clear_entity_bundle_field_definitions_cache' => '9.0.0',
    'taxonomy_post_update_handle_publishing_status_addition_in_views' => '9.0.0',
    'taxonomy_post_update_remove_hierarchy_from_vocabularies' => '9.0.0',
    'taxonomy_post_update_make_taxonomy_term_revisionable' => '9.0.0',
    'taxonomy_post_update_configure_status_field_widget' => '9.0.0',
    'taxonomy_post_update_clear_views_argument_validator_plugins_cache' => '10.0.0',
  ];
}

/**
 * Re-save Taxonomy configurations with new_revision config.
 */
function taxonomy_post_update_set_new_revision(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'taxonomy_vocabulary', function () {
        return TRUE;
    });
}

/**
 * Converts empty `description` in vocabularies to NULL.
 */
function taxonomy_post_update_set_vocabulary_description_to_null(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'taxonomy_vocabulary', function (VocabularyInterface $vocabulary): bool {
      // @see taxonomy_taxonomy_vocabulary_presave()
      return trim($vocabulary->getDescription()) === '';
    });
}
