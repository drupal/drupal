<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests links between a content entity and a content_moderation_state entity.
 *
 * @group content_moderation
 */
class ContentModerationStateTest extends KernelTestBase {

  use ContentModerationTestTrait;
  use EntityDefinitionTestTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'node',
    'block',
    'block_content',
    'media',
    'media_test_source',
    'image',
    'file',
    'field',
    'filter',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
    'text',
    'workflows',
    'path_alias',
    'taxonomy',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The ID of the revisionable entity type used in the tests.
   *
   * @var string
   */
  protected $revEntityTypeId = 'entity_test_rev';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema($this->revEntityTypeId);
    $this->installEntitySchema('entity_test_no_bundle');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
    $this->installSchema('file', 'file_usage');
    $this->installConfig(['field', 'file', 'filter', 'image', 'media', 'node', 'system']);

    // Add the French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests basic monolingual content moderation through the API.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testBasicModeration($entity_type_id) {
    $entity = $this->createEntity($entity_type_id, 'draft');
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

    // Check the default revision.
    $this->assertDefaultRevision($entity, 2);

    $entity->moderation_state->value = 'published';
    $entity->save();

    $entity = $this->reloadEntity($entity, 4);
    $this->assertEquals('published', $entity->moderation_state->value);

    // Check the default revision.
    $this->assertDefaultRevision($entity, 4);

    // Update the node to archived which will then be the default revision.
    $entity->moderation_state->value = 'archived';
    $entity->save();

    // Revert to the previous (published) revision.
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $previous_revision = $entity_storage->loadRevision(4);
    $previous_revision->isDefaultRevision(TRUE);
    $previous_revision->setNewRevision(TRUE);
    $previous_revision->save();

    // Check the default revision.
    $this->assertDefaultRevision($entity, 6);

    // Set an invalid moderation state.
    $this->expectException(EntityStorageException::class);
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
      'Media' => [
        'media',
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
   * Tests removal of content moderation state entity.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testContentModerationStateDataRemoval($entity_type_id) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->createEntity($entity_type_id);
    $entity = $this->reloadEntity($entity);
    $entity->delete();
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertNull($content_moderation_state);
  }

  /**
   * Tests removal of content moderation state entity revisions.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testContentModerationStateRevisionDataRemoval($entity_type_id) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->createEntity($entity_type_id);
    $revision_1 = clone $entity;
    $this->assertNotNull(ContentModerationState::loadFromModeratedEntity($revision_1));

    // Create a second revision.
    $entity = $this->reloadEntity($entity);
    $entity->setNewRevision(TRUE);
    $entity->save();
    $revision_2 = clone $entity;

    // Create a third revision.
    $entity = $this->reloadEntity($entity);
    $entity->setNewRevision(TRUE);
    $entity->save();
    $revision_3 = clone $entity;

    // Delete the second revision and check that its content moderation state is
    // removed as well, while the content moderation states for revisions 1 and
    // 3 are kept in place.
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_storage->deleteRevision($revision_2->getRevisionId());

    $this->assertNotNull(ContentModerationState::loadFromModeratedEntity($revision_1));
    $this->assertNull(ContentModerationState::loadFromModeratedEntity($revision_2));
    $this->assertNotNull(ContentModerationState::loadFromModeratedEntity($revision_3));
  }

  /**
   * Tests removal of content moderation state pending entity revisions.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testContentModerationStatePendingRevisionDataRemoval($entity_type_id) {
    $entity = $this->createEntity($entity_type_id, 'published');
    $entity->setNewRevision(TRUE);
    $entity->moderation_state = 'draft';
    $entity->save();

    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertNotEmpty($content_moderation_state);

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_storage->deleteRevision($entity->getRevisionId());

    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertNull($content_moderation_state);
  }

  /**
   * Tests removal of content moderation state entities for preexisting content.
   */
  public function testExistingContentModerationStateDataRemoval() {
    $storage = $this->entityTypeManager->getStorage('entity_test_mulrevpub');

    $entity = $this->createEntity('entity_test_mulrevpub', 'published', FALSE);
    $original_revision_id = $entity->getRevisionId();

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, $entity->getEntityTypeId(), $entity->bundle());

    $entity = $this->reloadEntity($entity);
    $entity->moderation_state = 'draft';
    $entity->save();

    $storage->deleteRevision($entity->getRevisionId());

