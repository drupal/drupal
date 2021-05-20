<?php

namespace Drupal\Tests\field\Kernel\EntityReference;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestStringId;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the new entity API for the entity reference field type.
 *
 * @group entity_reference
 */
class EntityReferenceItemTest extends FieldKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'comment',
    'file',
    'taxonomy',
    'text',
    'filter',
    'views',
    'field',
  ];

  /**
   * The taxonomy vocabulary to test with.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * The test entity with a string ID.
   *
   * @var \Drupal\entity_test\Entity\EntityTestStringId
   */
  protected $entityStringId;

  /**
   * Sets up the test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_string_id');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');

    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);

    $this->vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->vocabulary->save();

    $this->term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->term->save();

    NodeType::create([
      'type' => $this->randomMachineName(),
    ])->save();
    CommentType::create([
      'id' => $this->randomMachineName(),
      'target_entity_type_id' => 'node',
    ])->save();

    $this->entityStringId = EntityTestStringId::create([
      'id' => $this->randomMachineName(),
    ]);
    $this->entityStringId->save();

    // Use the util to create an instance.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_taxonomy_term', 'Test content entity reference', 'taxonomy_term');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_entity_test_string_id', 'Test content entity reference with string ID', 'entity_test_string_id');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_taxonomy_vocabulary', 'Test config entity reference', 'taxonomy_vocabulary');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_node', 'Test node entity reference', 'node', 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_user', 'Test user entity reference', 'user');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_comment', 'Test comment entity reference', 'comment');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_file', 'Test file entity reference', 'file');
    $this->createEntityReferenceField('entity_test_string_id', 'entity_test_string_id', 'field_test_entity_test', 'Test content entity reference with string ID', 'entity_test');
  }

  /**
   * Tests the entity reference field type for referencing content entities.
   */
  public function testContentEntityReferenceItem() {
    $tid = $this->term->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = EntityTest::create();
    $entity->field_test_taxonomy_term->target_id = $tid;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = EntityTest::load($entity->id());
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test_taxonomy_term);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test_taxonomy_term[0]);
    $this->assertEquals($tid, $entity->field_test_taxonomy_term->target_id);
    $this->assertEquals($this->term->getName(), $entity->field_test_taxonomy_term->entity->getName());
    $this->assertEquals($tid, $entity->field_test_taxonomy_term->entity->id());
    $this->assertEquals($this->term->uuid(), $entity->field_test_taxonomy_term->entity->uuid());
    // Verify that the label for the target ID property definition is correct.
    $label = $entity->field_test_taxonomy_term->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinition('target_id')->getLabel();
    $this->assertInstanceOf(TranslatableMarkup::class, $label);
    $this->assertEquals('Taxonomy term ID', $label->render());

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_test_taxonomy_term->entity->setName($new_name);
    $entity->field_test_taxonomy_term->entity->save();
    // Verify it is the correct name.
    $term = Term::load($tid);
    $this->assertEquals($new_name, $term->getName());

    // Make sure the computed term reflects updates to the term id.
    $term2 = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term2->save();

    // Test all the possible ways of assigning a value.
    $entity->field_test_taxonomy_term->target_id = $term->id();
    $this->assertEquals($term->id(), $entity->field_test_taxonomy_term->entity->id());
    $this->assertEquals($term->getName(), $entity->field_test_taxonomy_term->entity->getName());

    $entity->field_test_taxonomy_term = [['target_id' => $term2->id()]];
    $this->assertEquals($term2->id(), $entity->field_test_taxonomy_term->entity->id());
    $this->assertEquals($term2->getName(), $entity->field_test_taxonomy_term->entity->getName());

    // Test value assignment via the computed 'entity' property.
    $entity->field_test_taxonomy_term->entity = $term;
    $this->assertEquals($term->id(), $entity->field_test_taxonomy_term->target_id);
    $this->assertEquals($term->getName(), $entity->field_test_taxonomy_term->entity->getName());

    $entity->field_test_taxonomy_term = [['entity' => $term2]];
    $this->assertEquals($term2->id(), $entity->field_test_taxonomy_term->target_id);
    $this->assertEquals($term2->getName(), $entity->field_test_taxonomy_term->entity->getName());

    // Test assigning an invalid item throws an exception.
    try {
      $entity->field_test_taxonomy_term = ['target_id' => 'invalid', 'entity' => $term2];
      $this->fail('Assigning an invalid item throws an exception.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(\InvalidArgumentException::class, $e);
    }

    // Delete terms so we have nothing to reference and try again
    $term->delete();
    $term2->delete();
    $entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity->save();

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_test_taxonomy_term->generateSampleItems();
    $entity->field_test_taxonomy_vocabulary->generateSampleItems();
    $this->entityValidateAndSave($entity);

    // Tests that setting an integer target ID together with an entity object
    // succeeds and does not cause any exceptions. There is no assertion here,
    // as the assignment should not throw any exceptions and if it does the
    // test will fail.
    // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::setValue().
    $user = User::create(['name' => $this->randomString()]);
    $user->save();
    $entity = EntityTest::create(['user_id' => ['target_id' => (int) $user->id(), 'entity' => $user]]);
  }

  /**
   * Tests the ::generateSampleValue() method.
   */
  public function testGenerateSampleValue() {
    $entity = EntityTest::create();

    // Test while a term exists.
    $entity->field_test_taxonomy_term->generateSampleItems();
    $this->assertInstanceOf(TermInterface::class, $entity->field_test_taxonomy_term->entity);
    $this->entityValidateAndSave($entity);

    // Delete the term and test again.
    $this->term->delete();
    $entity->field_test_taxonomy_term->generateSampleItems();
    $this->assertInstanceOf(TermInterface::class, $entity->field_test_taxonomy_term->entity);
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests the ::generateSampleValue() method when it has a circular reference.
   */
  public function testGenerateSampleValueCircularReference() {
    // Delete the existing entity.
    $this->entityStringId->delete();

    $entity_storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $entity = $entity_storage->createWithSampleValues('entity_test');
    $this->assertInstanceOf(EntityTestStringId::class, $entity->field_test_entity_test_string_id->entity);
    $this->assertInstanceOf(EntityTest::class, $entity->field_test_entity_test_string_id->entity->field_test_entity_test->entity);
  }

  /**
   * Tests referencing content entities with string IDs.
   */
  public function testContentEntityReferenceItemWithStringId() {
    $entity = EntityTest::create();
    $entity->field_test_entity_test_string_id->target_id = $this->entityStringId->id();
    $entity->save();
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $storage->resetCache();
    $this->assertEquals($this->entityStringId->id(), $storage->load($entity->id())->field_test_entity_test_string_id->target_id);
    // Verify that the label for the target ID property definition is correct.
    $label = $entity->field_test_taxonomy_term->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinition('target_id')->getLabel();
    $this->assertInstanceOf(TranslatableMarkup::class, $label);
    $this->assertEquals('Taxonomy term ID', $label->render());
  }

  /**
   * Tests the entity reference field type for referencing config entities.
   */
  public function testConfigEntityReferenceItem() {
    $referenced_entity_id = $this->vocabulary->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = EntityTest::create();
    $entity->field_test_taxonomy_vocabulary->target_id = $referenced_entity_id;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = EntityTest::load($entity->id());
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test_taxonomy_vocabulary);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test_taxonomy_vocabulary[0]);
    $this->assertEquals($referenced_entity_id, $entity->field_test_taxonomy_vocabulary->target_id);
    $this->assertEquals($this->vocabulary->label(), $entity->field_test_taxonomy_vocabulary->entity->label());
    $this->assertEquals($referenced_entity_id, $entity->field_test_taxonomy_vocabulary->entity->id());
    $this->assertEquals($this->vocabulary->uuid(), $entity->field_test_taxonomy_vocabulary->entity->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_test_taxonomy_vocabulary->entity->set('name', $new_name);
    $entity->field_test_taxonomy_vocabulary->entity->save();
    // Verify it is the correct name.
    $vocabulary = Vocabulary::load($referenced_entity_id);
    $this->assertEquals($new_name, $vocabulary->label());

    // Make sure the computed term reflects updates to the term id.
    $vocabulary2 = $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary2->save();

    $entity->field_test_taxonomy_vocabulary->target_id = $vocabulary2->id();
    $this->assertEquals($vocabulary2->id(), $entity->field_test_taxonomy_vocabulary->entity->id());
    $this->assertEquals($vocabulary2->label(), $entity->field_test_taxonomy_vocabulary->entity->label());

    // Delete terms so we have nothing to reference and try again
    $this->vocabulary->delete();
    $vocabulary2->delete();
    $entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity->save();
  }

  /**
   * Tests entity auto create.
   */
  public function testEntityAutoCreate() {
    // The term entity is unsaved here.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $entity = EntityTest::create();
    // Now assign the unsaved term to the field.
    $entity->field_test_taxonomy_term->entity = $term;
    $entity->name->value = $this->randomMachineName();
    // This is equal to storing an entity to tempstore or cache and retrieving
    // it back. An example for this is node preview.
    $entity = serialize($entity);
    $entity = unserialize($entity);
    // And then the entity.
    $entity->save();
    $term = \Drupal::service('entity.repository')->loadEntityByUuid($term->getEntityTypeId(), $term->uuid());
    $this->assertEquals($entity->field_test_taxonomy_term->entity->id(), $term->id());
  }

  /**
   * Test saving order sequence doesn't matter.
   */
  public function testEntitySaveOrder() {
    // The term entity is unsaved here.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $entity = EntityTest::create();
    // Now assign the unsaved term to the field.
    $entity->field_test_taxonomy_term->entity = $term;
    $entity->name->value = $this->randomMachineName();
    // Now get the field value.
    $value = $entity->get('field_test_taxonomy_term');
    $this->assertTrue(empty($value['target_id']));
    $this->assertNull($entity->field_test_taxonomy_term->target_id);
    // And then set it.
    $entity->field_test_taxonomy_term = $value;
    // Now save the term.
    $term->save();
    // And then the entity.
    $entity->save();
    $this->assertEquals($term->id(), $entity->field_test_taxonomy_term->entity->id());
  }

  /**
   * Tests that the 'handler' field setting stores the proper plugin ID.
   */
  public function testSelectionHandlerSettings() {
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $field_storage->save();

    // Do not specify any value for the 'handler' setting in order to verify
    // that the default handler with the correct derivative is used.
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    $field = FieldConfig::load($field->id());
    $this->assertEquals('default:entity_test', $field->getSetting('handler'));

    // Change the target_type in the field storage, and check that the handler
    // was correctly reassigned in the field.
    $field_storage->setSetting('target_type', 'entity_test_rev');
    $field_storage->save();
    $field = FieldConfig::load($field->id());
    $this->assertEquals('default:entity_test_rev', $field->getSetting('handler'));

    // Change the handler to another, non-derivative plugin.
    $field->setSetting('handler', 'views');
    $field->save();
    $field = FieldConfig::load($field->id());
    $this->assertEquals('views', $field->getSetting('handler'));

    // Change the target_type in the field storage again, and check that the
    // non-derivative handler was unchanged.
    $field_storage->setSetting('target_type', 'entity_test_rev');
    $field_storage->save();
    $field = FieldConfig::load($field->id());
    $this->assertEquals('views', $field->getSetting('handler'));
  }

  /**
   * Tests ValidReferenceConstraint with newly created and unsaved entities.
   */
  public function testAutocreateValidation() {
    // The term entity is unsaved here.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $entity = EntityTest::create([
      'field_test_taxonomy_term' => [
        'entity' => $term,
        'target_id' => NULL,
      ],
    ]);
    $errors = $entity->validate();
    // Using target_id of NULL is valid with an unsaved entity.
    $this->assertCount(0, $errors);
    // Using target_id of NULL is not valid with a saved entity.
    $term->save();
    $entity = EntityTest::create([
      'field_test_taxonomy_term' => [
        'entity' => $term,
        'target_id' => NULL,
      ],
    ]);
    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals('This value should not be null.', $errors[0]->getMessage());
    $this->assertEquals('field_test_taxonomy_term.0', $errors[0]->getPropertyPath());
    // This should rectify the issue, favoring the entity over the target_id.
    $entity->save();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);

    // Test with an unpublished and unsaved node.
    $title = $this->randomString();
    $node = Node::create([
      'title' => $title,
      'type' => 'node',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);

    $entity = EntityTest::create([
      'field_test_node' => [
        'entity' => $node,
      ],
    ]);

    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'node', '%label' => $title]), $errors[0]->getMessage());
    $this->assertEquals('field_test_node.0.entity', $errors[0]->getPropertyPath());

    // Publish the node and try again.
    $node->setPublished();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);

    // Test with a mix of valid and invalid nodes.
    $unsaved_unpublished_node_title = $this->randomString();
    $unsaved_unpublished_node = Node::create([
      'title' => $unsaved_unpublished_node_title,
      'type' => 'node',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);

    $saved_unpublished_node_title = $this->randomString();
    $saved_unpublished_node = Node::create([
      'title' => $saved_unpublished_node_title,
      'type' => 'node',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $saved_unpublished_node->save();

    $saved_published_node_title = $this->randomString();
    $saved_published_node = Node::create([
      'title' => $saved_published_node_title,
      'type' => 'node',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $saved_published_node->save();

    $entity = EntityTest::create([
      'field_test_node' => [
        [
          'entity' => $unsaved_unpublished_node,
        ],
        [
          'target_id' => $saved_unpublished_node->id(),
        ],
        [
          'target_id' => $saved_published_node->id(),
        ],
      ],
    ]);

    $errors = $entity->validate();
    $this->assertCount(2, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'node', '%label' => $unsaved_unpublished_node_title]), $errors[0]->getMessage());
    $this->assertEquals('field_test_node.0.entity', $errors[0]->getPropertyPath());
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'node', '%label' => $saved_unpublished_node->id()]), $errors[1]->getMessage());
    $this->assertEquals('field_test_node.1.target_id', $errors[1]->getPropertyPath());

    // Publish one of the nodes and try again.
    $saved_unpublished_node->setPublished();
    $saved_unpublished_node->save();
    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'node', '%label' => $unsaved_unpublished_node_title]), $errors[0]->getMessage());
    $this->assertEquals('field_test_node.0.entity', $errors[0]->getPropertyPath());

    // Publish the last invalid node and try again.
    $unsaved_unpublished_node->setPublished();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);

    // Test with an unpublished and unsaved comment.
    $title = $this->randomString();
    $comment = Comment::create([
      'subject' => $title,
      'comment_type' => 'comment',
      'status' => 0,
    ]);

    $entity = EntityTest::create([
      'field_test_comment' => [
        'entity' => $comment,
      ],
    ]);

    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'comment', '%label' => $title]), $errors[0]->getMessage());
    $this->assertEquals('field_test_comment.0.entity', $errors[0]->getPropertyPath());

    // Publish the comment and try again.
    $comment->setPublished();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);

    // Test with an inactive and unsaved user.
    $name = $this->randomString();
    $user = User::create([
      'name' => $name,
      'status' => 0,
    ]);

    $entity = EntityTest::create([
      'field_test_user' => [
        'entity' => $user,
      ],
    ]);

    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'user', '%label' => $name]), $errors[0]->getMessage());
    $this->assertEquals('field_test_user.0.entity', $errors[0]->getPropertyPath());

    // Activate the user and try again.
    $user->activate();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);

    // Test with a temporary and unsaved file.
    $filename = $this->randomMachineName() . '.txt';
    $file = File::create([
      'filename' => $filename,
      'status' => 0,
    ]);

    $entity = EntityTest::create([
      'field_test_file' => [
        'entity' => $file,
      ],
    ]);

    $errors = $entity->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %label) cannot be referenced.', ['%type' => 'file', '%label' => $filename]), $errors[0]->getMessage());
    $this->assertEquals('field_test_file.0.entity', $errors[0]->getPropertyPath());

    // Set the file as permanent and try again.
    $file->setPermanent();
    $errors = $entity->validate();
    $this->assertCount(0, $errors);
  }

}
