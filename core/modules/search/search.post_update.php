<?php

/**
 * @file
 * Post update functions for Search module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function search_removed_post_updates(): array {
  return [
    'search_post_update_block_page' => '9.0.0',
    'search_post_update_reindex_after_diacritics_rule_change' => '10.0.0',
    'search_post_update_block_with_empty_page_id' => '12.0.0',
  ];
}

/**
 * Update config entity dependencies to the Search Help module, if necessary.
 *
 * @see search_update_11402()
 */
function search_post_update_search_help_dependencies(): void {
  if (\Drupal::moduleHandler()->moduleExists('help')) {
    // @todo https://www.drupal.org/project/drupal/issues/3587570 Determine why
    //   the search.page.help_search config entity does not have a UUID in the
    //   11.3 test database dump. This means it is not discovered as a config
    //   entity dependency of the help module.
    $search_page_config = \Drupal::configFactory()->getEditable('search.page.help_search');
    if (!$search_page_config->isNew() && $search_page_config->get('uuid') === NULL) {
      $search_page_config->set('uuid', \Drupal::service('uuid')->generate())->save();
    }

    // Update the dependencies of all help config entities if they have
    // changed.
    foreach (\Drupal::service('config.manager')->findConfigEntityDependenciesAsEntities('module', ['help']) as $entity) {
      $dependencies = $entity->getDependencies();
      if ($entity->calculateDependencies()->getDependencies() !== $dependencies) {
        $entity->save();
      }
    }
  }
}
