<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\DefaultContent;

use Drupal\Core\DefaultContent\Existing;
use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\DefaultContent\ImportException;
use Drupal\Core\File\FileExists;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Content Import.
 */
#[Group('DefaultContent')]
#[Group('Recipe')]
#[Group('#slow')]
#[CoversClass(Importer::class)]
#[RunTestsInSeparateProcesses]
class ContentImportAlreadyExistsTest extends BrowserTestBase {

  use RecipeTestTrait;

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
    'workspaces',
  ];

  /**
   * The directory with the source data.
   */
  private readonly string $contentDir;

  /**
   * The admin account.
   */
  private UserInterface $adminAccount;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminAccount = $this->setUpCurrentUser(admin: TRUE);

    // Apply the recipe that sets up the fields and configuration for our
    // default content.
    $fixtures_dir = $this->getDrupalRoot() . '/core/tests/fixtures';
    $this->applyRecipe($fixtures_dir . '/recipes/default_content_base');

    $this->contentDir = $fixtures_dir . '/default_content';
    \Drupal::service('file_system')->copy($this->contentDir . '/file/druplicon_copy.png', $this->publicFilesDirectory . '/druplicon_copy.png', FileExists::Error);
  }

  /**
   * @return array<array<mixed>>
   *   An array of test cases, each containing an existing entity handling mode.
   */
  public static function providerImportEntityThatAlreadyExists(): array {
    return [
      [Existing::Error],
      [Existing::Skip],
    ];
  }

  /**
 * Tests import entity that already exists.
 */
  #[DataProvider('providerImportEntityThatAlreadyExists')]
  public function testImportEntityThatAlreadyExists(Existing $existing): void {
    $this->drupalCreateUser(values: ['uuid' => '94503467-be7f-406c-9795-fc25baa22203']);

    if ($existing === Existing::Error) {
      $this->expectException(ImportException::class);
      $this->expectExceptionMessage('user 94503467-be7f-406c-9795-fc25baa22203 already exists.');
    }

    $this->container->get(Importer::class)
      ->importContent(new Finder($this->contentDir), $existing);
  }

}
