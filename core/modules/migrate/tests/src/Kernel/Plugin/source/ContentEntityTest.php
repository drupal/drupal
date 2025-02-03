<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the entity content source plugin.
 *
 * @group migrate
 * @group #slow
 */
class ContentEntityTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'migrate',
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);

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

    // Create a node, with data in a term reference field, and then add a French
    // translation of the node.
    $this->user = User::create([
      'name' => 'user123',
      'uid' => 1,
      'mail' => 'example@example.com',
    ]);
    $this->user->save();

    // Add the anonymous user so we can test later that it is not provided in a
    // source row.
    User::create([
      'name' => 'anon',
      'uid' => 0,
    ])->save();

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
      'title' => 'fr - Apples',
      $this->fieldName => $term->id(),
    ])->save();

    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Helper to assert IDs structure.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source plugin.
   * @param array $configuration
   *   The source plugin configuration (Nope, no getter available).
   *
   * @internal
   */
  protected function assertIds(MigrateSourceInterface $source, array $configuration): void {
    $ids = $source->getIds();
    [, $entity_type_id] = explode(PluginBase::DERIVATIVE_SEPARATOR, $source->getPluginId());
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    $this->assertArrayHasKey($entity_type->getKey('id'), $ids);
    $ids_count_expected = 1;

    if ($entity_type->isTranslatable()) {
      $ids_count_expected++;
      $this->assertArrayHasKey($entity_type->getKey('langcode'), $ids);
    }

    if ($entity_type->isRevisionable() && $configuration['add_revision_id']) {
      $ids_count_expected++;
      $this->assertArrayHasKey($entity_type->getKey('revision'), $ids);
    }

    $this->assertCount($ids_count_expected, $ids);
  }

  /**
   * Tests user source plugin.
   *
   * @dataProvider migrationConfigurationProvider
   */
  public function testUserSource(array $configuration): void {
    $migration = $this->migrationPluginManager
      ->createStubMigration($this->migrationDefinition('content_entity:user', $configuration));
    $user_source = $migration->getSourcePlugin();
    $this->assertSame('users', $user_source->__toString());
    if (!$configuration['include_translations']) {
      // Confirm that the anonymous user is in the source database but not
      // included in the rows returned by the content_entity.
      $this->assertNotNull(User::load(0));
      $this->assertEquals(1, $user_source->count());
    }
    $this->assertIds($user_source, $configuration);
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
   *
   * @dataProvider migrationConfigurationProvider
   */
  public function testFileSource(array $configuration): void {
    $file = File::create([
      'filename' => 'foo.txt',
      'uid' => $this->user->id(),
      'uri' => 'public://foo.txt',
    ]);
    $file->save();

    $migration = $this->migrationPluginManager
      ->createStubMigration($this->migrationDefinition('content_entity:file', $configuration));
    $file_source = $migration->getSourcePlugin();
    $this->assertSame('files', $file_source->__toString());
    if (!$configuration['include_translations']) {
      $this->assertEquals(1, $file_source->count());
    }
    $this->assertIds($file_source, $configuration);
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
   *
   * @dataProvider migrationConfigurationProvider
   */
  public function testNodeSource(array $configuration): void {
    $configuration += ['bundle' => $this->bundle];
    $migration = $this->migrationPluginManager
      ->createStubMigration($this->migrationDefinition('content_entity:node', $configuration));
    $node_source = $migration->getSourcePlugin();
    $this->assertSame('content items', $node_source->__toString());
    $this->assertIds($node_source, $configuration);
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
    if ($configuration['add_revision_id']) {
      $this->assertEquals(1, $values['vid']);
    }
    else {
      $this->assertEquals([['value' => '1']], $values['vid']);
    }
    $this->assertEquals('en', $values['langcode']);
    $this->assertEquals(1, $values['status'][0]['value']);
    $this->assertEquals('Apples', $values['title'][0]['value']);
    $this->assertEquals(1, $values['default_langcode'][0]['value']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
    if ($configuration['include_translations']) {
      $node_source->next();
      $values = $node_source->current()->getSource();
      $this->assertEquals($this->bundle, $values['type'][0]['target_id']);
      $this->assertEquals(1, $values['nid']);
      if ($configuration['add_revision_id']) {
        $this->assertEquals(1, $values['vid']);
      }
      else {
        $this->assertEquals([0 => ['value' => 1]], $values['vid']);
      }
      $this->assertEquals('fr', $values['langcode']);
      $this->assertEquals(1, $values['status'][0]['value']);
      $this->assertEquals('fr - Apples', $values['title'][0]['value']);
      $this->assertEquals(0, $values['default_langcode'][0]['value']);
      $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
    }
  }

  /**
   * Tests media source plugin.
   *
   * @dataProvider migrationConfigurationProvider
   */
  public function testMediaSource(array $configuration): void {
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

    $configuration += [
      'bundle' => 'image',
    ];
    $migration = $this->migrationPluginManager
      ->createStubMigration($this->migrationDefinition('content_entity:media', $configuration));
    $media_source = $migration->getSourcePlugin();
    $this->assertSame('media items', $media_source->__toString());
    if (!$configuration['include_translations']) {
      $this->assertEquals(1, $media_source->count());
    }
    $this->assertIds($media_source, $configuration);
    $fields = $media_source->fields();
    $this->assertArrayHasKey('bundle', $fields);
    $this->assertArrayHasKey('mid', $fields);
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('status', $fields);
    $media_source->rewind();
    $values = $media_source->current()->getSource();
    $this->assertEquals(1, $values['mid']);
    if ($configuration['add_revision_id']) {
      $this->assertEquals(1, $values['vid']);
    }
    else {
      $this->assertEquals([['value' => 1]], $values['vid']);
    }
    $this->assertEquals('Foo media', $values['name'][0]['value']);
    $this->assertNull($values['thumbnail'][0]['title']);
    $this->assertEquals(1, $values['uid'][0]['target_id']);
    $this->assertEquals('image', $values['bundle'][0]['target_id']);
  }

  /**
   * Tests term source plugin.
   *
   * @dataProvider migrationConfigurationProvider
   */
  public function testTermSource(array $configuration): void {
    $term2 = Term::create([
      'vid' => $this->vocabulary,
      'name' => 'Granny Smith',
      'uid' => $this->user->id(),
      'parent' => 1,
    ]);
    $term2->save();

    $configuration += [
      'bundle' => $this->vocabulary,
    ];
    $migration = $this->migrationPluginManager
      ->createStubMigration($this->migrationDefinition('content_entity:taxonomy_term', $configuration));
    $term_source = $migration->getSourcePlugin();
    $this->assertSame('taxonomy terms', $term_source->__toString());
    if (!$configuration['include_translations']) {
      $this->assertEquals(2, $term_source->count());
    }
    $this->assertIds($term_source, $configuration);
    $fields = $term_source->fields();
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('revision_id', $fields);
    $this->assertArrayHasKey('tid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $term_source->rewind();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(1, $values['tid']);
    $this->assertEquals('Apples', $values['name'][0]['value']);
    $this->assertSame([['target_id' => '0']], $values['parent']);
    $term_source->next();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(2, $values['tid']);
    $this->assertEquals('Granny Smith', $values['name'][0]['value']);
    $this->assertSame([['target_id' => '1']], $values['parent']);
  }

  /**
   * Data provider for several test methods.
   *
   * @see \Drupal\Tests\migrate\Kernel\Plugin\source\ContentEntityTest::testUserSource
   * @see \Drupal\Tests\migrate\Kernel\Plugin\source\ContentEntityTest::testFileSource
   * @see \Drupal\Tests\migrate\Kernel\Plugin\source\ContentEntityTest::testNodeSource
   * @see \Drupal\Tests\migrate\Kernel\Plugin\source\ContentEntityTest::testMediaSource
   * @see \Drupal\Tests\migrate\Kernel\Plugin\source\ContentEntityTest::testTermSource
   */
  public static function migrationConfigurationProvider(): array {
    $data = [];
    foreach ([FALSE, TRUE] as $include_translations) {
      foreach ([FALSE, TRUE] as $add_revision_id) {
        $configuration = [
          'include_translations' => $include_translations,
          'add_revision_id' => $add_revision_id,
        ];
        // Add an array key for this data set.
        $data[http_build_query($configuration)] = [$configuration];
      }
    }
    return $data;
  }

  /**
   * Get a migration definition.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return array
   *   The definition.
   */
  protected function migrationDefinition(string $plugin_id, array $configuration = []): array {
    return [
      'source' => [
        'plugin' => $plugin_id,
      ] + $configuration,
      'process' => [],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
  }

}
