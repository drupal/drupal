<?php

/**
 * @file
 * Post update functions for Views.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;

/**
 * Update the cacheability metadata for all views.
 */
function views_post_update_update_cacheability_metadata() {
  // Load all views.
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();

  /* @var \Drupal\views\Entity\View[] $views */
  foreach ($views as $view) {
    $displays = $view->get('display');
    foreach (array_keys($displays) as $display_id) {
      $display =& $view->getDisplay($display_id);
      // Unset the cache_metadata key, so all cacheability metadata for the
      // display is recalculated.
      unset($display['cache_metadata']);
    }
    $view->save();
  }

}

/**
 * Update some views fields that were previously duplicated.
 */
function views_post_update_cleanup_duplicate_views_data() {
  $config_factory = \Drupal::configFactory();
  $ids = [];
  $message = NULL;
  $data_tables = [];
  $base_tables = [];
  $revision_tables = [];
  $entities_by_table = [];
  $duplicate_fields = [];
  $handler_types = Views::getHandlerTypes();

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::service('entity_type.manager');
  // This will allow us to create an index of all entity types of the site.
  foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
    // Store the entity keyed by base table. If it has a data table, use that as
    // well.
    if ($data_table = $entity_type->getDataTable()) {
      $entities_by_table[$data_table] = $entity_type;
    }
    if ($base_table = $entity_type->getBaseTable()) {
      $entities_by_table[$base_table] = $entity_type;
    }

    // The following code basically contains the same kind of logic as
    // \Drupal\Core\Entity\Sql\SqlContentEntityStorage::initTableLayout() to
    // prefetch all tables (base, data, revision, and revision data).
    $base_tables[$entity_type_id] = $entity_type->getBaseTable() ?: $entity_type->id();
    $revisionable = $entity_type->isRevisionable();

    $revision_table = '';
    if ($revisionable) {
      $revision_table = $entity_type->getRevisionTable() ?: $entity_type->id() . '_revision';
    }
    $revision_tables[$entity_type_id] = $revision_table;

    $translatable = $entity_type->isTranslatable();
    $data_table = '';
    // For example the data table just exists, when the entity type is
    // translatable.
    if ($translatable) {
      $data_table = $entity_type->getDataTable() ?: $entity_type->id() . '_field_data';
    }
    $data_tables[$entity_type_id] = $data_table;

    $duplicate_fields[$entity_type_id] = array_intersect_key($entity_type->getKeys(), array_flip(['id', 'revision', 'bundle']));
  }

  foreach ($config_factory->listAll('views.view.') as $view_config_name) {
    $changed = FALSE;
    $view = $config_factory->getEditable($view_config_name);

    $displays = $view->get('display');
    if (isset($entities_by_table[$view->get('base_table')])) {
      $entity_type = $entities_by_table[$view->get('base_table')];
      $entity_type_id = $entity_type->id();
      $data_table = $data_tables[$entity_type_id];
      $base_table = $base_tables[$entity_type_id];
      $revision_table = $revision_tables[$entity_type_id];

      if ($data_table) {
        foreach ($displays as $display_name => &$display) {
          foreach ($handler_types as $handler_type) {
            if (!empty($display['display_options'][$handler_type['plural']])) {
              foreach ($display['display_options'][$handler_type['plural']] as $field_name => &$field) {
                $table = $field['table'];
                if (($table === $base_table || $table === $revision_table) && in_array($field_name, $duplicate_fields[$entity_type_id])) {
                  $field['table'] = $data_table;
                  $changed = TRUE;
                }
              }
            }
          }
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
      $view->save();
      $ids[] = $view->get('id');
    }
  }
  if (!empty($ids)) {
    $message = new TranslatableMarkup('Updated tables for field handlers for views: @ids', ['@ids' => implode(', ', array_unique($ids))]);
  }

  return $message;
}

/**
 * Include field formatter dependencies in a view when the formatter is used.
 */
function views_post_update_field_formatter_dependencies() {
  $views = View::loadMultiple();
  array_walk($views, function (View $view) {
    $view->save();
  });
}

/**
 * Fix views with dependencies on taxonomy terms that don't exist.
 */
function views_post_update_taxonomy_index_tid() {
  $views = View::loadMultiple();
  array_walk($views, function (View $view) {
    $old_dependencies = $view->getDependencies();
    $new_dependencies = $view->calculateDependencies()->getDependencies();
    if ($old_dependencies !== $new_dependencies) {
      $view->save();
    }
  });
}

/**
 * Fix views with serializer dependencies.
 */
function views_post_update_serializer_dependencies() {
  $views = View::loadMultiple();
  array_walk($views, function (View $view) {
    $old_dependencies = $view->getDependencies();
    $new_dependencies = $view->calculateDependencies()->getDependencies();
    if ($old_dependencies !== $new_dependencies) {
      $view->save();
    }
  });
}

/**
 * Set all boolean filter values to strings.
 */
function views_post_update_boolean_filter_values() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('views.view.') as $view_config_name) {
    $view = $config_factory->getEditable($view_config_name);
    $save = FALSE;
    foreach ($view->get('display') as $display_name => $display) {
      if (isset($display['display_options']['filters'])) {
        foreach ($display['display_options']['filters'] as $filter_name => $filter) {
          if (isset($filter['plugin_id']) && $filter['plugin_id'] === 'boolean') {
            $new_value = FALSE;
            // Update all boolean and integer values to strings.
            if ($filter['value'] === TRUE || $filter['value'] === 1) {
              $new_value = '1';
            }
            elseif ($filter['value'] === FALSE || $filter['value'] === 0) {
              $new_value = '0';
            }
            if ($new_value !== FALSE) {
              $view->set("display.$display_name.display_options.filters.$filter_name.value", $new_value);
              $save = TRUE;
            }
          }
        }
      }
    }
    if ($save) {
      $view->save();
    }
  }
}

