<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\ContentEntity;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the entity content source plugin.
 *
 * @group migrate_drupal
 */
class ContentEntityTest extends KernelTestBase {

  use EntityReferenceTestTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'migrate',
    'migrate_drupal',
    'system',
    'node',
    'taxonomy',
    'field',
    'file',
    'image',
    'media',
    'media_test_source',
    'text',
    'filter',
    'language',
    'content_translation',
  ];

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'article';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_entity_reference';

  /**
   * The vocabulary ID.
   *
   * @var string
   */
  protected $vocabulary = 'fruit';

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The source plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourcePluginManager
   */
  protected $sourcePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', 'users_data');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', ['node_access']);
    $this->installConfig($this->modules);

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create article content type.
    $node_type = NodeType::create(['type' => $this->bundle, 'name' => 'Article']);
    $node_type->save();

    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->vocabulary,
      'description' => $this->vocabulary,
      'vid' => $this->vocabulary,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    // Create a term reference field on node.
    $this->createEntityReferenceField(
      'node',
      $this->bundle,
      $this->fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [$this->vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Create a term reference field on user.
    $this->createEntityReferenceField(
      'user',
      'user',
      $this->fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [$this->vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create some data.
    $this->user = User::create([
      'name' => 'user123',
      'uid' => 1,
      'mail' => 'example@example.com',
    ]);
    $this->user->save();

    $term = Term::create([
      'vid' => $this->vocabulary,
      'name' => 'Apples',
      'uid' => $this->user->id(),
    ]);
    $term->save();
    $this->user->set($this->fieldName, $term->id());
    $this->user->save();
    $node = Node::create([
      'type' => $this->bundle,
      'title' => 'Apples',
      $this->fieldName => $term->id(),
      'uid' => $this->user->id(),
    ]);
    $node->save();
    $node->addTranslation('fr', [
      'title' => 'Pommes',
      $this->fieldName => $term->id(),
    ])->save();

    $this->sourcePluginManager = $this->container->get('plugin.manager.migrate.source');
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Tests the constructor for missing entity_type.
   */
  public function testConstructorEntityTypeMissing() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [];
    $plugin_definition = [
      'entity_type' => '',
    ];
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('Missing required "entity_type" definition.');
    ContentEntity::create($this->container, $configuration, 'content_entity', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for non content entity.
   */
  public function testConstructorNonContentEntity() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [];
    $plugin_definition = [
      'entity_type' => 'node_type',
    ];
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The entity type (node_type) is not supported. The "content_entity" source plugin only supports content entities.');
    ContentEntity::create($this->container, $configuration, 'content_entity:node_type', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for not bundleable entity.
   */
  public function testConstructorNotBundable() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [
      'bundle' => 'foo',
    ];
    $plugin_definition = [
      'entity_type' => 'user',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('A bundle was provided but the entity type (user) is not bundleable');
    ContentEntity::create($this->container, $configuration, 'content_entity:user', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for invalid entity bundle.
   */
  public function testConstructorInvalidBundle() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [
      'bundle' => 'foo',
    ];
    $plugin_definition = [
      'entity_type' => 'node',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided bundle (foo) is not valid for the (node) entity type.');
    ContentEntity::create($this->container, $configuration, 'content_entity:node', $plugin_definition, $migration);
  }

  /**
   * Tests user source plugin.
   */
  public function testUserSource() {
    $configuration = [
      'include_translations' => FALSE,
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:user'));
    $user_source = $this->sourcePluginManager->createInstance('content_entity:user', $configuration, $migration);
    $this->assertSame('users', $user_source->__toString());
    $this->assertEquals(1, $user_source->count());
    $ids = $user_source->getIds();
    $this->assertArrayHasKey('langcode', $ids);
    $this->assertArrayHasKey('uid', $ids);
    $fields = $user_source->fields();
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('pass', $fields);
    $this->assertArrayHasKey('mail', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('roles', $fields);
    $user_source->rewind();
    $values = $user_source->current()->getSource();
    $this->assertEquals('example@example.com', $values['mail'][0]['value']);
    $this->assertEquals('user123', $values['name'][0]['value']);
    $this->assertEquals(1, $values['uid']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
  }

  /**
   * Tests file source plugin.
   */
  public function testFileSource() {
    $file = File::create([
      'filename' => 'foo.txt',
      'uid' => $this->user->id(),
      'uri' => 'public://foo.txt',
    ]);
    $file->save();

    $configuration = [
      'include_translations' => FALSE,
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:file'));
    $file_source = $this->sourcePluginManager->createInstance('content_entity:file', $configuration, $migration);
    $this->assertSame('files', $file_source->__toString());
    $this->assertEquals(1, $file_source->count());
    $ids = $file_source->getIds();
    $this->assertArrayHasKey('fid', $ids);
    $fields = $file_source->fields();
    $this->assertArrayHasKey('fid', $fields);
    $this->assertArrayHasKey('filemime', $fields);
    $this->assertArrayHasKey('filename', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('uri', $fields);
    $file_source->rewind();
    $values = $file_source->current()->getSource();
    $this->assertEquals('text/plain', $values['filemime'][0]['value']);
    $this->assertEquals('public://foo.txt', $values['uri'][0]['value']);
    $this->assertEquals('foo.txt', $values['filename'][0]['value']);
    $this->assertEquals(1, $values['fid']);
  }

  /**
   * Tests node source plugin.
   */
  public function testNodeSource() {
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:node'));
    $node_source = $this->sourcePluginManager->createInstance('content_entity:node', ['bundle' => $this->bundle], $migration);
    $this->assertSame('content items', $node_source->__toString());
    $ids = $node_source->getIds();
    $this->assertArrayHasKey('langcode', $ids);
    $this->assertArrayHasKey('nid', $ids);
    $fields = $node_source->fields();
    $this->assertArrayHasKey('nid', $fields);
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('title', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('sticky', $fields);
    $node_source->rewind();
    $values = $node_source->current()->getSource();
    $this->assertEquals($this->bundle, $values['type'][0]['target_id']);
    $this->assertEquals(1, $values['nid']);
    $this->assertEquals(1, $values['vid']);
    $this->assertEquals('en', $values['langcode']);
    $this->assertEquals(1, $values['status'][0]['value']);
    $this->assertEquals('Apples', $values['title'][0]['value']);
    $this->assertEquals(1, $values['default_langcode'][0]['value']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
    $node_source->next();
    $values = $node_source->current()->getSource();
    $this->assertEquals($this->bundle, $values['type'][0]['target_id']);
    $this->assertEquals(1, $values['nid']);
    $this->assertEquals(1, $values['vid']);
    $this->assertEquals('fr', $values['langcode']);
    $this->assertEquals(1, $values['status'][0]['value']);
    $this->assertEquals('Pommes', $values['title'][0]['value']);
    $this->assertEquals(0, $values['default_langcode'][0]['value']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
  }

  /**
   * Tests media source plugin.
   */
  public function testMediaSource() {
    $values = [
      'id' => 'image',
      'label' => 'Image',
      'source' => 'test',
      'new_revision' => FALSE,
    ];
    $media_type = $this->createMediaType('test', $values);
    $media = Media::create([
      'name' => 'Foo media',
      'uid' => $this->user->id(),
      'bundle' => $media_type->id(),
    ]);
    $media->save();

    $configuration = [
      'include_translations' => FALSE,
      'bundle' => 'image',
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:media'));
    $media_source = $this->sourcePluginManager->createInstance('content_entity:media', $configuration, $migration);
    $this->assertSame('media items', $media_source->__toString());
    $this->assertEquals(1, $media_source->count());
    $ids = $media_source->getIds();
    $this->assertArrayHasKey('langcode', $ids);
    $this->assertArrayHasKey('mid', $ids);
    $fields = $media_source->fields();
    $this->assertArrayHasKey('bundle', $fields);
    $this->assertArrayHasKey('mid', $fields);
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('status', $fields);
    $media_source->rewind();
    $values = $media_source->current()->getSource();
    $this->assertEquals(1, $values['mid']);
    $this->assertEquals(1, $values['vid']);
    $this->assertEquals('Foo media', $values['name'][0]['value']);
    $this->assertNull($values['thumbnail'][0]['title']);
    $this->assertEquals(1, $values['uid'][0]['target_id']);
    $this->assertEquals('image', $values['bundle'][0]['target_id']);
  }

  /**
   * Tests term source plugin.
   */
  public function testTermSource() {
    $term2 = Term::create([
      'vid' => $this->vocabulary,
      'name' => 'Granny Smith',
      'uid' => $this->user->id(),
      'parent' => 1,
    ]);
    $term2->save();

    $configuration = [
      'include_translations' => FALSE,
      'bundle' => $this->vocabulary,
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:taxonomy_term'));
    $term_source = $this->sourcePluginManager->createInstance('content_entity:taxonomy_term', $configuration, $migration);
    $this->assertSame('taxonomy terms', $term_source->__toString());
    $this->assertEquals(2, $term_source->count());
    $ids = $term_source->getIds();
    $this->assertArrayHasKey('langcode', $ids);
    $this->assertArrayHasKey('revision_id', $ids);
    $this->assertArrayHasKey('tid', $ids);
    $fields = $term_source->fields();
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('revision_id', $fields);
    $this->assertArrayHasKey('tid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $term_source->rewind();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(1, $values['tid']);
    // @TODO: Add test coverage for parent in
    // https://www.drupal.org/project/drupal/issues/2940198
    $this->assertEquals('Apples', $values['name'][0]['value']);
    $term_source->next();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(2, $values['tid']);
    // @TODO: Add test coverage for parent in
    // https://www.drupal.org/project/drupal/issues/2940198
    $this->assertEquals('Granny Smith', $values['name'][0]['value']);
  }

  /**
   * Get a migration definition.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return array
   *   The definition.
   */
  protected function migrationDefinition($plugin_id) {
    return [
      'source' => [
        'plugin' => $plugin_id,
      ],
      'process' => [],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
  }

}
