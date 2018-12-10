<?php

/**
 * @file
 * Post update functions for the Content Moderation module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Site\Settings;
use Drupal\views\Entity\View;
use Drupal\workflows\Entity\Workflow;

/**
 * Synchronize moderation state default revisions with their host entities.
 */
function content_moderation_post_update_update_cms_default_revisions(&$sandbox) {
  // For every moderated entity, identify the default revision ID, track the
  // corresponding "content_moderation_state" revision and save it as the new
  // default revision, if needed.

  // Initialize sandbox info.
  $entity_type_id = &$sandbox['entity_type_id'];
  if (!isset($entity_type_id)) {
    $sandbox['bundles'] = [];
    $sandbox['entity_type_ids'] = [];
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    foreach (Workflow::loadMultipleByType('content_moderation') as $workflow) {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $plugin */
      $plugin = $workflow->getTypePlugin();
      foreach ($plugin->getEntityTypes() as $entity_type_id) {
        $sandbox['entity_type_ids'][$entity_type_id] = $entity_type_id;
        foreach ($plugin->getBundlesForEntityType($entity_type_id) as $bundle) {
          $sandbox['bundles'][$entity_type_id][$bundle] = $bundle;
        }
      }
    }
    $sandbox['offset'] = 0;
    $sandbox['limit'] = Settings::get('entity_update_batch_size', 50);
    $sandbox['total'] = count($sandbox['entity_type_ids']);
    $entity_type_id = array_shift($sandbox['entity_type_ids']);
  }

  // If there are no moderated bundles or we processed all of them, we are done.
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $content_moderation_state_storage */
  $content_moderation_state_storage = $entity_type_manager->getStorage('content_moderation_state');
  if (!$entity_type_id) {
    $content_moderation_state_storage->resetCache();
    $sandbox['#finished'] = 1;
    return;
  }

  // Retrieve a batch of moderated entities to be processed.
  $storage = $entity_type_manager->getStorage($entity_type_id);
  $entity_type = $entity_type_manager->getDefinition($entity_type_id);
  $query = $storage->getQuery()
    ->accessCheck(FALSE)
    ->sort($entity_type->getKey('id'))
    ->range($sandbox['offset'], $sandbox['limit']);
  $bundle_key = $entity_type->getKey('bundle');
  if ($bundle_key && !empty($sandbox['bundles'][$entity_type_id])) {
    $bundles = array_keys($sandbox['bundles'][$entity_type_id]);
    $query->condition($bundle_key, $bundles, 'IN');
  }
  $entity_ids = $query->execute();

  // Compute progress status and skip to the next entity type, if needed.
  $sandbox['#finished'] = ($sandbox['total'] - count($sandbox['entity_type_ids']) - 1) / $sandbox['total'];
  if (!$entity_ids) {
    $sandbox['offset'] = 0;
    $entity_type_id = array_shift($sandbox['entity_type_ids']) ?: FALSE;
    return;
  }

  // Load the "content_moderation_state" revisions corresponding to the
  // moderated entity default revisions.
  $result = $content_moderation_state_storage->getQuery()
    ->allRevisions()
    ->condition('content_entity_type_id', $entity_type_id)
    ->condition('content_entity_revision_id', array_keys($entity_ids), 'IN')
    ->execute();
  /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
  $revisions = $content_moderation_state_storage->loadMultipleRevisions(array_keys($result));

  // Update "content_moderation_state" data.
  foreach ($revisions as $revision) {
    if (!$revision->isDefaultRevision()) {
      $revision->setNewRevision(FALSE);
      $revision->isDefaultRevision(TRUE);
      $content_moderation_state_storage->save($revision);
    }
  }

  // Clear static cache to avoid memory issues.
  $storage->resetCache($entity_ids);

  $sandbox['offset'] += $sandbox['limit'];
}

/**
 * Set the default moderation state for new content to 'draft'.
 */
function content_moderation_post_update_set_default_moderation_state(&$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'workflow', function (Workflow $workflow) {
    if ($workflow->get('type') === 'content_moderation') {
      $configuration = $workflow->getTypePlugin()->getConfiguration();
      $configuration['default_moderation_state'] = 'draft';
      $workflow->getTypePlugin()->setConfiguration($configuration);
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * Set the filter on the moderation view to be the latest translation affected.
 */
function content_moderation_post_update_set_views_filter_latest_translation_affected_revision(&$sandbox) {
  $original_plugin_name = 'latest_revision';
  $new_plugin_name = 'latest_translation_affected_revision';

  // Check that views is installed and the moderated content view exists.
  if (\Drupal::moduleHandler()->moduleExists('views') && $view = View::load('moderated_content')) {
    $display = &$view->getDisplay('default');
    if (!isset($display['display_options']['filters'][$original_plugin_name])) {
      return;
    }

    $translation_affected_filter = [
      'id' => $new_plugin_name,
      'field' => $new_plugin_name,
      'plugin_id' => $new_plugin_name,
    ] + $display['display_options']['filters'][$original_plugin_name];

    $display['display_options']['filters'] = [$new_plugin_name => $translation_affected_filter] + $display['display_options']['filters'];
    unset($display['display_options']['filters'][$original_plugin_name]);

    $view->save();
  }
}

/**
 * Update the dependencies of entity displays to include associated workflow.
 */
function content_moderation_post_update_entity_display_dependencies(&$sandbox) {
  /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
  $moderation_info = \Drupal::service('content_moderation.moderation_information');
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::service('entity_type.manager');

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_form_display', function (EntityFormDisplay $entity_form_display) use ($moderation_info, $entity_type_manager) {
    $associated_entity_type = $entity_type_manager->getDefinition($entity_form_display->getTargetEntityTypeId());

    if ($moderation_info->isModeratedEntityType($associated_entity_type)) {
      $entity_form_display->calculateDependencies();
      return TRUE;
    }
    elseif ($moderation_state_component = $entity_form_display->getComponent('moderation_state')) {
      // Remove the component from the entity form display, then manually delete
      // it from the hidden components list, completely purging it.
      $entity_form_display->removeComponent('moderation_state');
      $hidden_components = $entity_form_display->get('hidden');
      unset($hidden_components['moderation_state']);
      $entity_form_display->set('hidden', $hidden_components);
      $entity_form_display->calculateDependencies();
      return TRUE;
    }

    return FALSE;
  });
}