    $entity = $this->reloadEntity($entity);
    $this->assertEquals('published', $entity->moderation_state->value);
    $this->assertEquals($original_revision_id, $storage->getLatestRevisionId($entity->id()));
  }

  /**
   * Tests removal of content moderation state translations.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testContentModerationStateTranslationDataRemoval($entity_type_id) {
    // Test content moderation state translation deletion.
    if ($this->entityTypeManager->getDefinition($entity_type_id)->isTranslatable()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->createEntity($entity_type_id, 'published');
      $langcode = 'fr';
      $translation = $entity->addTranslation($langcode, ['title' => 'French title test']);
      // Make sure we add values for all of the required fields.
      if ($entity_type_id == 'block_content') {
        $translation->info = $this->randomString();
      }
      $translation->save();
      $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
      $this->assertTrue($content_moderation_state->hasTranslation($langcode));
      $entity->removeTranslation($langcode);
      $entity->save();
      $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
      $this->assertFalse($content_moderation_state->hasTranslation($langcode));
    }
  }

  /**
   * Tests basic multilingual content moderation through the API.
   */
  public function testMultilingualModeration() {
    $this->createContentType([
      'type' => 'example',
    ]);

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'example');

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
    // Revision 2 (fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);
    $this->assertFalse($french_node->isPublished());

    // Move English node to create another draft.
    $english_node = $this->reloadEntity($english_node);
    $english_node->moderation_state->value = 'draft';
    // Revision 3 (en, fr).
    $english_node->save();
    $english_node = $this->reloadEntity($english_node);
    $this->assertEquals('draft', $english_node->moderation_state->value);

    // French node should still be in draft.
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);

    // Publish the French node.
    $french_node->moderation_state->value = 'published';
    // Revision 4 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($french_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('published', $french_node->moderation_state->value);
    $this->assertTrue($french_node->isPublished());
    $english_node = $french_node->getTranslation('en');
    $this->assertEquals('draft', $english_node->moderation_state->value);

    // Publish the English node.
    $english_node->moderation_state->value = 'published';
    // Revision 5 (en, fr).
    $english_node->save();
    $english_node = $this->reloadEntity($english_node);
    $this->assertTrue($english_node->isPublished());

    // Move the French node back to draft.
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $french_node->moderation_state->value = 'draft';
    // Revision 6 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node, 6)->getTranslation('fr');
    $this->assertFalse($french_node->isPublished());
    $this->assertTrue($french_node->getTranslation('en')->isPublished());

    // Republish the French node.
    $french_node->moderation_state->value = 'published';
    // Revision 7 (en, fr).
    $french_node->save();
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());

    // Change the EN state without saving the node.
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($english_node);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 8 (en, fr).
    $content_moderation_state->save();
    $english_node = $this->reloadEntity($french_node, $french_node->getRevisionId() + 1);

    $this->assertEquals('draft', $english_node->moderation_state->value);
    $french_node = $this->reloadEntity($english_node)->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->value);

    // This should unpublish the French node.
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($english_node);
    $content_moderation_state = $content_moderation_state->getTranslation('fr');
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 9 (en, fr).
    $content_moderation_state->save();

    $english_node = $this->reloadEntity($english_node, $english_node->getRevisionId());
    $this->assertEquals('draft', $english_node->moderation_state->value);
    $french_node = $this->reloadEntity($english_node, '9')->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->value);
    // Switching the moderation state to an unpublished state should update the
    // entity.
    $this->assertFalse($french_node->isPublished());

    // Check that revision 7 is still the default one for the node.
    $this->assertDefaultRevision($english_node, 7);
  }

  /**
   * Tests moderation when the moderation_state field has a config override.
   */
  public function testModerationWithFieldConfigOverride() {
    $this->createContentType([
      'type' => 'test_type',
    ]);

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'test_type');

    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'test_type');
    $field_config = $fields['moderation_state']->getConfig('test_type');
    $field_config->setLabel('Field Override!');
    $field_config->save();

    $node = Node::create([
      'title' => 'Test node',
      'type' => 'test_type',
    ]);
    $node->save();
    $this->assertFalse($node->isPublished());
    $this->assertEquals('draft', $node->moderation_state->value);

    $node->moderation_state = 'published';
    $node->save();
    $this->assertTrue($node->isPublished());
    $this->assertEquals('published', $node->moderation_state->value);
  }

  /**
   * Tests that entities with special languages can be moderated.
   *
   * @dataProvider moderationWithSpecialLanguagesTestCases
   */
  public function testModerationWithSpecialLanguages($original_language, $updated_language) {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, $this->revEntityTypeId, $this->revEntityTypeId);

    // Create a test entity.
    $storage = $this->entityTypeManager->getStorage($this->revEntityTypeId);
    $entity = $storage->create([
      'langcode' => $original_language,
    ]);
    $entity->save();
    $this->assertEquals('draft', $entity->moderation_state->value);

    $entity->moderation_state->value = 'published';
    $entity->langcode = $updated_language;
    $entity->save();

    $this->assertEquals('published', $storage->load($entity->id())->moderation_state->value);
  }

  /**
   * Test cases for ::testModerationWithSpecialLanguages().
   */
  public function moderationWithSpecialLanguagesTestCases() {
    return [
      'Not specified to not specified' => [
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ],
      'English to not specified' => [
        'en',
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ],
      'Not specified to english' => [
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'en',
      ],
    ];
  }

  /**
   * Tests changing the language of content without adding a translation.
   */
  public function testChangingContentLangcode() {
    $this->createContentType([
      'type' => 'test_type',
    ]);
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'test_type');

    $entity = Node::create([
      'title' => 'Test node',
      'langcode' => 'en',
      'type' => 'test_type',
    ]);
    $entity->save();

    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertCount(1, $entity->getTranslationLanguages());
    $this->assertCount(1, $content_moderation_state->getTranslationLanguages());
    $this->assertEquals('en', $entity->langcode->value);
    $this->assertEquals('en', $content_moderation_state->langcode->value);

    $entity->langcode = 'fr';
    $entity->save();

    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertCount(1, $entity->getTranslationLanguages());
    $this->assertCount(1, $content_moderation_state->getTranslationLanguages());
    $this->assertEquals('fr', $entity->langcode->value);
    $this->assertEquals('fr', $content_moderation_state->langcode->value);
  }

  /**
   * Tests that a non-translatable entity type with a langcode can be moderated.
   */
  public function testNonTranslatableEntityTypeModeration() {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, $this->revEntityTypeId, $this->revEntityTypeId);

    // Check that the tested entity type is not translatable.
    $entity_type = $this->entityTypeManager->getDefinition($this->revEntityTypeId);
    $this->assertFalse($entity_type->isTranslatable(), 'The test entity type is not translatable.');

    // Create a test entity.
    $storage = $this->entityTypeManager->getStorage($this->revEntityTypeId);
    $entity = $storage->create();
    $entity->save();
    $this->assertEquals('draft', $entity->moderation_state->value);

    $entity->moderation_state->value = 'published';
    $entity->save();

    $this->assertEquals('published', $storage->load($entity->id())->moderation_state->value);
  }

  /**
   * Tests that a non-translatable entity type without a langcode can be
   * moderated.
   */
  public function testNonLangcodeEntityTypeModeration() {
    // Unset the langcode entity key for 'entity_test_rev'.
    $entity_type = clone $this->entityTypeManager->getDefinition($this->revEntityTypeId);
    $keys = $entity_type->getKeys();
    unset($keys['langcode']);
    $entity_type->set('entity_keys', $keys);
    \Drupal::state()->set($this->revEntityTypeId . '.entity_type', $entity_type);

    // Update the entity type in order to remove the 'langcode' field.
    \Drupal::entityDefinitionUpdateManager()->updateFieldableEntityType($entity_type, \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type->id()));

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, $this->revEntityTypeId, $this->revEntityTypeId);

    // Check that the tested entity type is not translatable and does not have a
    // 'langcode' entity key.
    $entity_type = $this->entityTypeManager->getDefinition($this->revEntityTypeId);
    $this->assertFalse($entity_type->isTranslatable(), 'The test entity type is not translatable.');
    $this->assertFalse($entity_type->getKey('langcode'), "The test entity type does not have a 'langcode' entity key.");

    // Create a test entity.
    $storage = $this->entityTypeManager->getStorage($this->revEntityTypeId);
    $entity = $storage->create();
    $entity->save();
    $this->assertEquals('draft', $entity->moderation_state->value);

    $entity->moderation_state->value = 'published';
    $entity->save();

    $this->assertEquals('published', $storage->load($entity->id())->moderation_state->value);
  }

  /**
   * Tests the dependencies of the workflow when using content moderation.
   */
  public function testWorkflowDependencies() {
    $node_type = $this->createContentType([
      'type' => 'example',
    ]);

    $workflow = $this->createEditorialWorkflow();
    // Test both a config and non-config based bundle and entity type.
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'example');
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'entity_test_rev', 'entity_test_rev');
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'entity_test_no_bundle', 'entity_test_no_bundle');

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
      'node',
    ], $workflow->getTypePlugin()->getEntityTypes());

    // Delete the node type and ensure it is removed from the workflow.
    $node_type->delete();
    $workflow = Workflow::load('editorial');
    $entity_types = $workflow->getTypePlugin()->getEntityTypes();
    $this->assertNotContains('node', $entity_types);

    // Uninstall entity test and ensure it's removed from the workflow.
    $this->container->get('config.manager')->uninstall('module', 'entity_test');
    $workflow = Workflow::load('editorial');
    $entity_types = $workflow->getTypePlugin()->getEntityTypes();
    $this->assertEquals([], $entity_types);
  }

  /**
   * Tests the content moderation workflow dependencies for non-config bundles.
   */
  public function testWorkflowNonConfigBundleDependencies() {
    // Create a bundle not based on any particular configuration.
    entity_test_create_bundle('test_bundle');

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test', 'test_bundle');
    $workflow->save();

    // Ensure the bundle is correctly added to the workflow.
    $this->assertEquals([
      'module' => [
        'content_moderation',
        'entity_test',
      ],
    ], $workflow->getDependencies());
    $this->assertEquals([
      'test_bundle',
    ], $workflow->getTypePlugin()->getBundlesForEntityType('entity_test'));

    // Delete the test bundle to ensure the workflow entity responds
    // appropriately.
    entity_test_delete_bundle('test_bundle');

    $workflow = Workflow::load('editorial');
    $this->assertEquals([], $workflow->getTypePlugin()->getBundlesForEntityType('entity_test'));
    $this->assertEquals([
      'module' => [
        'content_moderation',
      ],
    ], $workflow->getDependencies());
  }

  /**
   * Tests the revision default state of the moderation state entity revisions.
   *
   * @param string $entity_type_id
   *   The ID of entity type to be tested.
   *
   * @dataProvider basicModerationTestCases
   */
  public function testRevisionDefaultState($entity_type_id) {
    // Check that the revision default state of the moderated entity and the
    // content moderation state entity always match.
    $entity = $this->createEntity($entity_type_id, 'published');
    $cms_entity = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertEquals($entity->isDefaultRevision(), $cms_entity->isDefaultRevision());

    $entity->get('moderation_state')->value = 'published';
    $entity->save();
    $cms_entity = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertEquals($entity->isDefaultRevision(), $cms_entity->isDefaultRevision());

    $entity->get('moderation_state')->value = 'draft';
    $entity->save();
    $cms_entity = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertEquals($entity->isDefaultRevision(), $cms_entity->isDefaultRevision());

    $entity->get('moderation_state')->value = 'published';
    $entity->save();
    $cms_entity = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertEquals($entity->isDefaultRevision(), $cms_entity->isDefaultRevision());
  }

  /**
   * Creates an entity.
   *
   * The entity will have required fields populated and the corresponding bundle
   * will be enabled for content moderation.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $moderation_state
   *   (optional) The initial moderation state of the newly created entity.
   *   Defaults to 'published'.
   * @param bool $create_workflow
   *   (optional) Whether to create an editorial workflow and configure it for
   *   the given entity type. Defaults to TRUE.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The created entity.
   */
  protected function createEntity($entity_type_id, $moderation_state = 'published', $create_workflow = TRUE) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $bundle_id = $entity_type_id;
    // Set up a bundle entity type for the specified entity type, if needed.
    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
      $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);

      $bundle_id = 'example';
      if (!$bundle_entity_storage->load($bundle_id)) {
        $bundle_entity = $bundle_entity_storage->create([
          $bundle_entity_type->getKey('id') => 'example',
        ]);
        if ($entity_type_id == 'media') {
          $bundle_entity->set('source', 'test');
          $bundle_entity->save();
          $source_field = $bundle_entity->getSource()->createSourceField($bundle_entity);
          $source_field->getFieldStorageDefinition()->save();
          $source_field->save();
          $bundle_entity->set('source_configuration', [
            'source_field' => $source_field->getName(),
          ]);
        }
        $bundle_entity->save();
      }
    }

    if ($create_workflow) {
      $workflow = $this->createEditorialWorkflow();
      $workflow->getTypePlugin()->addEntityTypeAndBundle($entity_type_id, $bundle_id);
      $workflow->save();
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $entity_storage->create([
      $entity_type->getKey('label') => 'Test title',
      $entity_type->getKey('bundle') => $bundle_id,
      'moderation_state' => $moderation_state,
    ]);
    // Make sure we add values for all of the required fields.
    if ($entity_type_id == 'block_content') {
      $entity->info = $this->randomString();
    }

    $entity->save();
    return $entity;
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

  /**
   * Checks the default revision ID and publishing status for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   * @param int $revision_id
   *   The expected revision ID.
   * @param bool|null $published
   *   (optional) Whether to check if the entity is published or not. Defaults
   *   to TRUE.
   */
  protected function assertDefaultRevision(EntityInterface $entity, $revision_id, $published = TRUE) {
    // Get the default revision.
    $entity = $this->reloadEntity($entity);
    $this->assertEquals($revision_id, $entity->getRevisionId());

    if ($published !== NULL && $entity instanceof EntityPublishedInterface) {
      $this->assertSame($published, $entity->isPublished());
    }
  }

  /**
   * Tests that the 'taxonomy_term' entity type cannot be moderated.
   */
  public function testTaxonomyTermEntityTypeModeration() {
    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $entity_type = \Drupal::entityTypeManager()->getDefinition('taxonomy_term');
    $this->assertFalse($moderation_info->canModerateEntitiesOfEntityType($entity_type));
  }

}
