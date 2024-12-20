<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\DefaultContent;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DefaultContent\PreImportEvent;
use Drupal\Core\DefaultContent\Existing;
use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\DefaultContent\ImportException;
use Drupal\Core\DefaultContent\InvalidEntityException;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\layout_builder\Section;
use Drupal\media\MediaInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Psr\Log\LogLevel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Drupal\Core\DefaultContent\Importer
 * @group DefaultContent
 * @group Recipe
 * @group #slow
 */
class ContentImportTest extends BrowserTestBase {

  use EntityReferenceFieldCreationTrait;
  use MediaTypeCreationTrait;
  use RecipeTestTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'content_translation',
    'entity_test',
    'layout_builder',
    'media',
    'menu_link_content',
    'node',
    'path',
    'path_alias',
    'system',
    'taxonomy',
    'user',
  ];

  private readonly string $contentDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser(admin: TRUE);

    BlockContentType::create(['id' => 'basic', 'label' => 'Basic'])->save();
    block_content_add_body_field('basic');

    $this->createVocabulary(['vid' => 'tags']);
    $this->createMediaType('image', ['id' => 'image']);
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateContentType(['type' => 'article']);
    $this->createEntityReferenceField('node', 'article', 'field_tags', 'Tags', 'taxonomy_term');

    // Create a field with custom serialization, so we can ensure that the
    // importer handles that properly.
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'taxonomy_term',
      'field_name' => 'field_serialized_stuff',
      'type' => 'serialized_property_item_test',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'tags',
    ])->save();

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'article',
    ])
      ->setThirdPartySetting('content_translation', 'enabled', TRUE)
      ->save();

    $this->contentDir = $this->getDrupalRoot() . '/core/tests/fixtures/default_content';
    \Drupal::service('file_system')->copy($this->contentDir . '/file/druplicon_copy.png', $this->publicFilesDirectory . '/druplicon_copy.png', FileExists::Error);

    // Enable Layout Builder for the Page content type, with custom overrides.
    \Drupal::service(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'page')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * @return array<array<mixed>>
   */
  public static function providerImportEntityThatAlreadyExists(): array {
    return [
      [Existing::Error],
      [Existing::Skip],
    ];
  }

  /**
   * @dataProvider providerImportEntityThatAlreadyExists
   */
  public function testImportEntityThatAlreadyExists(Existing $existing): void {
    $this->drupalCreateUser(values: ['uuid' => '94503467-be7f-406c-9795-fc25baa22203']);

    if ($existing === Existing::Error) {
      $this->expectException(ImportException::class);
      $this->expectExceptionMessage('user 94503467-be7f-406c-9795-fc25baa22203 already exists.');
    }

    $this->container->get(Importer::class)
      ->importContent(new Finder($this->contentDir), $existing);
  }

  /**
   * Tests importing content directly, via the API.
   */
  public function testDirectContentImport(): void {
    $logger = new TestLogger();

    /** @var \Drupal\Core\DefaultContent\Importer $importer */
    $importer = $this->container->get(Importer::class);
    $importer->setLogger($logger);
    $importer->importContent(new Finder($this->contentDir));

    $this->assertContentWasImported();
    // We should see a warning about importing a file entity associated with a
    // file that doesn't exist.
    $predicate = function (array $record): bool {
      return (
        $record['message'] === 'File entity %name was imported, but the associated file (@path) was not found.' &&
        $record['context']['%name'] === 'dce9cdc3-d9fc-4d37-849d-105e913bb5ad.png' &&
        $record['context']['@path'] === $this->contentDir . '/file/dce9cdc3-d9fc-4d37-849d-105e913bb5ad.png'
      );
    };
    $this->assertTrue($logger->hasRecordThatPasses($predicate, LogLevel::WARNING));
  }

  /**
   * Tests that the importer validates entities before saving them.
   */
  public function testEntityValidationIsTriggered(): void {
    $dir = uniqid('public://');
    mkdir($dir);

    /** @var string $data */
    $data = file_get_contents($this->contentDir . '/node/2d3581c3-92c7-4600-8991-a0d4b3741198.yml');
    $data = Yaml::decode($data);
    /** @var array{default: array{sticky: array<int, array{value: mixed}>}} $data */
    $data['default']['sticky'][0]['value'] = 'not a boolean!';
    file_put_contents($dir . '/invalid.yml', Yaml::encode($data));

    $this->expectException(InvalidEntityException::class);
    $this->expectExceptionMessage("$dir/invalid.yml: sticky.0.value=This value should be of the correct primitive type.");
    $this->container->get(Importer::class)->importContent(new Finder($dir));
  }

  /**
   * Asserts that the default content was imported as expected.
   */
  private function assertContentWasImported(): void {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = $this->container->get(EntityRepositoryInterface::class);

    $node = $entity_repository->loadEntityByUuid('node', 'e1714f23-70c0-4493-8e92-af1901771921');
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('Crikey it works!', $node->body->value);
    $this->assertSame('article', $node->bundle());
    $this->assertSame('Test Article', $node->label());
    $tag = $node->field_tags->entity;
    $this->assertInstanceOf(TermInterface::class, $tag);
    $this->assertSame('Default Content', $tag->label());
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('550f86ad-aa11-4047-953f-636d42889f85', $tag->uuid());
    // The tag carries a field with serialized data, so ensure it came through
    // properly.
    $this->assertSame('a:2:{i:0;s:2:"Hi";i:1;s:6:"there!";}', $tag->field_serialized_stuff->value);
    $this->assertSame('94503467-be7f-406c-9795-fc25baa22203', $node->getOwner()->uuid());
    // The node's URL should use the path alias shipped with the recipe.
    $node_url = $node->toUrl()->toString();
    $this->assertSame(Url::fromUserInput('/test-article')->toString(), $node_url);

    $media = $entity_repository->loadEntityByUuid('media', '344b943c-b231-4d73-9669-0b0a2be12aa5');
    $this->assertInstanceOf(MediaInterface::class, $media);
    $this->assertSame('image', $media->bundle());
    $this->assertSame('druplicon.png', $media->label());
    $file = $media->field_media_image->entity;
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertSame('druplicon.png', $file->getFilename());
    $this->assertSame('d8404562-efcc-40e3-869e-40132d53fe0b', $file->uuid());

    // Another file entity referencing an existing file but already in use by
    // another entity, should be imported.
    $same_file_different_entity = $entity_repository->loadEntityByUuid('file', '23a7f61f-1db3-407d-a6dd-eb4731995c9f');
    $this->assertInstanceOf(FileInterface::class, $same_file_different_entity);
    $this->assertSame('druplicon-duplicate.png', $same_file_different_entity->getFilename());
    $this->assertStringEndsWith('/druplicon_0.png', (string) $same_file_different_entity->getFileUri());

    // Another file entity that references a file with the same name as, but
    // different contents than, an existing file, should be imported and the
    // file should be renamed.
    $different_file = $entity_repository->loadEntityByUuid('file', 'a6b79928-838f-44bd-a8f0-44c2fff9e4cc');
    $this->assertInstanceOf(FileInterface::class, $different_file);
    $this->assertSame('druplicon-different.png', $different_file->getFilename());
    $this->assertStringEndsWith('/druplicon_1.png', (string) $different_file->getFileUri());

    // Another file entity referencing an existing file but one that is not in
    // use by another entity, should be imported but use the existing file.
    $different_file = $entity_repository->loadEntityByUuid('file', '7fb09f9f-ba5f-4db4-82ed-aa5ccf7d425d');
    $this->assertInstanceOf(FileInterface::class, $different_file);
    $this->assertSame('druplicon_copy.png', $different_file->getFilename());
    $this->assertStringEndsWith('/druplicon_copy.png', (string) $different_file->getFileUri());

    // Our node should have a menu link, and it should use the path alias we
    // included with the recipe.
    $menu_link = $entity_repository->loadEntityByUuid('menu_link_content', '3434bd5a-d2cd-4f26-bf79-a7f6b951a21b');
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link);
    $this->assertSame($menu_link->getUrlObject()->toString(), $node_url);
    $this->assertSame('main', $menu_link->getMenuName());

    $block_content = $entity_repository->loadEntityByUuid('block_content', 'd9b72b2f-a5ea-4a3f-b10c-28deb7b3b7bf');
    $this->assertInstanceOf(BlockContentInterface::class, $block_content);
    $this->assertSame('basic', $block_content->bundle());
    $this->assertSame('Useful Info', $block_content->label());
    $this->assertSame("I'd love to put some useful info here.", $block_content->body->value);

    // A node with a non-existent owner should be reassigned to the current
    // user.
    $node = $entity_repository->loadEntityByUuid('node', '7f1dd75a-0be2-4d3b-be5d-9d1a868b9267');
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame(\Drupal::currentUser()->id(), $node->getOwner()->id());

    // Ensure a node with a translation is imported properly.
    $node = $entity_repository->loadEntityByUuid('node', '2d3581c3-92c7-4600-8991-a0d4b3741198');
    $this->assertInstanceOf(NodeInterface::class, $node);
    $translation = $node->getTranslation('fr');
    $this->assertSame('Perdu en traduction', $translation->label());
    $this->assertSame("Içi c'est la version français.", $translation->body->value);

    // Layout data should be imported.
    $node = $entity_repository->loadEntityByUuid('node', '32650de8-9edd-48dc-80b8-8bda180ebbac');
    $this->assertInstanceOf(NodeInterface::class, $node);
    $section = $node->layout_builder__layout[0]->section;
    $this->assertInstanceOf(Section::class, $section);
    $this->assertCount(2, $section->getComponents());
    $this->assertSame('system_powered_by_block', $section->getComponent('03b45f14-cf74-469a-8398-edf3383ce7fa')->getPluginId());
  }

  /**
   * Tests that the pre-import event allows skipping certain entities.
   */
  public function testPreImportEvent(): void {
    $invalid_uuid_detected = FALSE;

    $listener = function (PreImportEvent $event) use (&$invalid_uuid_detected): void {
      $event->skip('3434bd5a-d2cd-4f26-bf79-a7f6b951a21b', 'Decided not to!');
      try {
        $event->skip('not-a-thing');
      }
      catch (\InvalidArgumentException) {
        $invalid_uuid_detected = TRUE;
      }
    };
    \Drupal::service(EventDispatcherInterface::class)
      ->addListener(PreImportEvent::class, $listener);

    $finder = new Finder($this->contentDir);
    $this->assertSame('menu_link_content', $finder->data['3434bd5a-d2cd-4f26-bf79-a7f6b951a21b']['_meta']['entity_type']);

    /** @var \Drupal\Core\DefaultContent\Importer $importer */
    $importer = \Drupal::service(Importer::class);
    $logger = new TestLogger();
    $importer->setLogger($logger);
    $importer->importContent($finder, Existing::Error);

    // The entity we skipped should not be here, and the reason why should have
    // been logged.
    $menu_link = \Drupal::service(EntityRepositoryInterface::class)
      ->loadEntityByUuid('menu_link_content', '3434bd5a-d2cd-4f26-bf79-a7f6b951a21b');
    $this->assertNull($menu_link);
    $this->assertTrue($logger->hasInfo([
      'message' => 'Skipped importing @entity_type @uuid because: %reason',
      'context' => [
        '@entity_type' => 'menu_link_content',
        '@uuid' => '3434bd5a-d2cd-4f26-bf79-a7f6b951a21b',
        '%reason' => 'Decided not to!',
      ],
    ]));
    // We should have caught an exception for trying to skip an invalid UUID.
    $this->assertTrue($invalid_uuid_detected);
  }

}
