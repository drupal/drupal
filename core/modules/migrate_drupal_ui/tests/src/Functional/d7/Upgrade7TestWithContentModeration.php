<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;

/**
 * Tests Drupal 7 upgrade using the migrate UI with Content Moderation.
 *
 * @group migrate_drupal_ui
 * @group #slow
 */
class Upgrade7TestWithContentModeration extends Upgrade7Test {

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
      'blog',
      'et',
      'test_content_type',
    ];
    foreach ($types as $type) {
      $this->drupalCreateContentType(['type' => $type]);
    }

    $editorial = Workflow::load('editorial');
    assert($editorial instanceof WorkflowInterface);
    $type_settings = $editorial->getTypePlugin()->getConfiguration();
    $type_settings['default_moderation_state'] = 'published';
    $type_settings['entity_types']['node'] = array_merge(
      ['article'],
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
      'content_moderation_state' => 5,
      'workflow' => 1,
    ];
    $entity_counts['entity_view_display'] = $entity_counts['entity_view_display'] + 1;
    $entity_counts['field_config'] = $entity_counts['field_config'] + 2;
    $entity_counts['view'] = $entity_counts['view'] + 1;
    return $entity_counts;
  }

}
