<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the ContentEntityStorageBase::createWithSampleValues method.
 *
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityStorageBase
 * @group Entity
 */
class CreateSampleEntityTest extends KernelTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path_alias', 'system', 'field', 'filter', 'text', 'file', 'user', 'node', 'comment', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('file');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('comment_type');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    Vocabulary::create(['name' => 'Tags', 'vid' => 'tags'])->save();
  }

  /**
   * Tests sample value content entity creation of all types.
   *
   * @covers ::createWithSampleValues
   */
  public function testSampleValueContentEntity() {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      if ($definition->entityClassImplements(FieldableEntityInterface::class)) {
        $label = $definition->getKey('label');
        $values = [];
        if ($label) {
          $title = $this->randomString();
          $values[$label] = $title;
        }
        // Create sample entities with bundles.
        if ($bundle_type = $definition->getBundleEntityType()) {
          foreach ($this->entityTypeManager->getStorage($bundle_type)->loadMultiple() as $bundle) {
            $entity = $this->entityTypeManager->getStorage($entity_type_id)->createWithSampleValues($bundle->id(), $values);
            $violations = $entity->validate();
            $this->assertCount(0, $violations);
            if ($label) {
              $this->assertEquals($title, $entity->label());
            }
          }
        }
        // Create sample entities without bundles.
        else {
          $entity = $this->entityTypeManager->getStorage($entity_type_id)->createWithSampleValues(FALSE, $values);
          $violations = $entity->validate();
          $this->assertCount(0, $violations);
          if ($label) {
            $this->assertEquals($title, $entity->label());
          }
        }
      }
    }
  }

}
