<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests links between a content entity and a content_moderation_state entity.
 *
 * @group content_moderation
 */
class ContentModerationStateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'node',
    'block_content',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
    'text',
    'workflows',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_no_bundle');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests basic monolingual content moderation through the API.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testBasicModeration($entity_type_id) {
    // Make the 'entity_test_with_bundle' entity type revisionable.
    if ($entity_type_id == 'entity_test_with_bundle') {
      $this->setEntityTestWithBundleKeys(['revision' => 'revision_id']);
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $bundle_id = $entity_type_id;
    $bundle_entity_type_id = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();
    if ($bundle_entity_type_id) {
      $bundle_entity_type_definition = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
      $entity_type_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);

      $entity_type = $entity_type_storage->create([
        $bundle_entity_type_definition->getKey('id') => 'example',
      ]);
      $entity_type->save();
      $bundle_id = $entity_type->id();
    }

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle($entity_type_id, $bundle_id);
    $workflow->save();

    $entity = $entity_storage->create([
      'title' => 'Test title',
      $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle') => $bundle_id,
    ]);
    if ($entity instanceof EntityPublishedInterface) {
      $entity->setUnpublished();
    }
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertEquals('draft', $entity->moderation_state->value);

    $entity->moderation_state->value = 'published';
    $entity->save();

    $entity = $this->reloadEntity($entity);
    $this->assertEquals('published', $entity->moderation_state->value);

    // Change the state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    $content_moderation_state->save();

    $entity = $this->reloadEntity($entity, 3);
    $this->assertEquals('draft', $entity->moderation_state->value);
    if ($entity instanceof EntityPublishedInterface) {
      $this->assertFalse($entity->isPublished());
    }

    // Get the default revision.
    $entity = $this->reloadEntity($entity);
    if ($entity instanceof EntityPublishedInterface) {
      $this->assertTrue((bool) $entity->isPublished());
    }
    $this->assertEquals(2, $entity->getRevisionId());

    $entity->moderation_state->value = 'published';
    $entity->save();

    $entity = $this->reloadEntity($entity, 4);
    $this->assertEquals('published', $entity->moderation_state->value);

    // Get the default revision.
    $entity = $this->reloadEntity($entity);
    if ($entity instanceof EntityPublishedInterface) {
      $this->assertTrue((bool) $entity->isPublished());
    }
    $this->assertEquals(4, $entity->getRevisionId());

    // Update the node to archived which will then be the default revision.
    $entity->moderation_state->value = 'archived';
    $entity->save();

    // Revert to the previous (published) revision.
    $previous_revision = $entity_storage->loadRevision(4);
    $previous_revision->isDefaultRevision(TRUE);
    $previous_revision->setNewRevision(TRUE);
    $previous_revision->save();

    // Get the default revision.
    $entity = $this->reloadEntity($entity);
    $this->assertEquals('published', $entity->moderation_state->value);
    if ($entity instanceof EntityPublishedInterface) {
      $this->assertTrue($entity->isPublished());
    }

    // Set an invalid moderation state.
    $this->setExpectedException(EntityStorageException::class);
    $entity->moderation_state->value = 'foobar';
    $entity->save();
  }

  /**
   * Test cases for basic moderation test.
   */
  public function basicModerationTestCases() {
    return [
      'Nodes' => [
        'node',
      ],
      'Block content' => [
        'block_content',
      ],
      'Test Entity with Bundle' => [
        'entity_test_with_bundle',
      ],
      'Test entity - revisions, data table, and published interface' => [
        'entity_test_mulrevpub',
      ],
      'Entity Test with revisions' => [
        'entity_test_rev',
      ],
      'Entity without bundle' => [
        'entity_test_no_bundle',
      ],
    ];
  }

  /**
   * Tests basic multilingual content moderation through the API.
   */
  public function testMultilingualModeration() {
    // Enable French.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $english_node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    // Revision 1 (en).
    $english_node
      ->setUnpublished()
      ->save();
    $this->assertEquals('draft', $english_node->moderation_state->value);
    $this->assertFalse($english_node->isPublished());

    // Create a French translation.
    $french_node = $english_node->addTranslation('fr', ['title' => 'French title']);
    $french_node->setUnpublished();
    // Revision 1 (fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);
    $this->assertFalse($french_node->isPublished());

    // Move English node to create another draft.
    $english_node = $this->reloadEntity($english_node);
    $english_node->moderation_state->value = 'draft';
    // Revision 2 (en, fr).
    $english_node->save();
    $english_node = $this->reloadEntity($english_node);
    $this->assertEquals('draft', $english_node->moderation_state->value);

    // French node should still be in draft.
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);

    // Publish the French node.
    $french_node->moderation_state->value = 'published';
    // Revision 3 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($french_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('published', $french_node->moderation_state->value);
    $this->assertTrue($french_node->isPublished());
    $english_node = $french_node->getTranslation('en');
    $this->assertEquals('draft', $english_node->moderation_state->value);

    // Publish the English node.
    $english_node->moderation_state->value = 'published';
    // Revision 4 (en, fr).
    $english_node->save();
    $english_node = $this->reloadEntity($english_node);
    $this->assertTrue($english_node->isPublished());

    // Move the French node back to draft.
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $french_node->moderation_state->value = 'draft';
    // Revision 5 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node, 5)->getTranslation('fr');
    $this->assertFalse($french_node->isPublished());
    $this->assertTrue($french_node->getTranslation('en')->isPublished());

    // Republish the French node.
    $french_node->moderation_state->value = 'published';
    // Revision 6 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());

    // Change the EN state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 7 (en, fr).
    $content_moderation_state->save();
    $english_node = $this->reloadEntity($french_node, $french_node->getRevisionId() + 1);

    $this->assertEquals('draft', $english_node->moderation_state->value);
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->value);

    // This should unpublish the French node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state = $content_moderation_state->getTranslation('fr');
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 8 (en, fr).
    $content_moderation_state->save();

    $english_node = $this->reloadEntity($english_node, $english_node->getRevisionId());
    $this->assertEquals('draft', $english_node->moderation_state->value);
    $french_node = $this->reloadEntity($english_node, '8')->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);
    // Switching the moderation state to an unpublished state should update the
    // entity.
    $this->assertFalse($french_node->isPublished());

    // Get the default english node.
    $english_node = $this->reloadEntity($english_node);
    $this->assertTrue($english_node->isPublished());
    $this->assertEquals(6, $english_node->getRevisionId());
  }

  /**
   * Tests that a non-translatable entity type with a langcode can be moderated.
   */
  public function testNonTranslatableEntityTypeModeration() {
    // Make the 'entity_test_with_bundle' entity type revisionable.
    $this->setEntityTestWithBundleKeys(['revision' => 'revision_id']);

    // Create a test bundle.
    $entity_test_bundle = EntityTestBundle::create([
      'id' => 'example',
    ]);
    $entity_test_bundle->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_with_bundle', 'example');
    $workflow->save();

    // Check that the tested entity type is not translatable.
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_with_bundle');
    $this->assertFalse($entity_type->isTranslatable(), 'The test entity type is not translatable.');

    // Create a test entity.
    $entity_test_with_bundle = EntityTestWithBundle::create([
      'type' => 'example'
    ]);
    $entity_test_with_bundle->save();
    $this->assertEquals('draft', $entity_test_with_bundle->moderation_state->value);

    $entity_test_with_bundle->moderation_state->value = 'published';
    $entity_test_with_bundle->save();

    $this->assertEquals('published', EntityTestWithBundle::load($entity_test_with_bundle->id())->moderation_state->value);
  }

  /**
   * Tests that a non-translatable entity type without a langcode can be
   * moderated.
   */
  public function testNonLangcodeEntityTypeModeration() {
    // Make the 'entity_test_with_bundle' entity type revisionable and unset
    // the langcode entity key.
    $this->setEntityTestWithBundleKeys(['revision' => 'revision_id'], ['langcode']);

    // Create a test bundle.
    $entity_test_bundle = EntityTestBundle::create([
      'id' => 'example',
    ]);
    $entity_test_bundle->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_with_bundle', 'example');
    $workflow->save();

    // Check that the tested entity type is not translatable.
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_with_bundle');
    $this->assertFalse($entity_type->isTranslatable(), 'The test entity type is not translatable.');

    // Create a test entity.
    $entity_test_with_bundle = EntityTestWithBundle::create([
      'type' => 'example'
    ]);
    $entity_test_with_bundle->save();
    $this->assertEquals('draft', $entity_test_with_bundle->moderation_state->value);

    $entity_test_with_bundle->moderation_state->value = 'published';
    $entity_test_with_bundle->save();

    $this->assertEquals('published', EntityTestWithBundle::load($entity_test_with_bundle->id())->moderation_state->value);
  }

  /**
   * Set the keys on the test entity type.
   *
   * @param array $keys
   *   The entity keys to override
   * @param array $remove_keys
   *   Keys to remove.
   */
  protected function setEntityTestWithBundleKeys($keys, $remove_keys = []) {
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_with_bundle');
    $original_keys = $entity_type->getKeys();
    foreach ($remove_keys as $remove_key) {
      unset($original_keys[$remove_key]);
    }
    $entity_type->set('entity_keys', $keys + $original_keys);
    \Drupal::state()->set('entity_test_with_bundle.entity_type', $entity_type);
    \Drupal::entityDefinitionUpdateManager()->applyUpdates();
  }

  /**
   * Tests the dependencies of the workflow when using content moderation.
   */
  public function testWorkflowDependencies() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();

    $workflow = Workflow::load('editorial');
    // Test both a config and non-config based bundle and entity type.
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_no_bundle', 'entity_test_no_bundle');
    $workflow->save();

    $this->assertEquals([
      'module' => [
        'content_moderation',
        'entity_test',
      ],
      'config' => [
        'node.type.example',
      ],
    ], $workflow->getDependencies());

    $this->assertEquals([
      'entity_test_no_bundle',
      'entity_test_rev',
      'node'
    ], $workflow->getTypePlugin()->getEntityTypes());

    // Delete the node type and ensure it is removed from the workflow.
    $node_type->delete();
    $workflow = Workflow::load('editorial');
    $entity_types = $workflow->getTypePlugin()->getEntityTypes();
    $this->assertFalse(in_array('node', $entity_types));

    // Uninstall entity test and ensure it's removed from the workflow.
    $this->container->get('config.manager')->uninstall('module', 'entity_test');
    $workflow = Workflow::load('editorial');
    $entity_types = $workflow->getTypePlugin()->getEntityTypes();
    $this->assertEquals([], $entity_types);
  }

  /**
   * Reloads the entity after clearing the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   * @param int|bool $revision_id
   *   The specific revision ID to load. Defaults FALSE and just loads the
   *   default revision.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity, $revision_id = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    if ($revision_id) {
      return $storage->loadRevision($revision_id);
    }
    return $storage->load($entity->id());
  }

}
