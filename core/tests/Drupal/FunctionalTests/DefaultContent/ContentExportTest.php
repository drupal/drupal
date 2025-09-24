<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\DefaultContent;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DefaultContent\ContentExportCommand;
use Drupal\Core\DefaultContent\Exporter;
use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\file\Entity\File;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LogLevel;

/**
 * Tests exporting content in YAML format.
 */
#[CoversClass(ContentExportCommand::class)]
#[CoversClass(Exporter::class)]
#[Group('DefaultContent')]
#[Group('Recipe')]
#[RunTestsInSeparateProcesses]
class ContentExportTest extends BrowserTestBase {

  use EntityReferenceFieldCreationTrait;
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Scans for content in the fixture.
   */
  private readonly Finder $finder;

  /**
   * The directory where the default content is located.
   */
  private readonly string $contentDir;

  /**
   * The user account which is doing the content import and export.
   */
  private readonly UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Apply the recipe that sets up the fields and configuration for our
    // default content.
    $fixtures_dir = $this->getDrupalRoot() . '/core/tests/fixtures';
    $this->applyRecipe($fixtures_dir . '/recipes/default_content_base');

    // We need an administrative user to import and export content.
    $this->adminUser = $this->setUpCurrentUser(admin: TRUE);

    // Import all of the default content from the fixture.
    $this->contentDir = $fixtures_dir . '/default_content';
    $this->finder = new Finder($this->contentDir);
    $this->assertNotEmpty($this->finder->data);
    $this->container->get(Importer::class)->importContent($this->finder);
  }

  /**
   * Ensures that all imported content can be exported properly.
   */
  public function testExportContent(): void {
    // We should get an error if we try to export a non-existent entity type.
    $process = $this->runDrupalCommand(['content:export', 'camels', 42, '--no-ansi']);
    $this->assertSame(1, $process->wait());
    $this->assertStringContainsString('The entity type "camels" does not exist.', $process->getOutput());

    // We should get an error if we try to export a non-existent entity.
    $process = $this->runDrupalCommand(['content:export', 'taxonomy_term', 42, '--no-ansi']);
    $this->assertSame(1, $process->wait());
    $this->assertStringContainsString('taxonomy_term 42 does not exist.', $process->getOutput());

    // We should get an error if we try to export a config entity.
    $process = $this->runDrupalCommand(['content:export', 'taxonomy_vocabulary', 'tags', '--no-ansi']);
    $this->assertSame(1, $process->wait());
    $this->assertStringContainsString('taxonomy_vocabulary is not a content entity type.', $process->getOutput());

    $entity_repository = $this->container->get(EntityRepositoryInterface::class);

    foreach ($this->finder->data as $uuid => $imported_data) {
      $entity_type_id = $imported_data['_meta']['entity_type'];
      $entity = $entity_repository->loadEntityByUuid($entity_type_id, $uuid);
      $this->assertInstanceOf(ContentEntityInterface::class, $entity);

      $process = $this->runDrupalCommand([
        'content:export',
        $entity->getEntityTypeId(),
        $entity->id(),
      ]);
      // The export should succeed without error.
      $this->assertSame(0, $process->wait());

      // The path is added by the importer and is never exported.
      unset($imported_data['_meta']['path']);
      // The output should be identical to the imported data. Sort recursively
      // by key to prevent false negatives.
      $exported_data = Yaml::decode($process->getOutput());

      // If the entity is a file, the file URI might vary slightly -- i.e., if
      // the file already existed, the imported one would have been renamed. We
      // need to account for that.
      if ($entity->getEntityTypeId() === 'file') {
        $imported_uri = $entity->getFileUri();
        $extension = strlen('.' . pathinfo($imported_uri, PATHINFO_EXTENSION));
        $imported_uri = substr($imported_uri, 0, -$extension);
        $exported_uri = substr($exported_data['default']['uri'][0]['value'], 0, -$extension);
        $this->assertStringStartsWith($imported_uri, $exported_uri);
        // We know they match; no need to consider them further.
        unset(
          $exported_data['default']['uri'][0]['value'],
          $imported_data['default']['uri'][0]['value'],
        );
      }

      // This specific node is special -- it is always reassigned to the current
      // user during import, because its owner does not exist. Therefore, the
      // current user is who it should be referring to when exported.
      if ($uuid === '7f1dd75a-0be2-4d3b-be5d-9d1a868b9267') {
        $new_owner = $this->adminUser->uuid();
        $exported_data['_meta']['depends'] = $imported_data['_meta']['depends'] = [$new_owner => 'user'];
        $exported_data['default']['uid'][0]['entity'] = $imported_data['default']['uid'][0]['entity'] = $new_owner;
      }

      self::recursiveSortByKey($exported_data);
      self::recursiveSortByKey($imported_data);
      $this->assertSame($imported_data, $exported_data);
    }
  }

  /**
   * Tests that an exported user account can be logged in with after import.
   */
  public function testExportedPasswordIsPreserved(): void {
    $account = $this->createUser();
    $this->assertNotEmpty($account->passRaw);

    // Export the account to temporary file.
    $process = $this->runDrupalCommand([
      'content:export',
      'user',
      $account->id(),
    ]);
    $this->assertSame(0, $process->wait());
    $dir = 'public://content';
    mkdir($dir);
    file_put_contents($dir . '/user.yml', $process->getOutput());

    // Delete the account and re-import it.
    $account->delete();
    $this->container->get(Importer::class)
      ->importContent(new Finder($dir));
    // Ensure the import succeeded, and that we can log in with the imported
    // account. We want to use the standard login form, rather than a one-time
    // login link, to ensure the password is preserved.
    $this->assertIsObject(user_load_by_name($account->getAccountName()));
    $this->useOneTimeLoginLinks = FALSE;
    $this->drupalLogin($account);
    $this->assertSession()->addressMatches('/\/user\/[0-9]+$/');
  }

  /**
   * Tests exporting a single entity to a directory with attachments.
   */
  public function testExportSingleEntityToDirectory(): void {
    $file = $this->container->get(EntityRepositoryInterface::class)
      ->loadEntityByUuid('file', '7fb09f9f-ba5f-4db4-82ed-aa5ccf7d425d');
    $this->assertInstanceOf(File::class, $file);

    $dir = 'public://export-content';
    $process = $this->runDrupalCommand([
      'content:export',
      'file',
      $file->id(),
      "--dir=$dir",
    ]);
    $this->assertSame(0, $process->wait());
    $this->assertStringContainsString('The file "' . $file->label() . '" was exported to ', $process->getOutput());
    $this->assertFileExists($dir . '/file/' . $file->uuid() . '.yml');
    $this->assertFileExists($dir . '/file/' . $file->getFilename());
  }

  /**
   * Tests exporting a piece of content with its dependencies.
   */
  public function testExportWithDependencies(): void {
    $image_uri = $this->getRandomGenerator()
      ->image(uniqid('public://') . '.png', '200x200', '300x300');
    $file = File::create(['uri' => $image_uri]);
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'field_media_image' => [$file],
    ]);
    $media->save();
    $this->createEntityReferenceField('node', 'article', 'field_media', 'Media', 'media', selection_handler_settings: [
      'target_bundles' => ['image' => 'image'],
    ]);
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'field_tags' => Term::load(1),
      'field_media' => $media,
      'uid' => User::load(2),
    ]);
    $command = ['content:export', 'node', $node->id(), '--with-dependencies'];

    // With no `--dir` option, we should get an error.
    $process = $this->runDrupalCommand($command);
    $this->assertGreaterThan(0, $process->wait());
    $this->assertStringContainsString('The --dir option is required when exporting with dependencies.', $process->getErrorOutput());

    $command[] = "--dir=public://content";
    $process = $this->runDrupalCommand($command);
    $this->assertSame(0, $process->wait());
    $expected_output_dir = $this->container->get(FileSystemInterface::class)
      ->realpath('public://content');
    $this->assertStringContainsString('5 items were exported to ', $process->getOutput());

    $this->assertFileExists($expected_output_dir . '/node/' . $node->uuid() . '.yml');
    $this->assertFileExists($expected_output_dir . '/taxonomy_term/' . $node->field_tags[0]->entity->uuid() . '.yml');
    $this->assertFileExists($expected_output_dir . '/media/' . $media->uuid() . '.yml');
    $this->assertFileExists($expected_output_dir . '/file/' . $file->uuid() . '.yml');
    $this->assertFileExists($expected_output_dir . '/user/' . $node->getOwner()->uuid() . '.yml');

    // The physical file should have been copied too.
    $original_file_hash = hash_file('sha256', $file->getFileUri());
    $this->assertIsString($original_file_hash);
    $exported_file_hash = hash_file('sha256', $expected_output_dir . '/file/' . $file->getFilename());
    $this->assertIsString($exported_file_hash);
    $this->assertTrue(hash_equals($original_file_hash, $exported_file_hash));
  }

  /**
   * Tests that the exporter handles circular dependencies gracefully.
   */
  public function testCircularDependency(): void {
    $this->createEntityReferenceField('node', 'article', 'field_related', 'Related Content', 'node', selection_handler_settings: [
      'target_bundles' => ['page' => 'page'],
    ]);
    $this->createEntityReferenceField('node', 'page', 'field_related', 'Related Content', 'node', selection_handler_settings: [
      'target_bundles' => ['article' => 'article'],
    ]);

    $page = $this->drupalCreateNode(['type' => 'page']);
    $article = $this->drupalCreateNode([
      'type' => 'article',
      'field_related' => $page,
    ]);
    $page->set('field_related', $article)->save();

    $command = [
      'content:export',
      'node',
      $page->id(),
      '--with-dependencies',
      '--dir=public://content',
    ];
    // If the export takes more than 10 seconds, it's probably stuck in an
    // infinite loop.
    $process = $this->runDrupalCommand($command, 10);
    $this->assertSame(0, $process->wait());

    $destination = 'public://content/node';
    $this->assertFileExists($destination . '/' . $page->uuid() . '.yml');
    $this->assertFileExists($destination . '/' . $article->uuid() . '.yml');
  }

  /**
   * Tests that the exporter handles missing dependencies gracefully.
   */
  public function testMissingDependenciesAreLogged(): void {
    $this->createEntityReferenceField('node', 'article', 'field_related', 'Related Content', 'node', selection_handler_settings: [
      'target_bundles' => ['page' => 'page'],
    ]);

    $page = $this->drupalCreateNode(['type' => 'page']);
    $page_id = $page->id();
    $article = $this->drupalCreateNode([
      'type' => 'article',
      'field_related' => $page,
    ]);
    $page->delete();

    // We need to clear the caches or the related content is included because
    // the article is cached.
    $entity_storage = $this->container->get(EntityTypeManagerInterface::class)->getStorage('node');
    $entity_storage->resetCache([$page->id(), $article->id()]);
    $article = $entity_storage->load($article->id());

    /** @var \Drupal\Core\DefaultContent\Exporter $exporter */
    $exporter = $this->container->get(Exporter::class);
    $logger = new TestLogger();
    $exporter->setLogger($logger);
    $dependencies = $exporter->export($article)->metadata->getDependencies();
    // The export succeeded without throwing an exception, and depends only on
    // the author. The page should not be among the dependencies.
    $author_uuid = $this->adminUser->uuid();
    $this->assertCount(1, $dependencies);
    $this->assertSame(['user', $author_uuid], $dependencies[0]);

    // The invalid reference should have been logged.
    $predicate = function (array $record) use ($page_id, $article): bool {
      return (
        $record['message'] === 'Failed to export reference to @target_type %missing_id referenced by %field on @entity_type %label because the referenced @target_type does not exist.' &&
        $record['context']['@target_type'] === 'content item' &&
        $record['context']['%missing_id'] === $page_id &&
        $record['context']['%field'] === 'Related Content' &&
        $record['context']['@entity_type'] === 'content item' &&
        $record['context']['%label'] === $article->label()
      );
    };
    $this->assertTrue($logger->hasRecordThatPasses($predicate, LogLevel::WARNING));
  }

  /**
   * Tests exporting file entities without an accompanying physical file.
   */
  public function testExportFileEntityWithMissingPhysicalFile(): void {
    $file = $this->container->get(EntityRepositoryInterface::class)
      ->loadEntityByUuid('file', '2b8e0616-3ef0-4a91-8cfb-b31d9128f9f8');
    $this->assertInstanceOf(File::class, $file);
    $this->assertFileDoesNotExist($file->getFileUri());

    $logger = new TestLogger();
    $this->container->get('logger.factory')->addLogger($logger);

    /** @var \Drupal\Core\DefaultContent\Exporter $exporter */
    $exporter = $this->container->get(Exporter::class);
    $attachments = $exporter->export($file)->metadata->getAttachments();
    // The export succeeded without throwing an exception, but the physical file
    // does not exist, so it should not have been attached.
    $this->assertEmpty($attachments);

    // The problem should have been logged.
    $predicate = function (array $record) use ($file): bool {
      return (
        $record['level'] === RfcLogLevel::WARNING &&
        $record['message'] === 'The file (%uri) associated with file entity %name does not exist.' &&
        $record['context']['%uri'] === $file->getFileUri() &&
        $record['context']['%name'] === $file->label()
      );
    };
    $this->assertTrue($logger->hasRecordThatPasses($predicate));
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $data
   *   The array to sort.
   */
  private static function recursiveSortByKey(array &$data): void {
    // If the array is a list, it is by definition already sorted.
    if (!array_is_list($data)) {
      ksort($data);
    }
    foreach ($data as &$value) {
      if (is_array($value)) {
        self::recursiveSortByKey($value);
      }
    }
  }

}
