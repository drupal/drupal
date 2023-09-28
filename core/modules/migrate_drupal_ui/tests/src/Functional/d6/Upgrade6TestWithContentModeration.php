<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;

/**
 * Tests Drupal 6 upgrade using the migrate UI with Content Moderation.
 *
 * @group migrate_drupal_ui
 * @group #slow
 */
class Upgrade6TestWithContentModeration extends Upgrade6Test {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a moderation flow.
    $types = [
      'story',
      'test_planet',
      'company',
      'employee',
    ];
    foreach ($types as $type) {
      $this->drupalCreateContentType(['type' => $type]);
    }
    $editorial = Workflow::load('editorial');
    assert($editorial instanceof WorkflowInterface);
    $type_settings = $editorial->getTypePlugin()->getConfiguration();
    $type_settings['default_moderation_state'] = 'published';
    $type_settings['entity_types']['node'] = array_merge(
      ['page'],
      $types
    );
    $type_plugin = $editorial->getTypePlugin();
    $type_plugin->setConfiguration($type_settings);
    $editorial->trustData()->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    $entity_counts = parent::getEntityCounts() + [
      'content_moderation_state' => 17,
      'workflow' => 1,
    ];
    $entity_counts['field_config'] = $entity_counts['field_config'] + 1;
    $entity_counts['view'] = $entity_counts['view'] + 1;
    return $entity_counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $entity_counts_incremental = parent::getEntityCountsIncremental();
    $entity_counts_incremental['content_moderation_state'] = $entity_counts_incremental['content_moderation_state'] + 1;
    return $entity_counts_incremental;
  }

}