/**
 * Rebuild caches to ensure schema changes are read in.
 */
function views_post_update_grouped_filters() {
  // Empty update to cause a cache rebuild so that the schema changes are read.
}

/**
 * Fix table names for revision metadata fields.
 *
 * @see https://www.drupal.org/node/2831499
 */
function views_post_update_revision_metadata_fields() {
  // The table names are fixed automatically in
  // \Drupal\views\Entity\View::preSave(), so we just need to re-save all views.
  $views = View::loadMultiple();
  array_walk($views, function (View $view) {
    $view->save();
  });
}

/**
 * Add additional settings to the entity link field and convert node_path usage
 * to entity_link.
 */
function views_post_update_entity_link_url() {
  // Load all views.
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();

  /* @var \Drupal\views\Entity\View[] $views */
  foreach ($views as $view) {
    $displays = $view->get('display');
    $changed = FALSE;
    foreach ($displays as $display_name => &$display) {
      if (isset($display['display_options']['fields'])) {
        foreach ($display['display_options']['fields'] as $field_name => &$field) {
          if (isset($field['plugin_id']) && $field['plugin_id'] === 'entity_link') {
            // Add any missing settings for entity_link.
            if (!isset($field['output_url_as_text'])) {
              $field['output_url_as_text'] = FALSE;
              $changed = TRUE;
            }
            if (!isset($field['absolute'])) {
              $field['absolute'] = FALSE;
              $changed = TRUE;
            }
          }
          elseif (isset($field['plugin_id']) && $field['plugin_id'] === 'node_path') {
            // Convert the use of node_path to entity_link.
            $field['plugin_id'] = 'entity_link';
            $field['field'] = 'view_node';
            $field['output_url_as_text'] = TRUE;
            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {
      $view->set('display', $displays);
      $view->save();
    }
  }
}

/**
 * Update dependencies for moved bulk field plugin.
 */
function views_post_update_bulk_field_moved() {
  $views = View::loadMultiple();
  array_walk($views, function (View $view) {
    $old_dependencies = $view->getDependencies();
    $new_dependencies = $view->calculateDependencies()->getDependencies();
    if ($old_dependencies !== $new_dependencies) {
      $view->save();
    }
  });
}

/**
 * Add placeholder settings to string or numeric filters.
 */
function views_post_update_filter_placeholder_text() {
  // Load all views.
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();
  /** @var \Drupal\views\Plugin\ViewsHandlerManager $filter_manager */
  $filter_manager = \Drupal::service('plugin.manager.views.filter');

  /* @var \Drupal\views\Entity\View[] $views */
  foreach ($views as $view) {
    $displays = $view->get('display');
    $save = FALSE;
    foreach ($displays as $display_name => &$display) {
      if (isset($display['display_options']['filters'])) {
        foreach ($display['display_options']['filters'] as $filter_name => &$filter) {
          // Any of the children of the modified classes will also be inheriting
          // the new settings.
          $filter_instance = $filter_manager->getHandler($filter);
          if ($filter_instance instanceof StringFilter) {
            if (!isset($filter['expose']['placeholder'])) {
              $filter['expose']['placeholder'] = '';
              $save = TRUE;
            }
          }
          elseif ($filter_instance instanceof NumericFilter) {
            if (!isset($filter['expose']['placeholder'])) {
              $filter['expose']['placeholder'] = '';
              $save = TRUE;
            }
            if (!isset($filter['expose']['min_placeholder'])) {
              $filter['expose']['min_placeholder'] = '';
              $save = TRUE;
            }
            if (!isset($filter['expose']['max_placeholder'])) {
              $filter['expose']['max_placeholder'] = '';
              $save = TRUE;
            }
          }
        }
      }
    }
    if ($save) {
      $view->set('display', $displays);
      $view->save();
    }
  }
}

/**
 * Include views data table provider in views dependencies.
 */
function views_post_update_views_data_table_dependencies(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('view');
  if (!isset($sandbox['views'])) {
    $sandbox['views'] = $storage->getQuery()->accessCheck(FALSE)->execute();
    $sandbox['count'] = count($sandbox['views']);
  }

  // Process 10 views at a time.
  $views = $storage->loadMultiple(array_splice($sandbox['views'], 0, 10));
  foreach ($views as $view) {
    $original_dependencies = $view->getDependencies();
    // Only re-save if dependencies have changed.
    if ($view->calculateDependencies()->getDependencies() !== $original_dependencies) {
      // We can trust the data because we've already recalculated the
      // dependencies.
      $view->trustData();
      $view->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['views']) ? 1 : ($sandbox['count'] - count($sandbox['views'])) / $sandbox['count'];
}

/**
 * Fix cache max age for table displays.
 */
function views_post_update_table_display_cache_max_age(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $displays = $view->get('display');
    foreach ($displays as $display_name => &$display) {
      if (isset($display['display_options']['style']['type']) && $display['display_options']['style']['type'] === 'table') {
        return TRUE;
      }
    }
    return FALSE;
  });
}

/**
 * Update exposed filter blocks label display to be disabled.
 */
function views_post_update_exposed_filter_blocks_label_display(&$sandbox = NULL) {
  // If Block is not installed, there's nothing to do.
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', function ($block) {
    /** @var \Drupal\block\BlockInterface $block */
    if (strpos($block->getPluginId(), 'views_exposed_filter_block:') === 0) {
      $block->getPlugin()->setConfigurationValue('label_display', '0');
      return TRUE;
    }

    return FALSE;
  });
}

/**
 * Rebuild cache to allow placeholder texts to be translatable.
 */
function views_post_update_make_placeholders_translatable() {
  // Empty update to cause a cache rebuild to allow placeholder texts to be
  // translatable.
}

/**
 * Define default values for limit operators settings in all filters.
 */
function views_post_update_limit_operator_defaults(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $displays = $view->get('display');

    $update = FALSE;
    foreach ($displays as $display_name => &$display) {
      if (!isset($display['display_options']['filters'])) {
        continue;
      }

      foreach ($display['display_options']['filters'] as $filter_name => $filter) {
        if (!isset($filter['expose']['operator_limit_selection'])) {
          $filter['expose']['operator_limit_selection'] = FALSE;
          $update = TRUE;
        }
        if (!isset($filter['expose']['operator_list'])) {
          $filter['expose']['operator_list'] = [];
          $update = TRUE;
        }
        if ($update) {
          $view->set("display.$display_name.display_options.filters.$filter_name", $filter);
        }
      }
    }
    return $update;
  });
}
