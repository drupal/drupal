<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\CommentType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that comment bundles behave as expected.
 *
 * @group comment
 */
class CommentBundlesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node', 'taxonomy', 'user'];

  /**
   * Entity type ids to use for target_entity_type_id on comment bundles.
   *
   * @var array
   */
  protected $targetEntityTypes;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityFieldManager = $this->container->get('entity_field.manager');

    $this->installEntitySchema('comment');

    // Create multiple comment bundles,
    // each of which has a different target entity type.
    $this->targetEntityTypes = [
      'comment' => 'Comment',
      'node' => 'Node',
      'taxonomy_term' => 'Taxonomy Term',
    ];
    foreach ($this->targetEntityTypes as $id => $label) {
      CommentType::create([
        'id' => 'comment_on_' . $id,
        'label' => 'Comment on ' . $label,
        'target_entity_type_id' => $id,
      ])->save();
    }
  }

  /**
   * Tests that the entity_id field is set correctly for each comment bundle.
   */
  public function testEntityIdField() {
    $field_definitions = [];

    foreach (array_keys($this->targetEntityTypes) as $id) {
      $bundle = 'comment_on_' . $id;
      $field_definitions[$bundle] = $this->entityFieldManager
        ->getFieldDefinitions('comment', $bundle);
    }
    // Test that the value of the entity_id field for each bundle is correct.
    foreach ($field_definitions as $bundle => $definition) {
      $entity_type_id = str_replace('comment_on_', '', $bundle);
      $target_type = $definition['entity_id']->getSetting('target_type');
      $this->assertEquals($entity_type_id, $target_type);

      // Verify that the target type remains correct
      // in the deeply-nested object properties.
      $nested_target_type = $definition['entity_id']->getItemDefinition()->getFieldDefinition()->getSetting('target_type');
      $this->assertEquals($entity_type_id, $nested_target_type);
    }

  }

}
