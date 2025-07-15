<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\DefaultContent;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DefaultContent\ContentExportCommand;
use Drupal\Core\DefaultContent\Exporter;
use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests exporting content in YAML format.
 */
#[CoversClass(ContentExportCommand::class)]
#[CoversClass(Exporter::class)]
#[Group('DefaultContent')]
#[Group('Recipe')]
class ContentExportTest extends BrowserTestBase {

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
      $this->assertSame(0, $process->wait(), $process->getErrorOutput());

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
