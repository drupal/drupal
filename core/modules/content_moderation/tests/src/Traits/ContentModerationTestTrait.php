<?php

namespace Drupal\Tests\content_moderation\Traits;

use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;

/**
 * Trait ContentModerationTestTraint.
 */
trait ContentModerationTestTrait {

  /**
   * Creates the editorial workflow.
   *
   * @return \Drupal\workflows\Entity\Workflow
   *   The editorial workflow entity.
   */
  protected function createEditorialWorkflow() {
    $workflow = Workflow::create([
      'type' => 'content_moderation',
      'id' => 'editorial',
      'label' => 'Editorial',
      'type_settings' => [
        'states' => [
          'archived' => [
            'label' => 'Archived',
            'weight' => 5,
            'published' => FALSE,
            'default_revision' => TRUE,
          ],
          'draft' => [
            'label' => 'Draft',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -5,
          ],
          'published' => [
            'label' => 'Published',
            'published' => TRUE,
            'default_revision' => TRUE,
            'weight' => 0,
          ],
        ],
        'transitions' => [
          'archive' => [
            'label' => 'Archive',
            'from' => ['published'],
            'to' => 'archived',
            'weight' => 2,
          ],
          'archived_draft' => [
            'label' => 'Restore to Draft',
            'from' => ['archived'],
            'to' => 'draft',
            'weight' => 3,
          ],
          'archived_published' => [
            'label' => 'Restore',
            'from' => ['archived'],
            'to' => 'published',
            'weight' => 4,
          ],
          'create_new_draft' => [
            'label' => 'Create New Draft',
            'to' => 'draft',
            'weight' => 0,
            'from' => [
              'draft',
              'published',
            ],
          ],
          'publish' => [
            'label' => 'Publish',
            'to' => 'published',
            'weight' => 1,
            'from' => [
              'draft',
              'published',
            ],
          ],
        ],
      ],
    ]);
    $workflow->save();
    return $workflow;
  }

  /**
   * Adds an entity type ID / bundle ID to the given workflow.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   A workflow object.
   * @param string $entity_type_id
   *   The entity type ID to add.
   * @param string $bundle
   *   The bundle ID to add.
   */
  protected function addEntityTypeAndBundleToWorkflow(WorkflowInterface $workflow, $entity_type_id, $bundle) {
    $workflow->getTypePlugin()->addEntityTypeAndBundle($entity_type_id, $bundle);
    $workflow->save();
  }

}
