<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Recipe\Finder;
use Drupal\Core\Recipe\Recipe;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Tests the recipe finder.
 */
#[CoversClass(Finder::class)]
#[Group('Recipe')]
#[RunTestsInSeparateProcesses]
class RecipeFinderTest extends KernelTestBase {

  /**
   * A directory created by the test, which is removed in tearDown().
   */
  private ?string $temporaryDirectory = NULL;

  /**
   * Tests that the finder yields Recipe objects.
   */
  public function testFinderYieldsRecipes(): void {
    foreach (new Finder($this->root . '/core/recipes') as $recipe) {
      $this->assertInstanceOf(Recipe::class, $recipe);
    }
  }

  /**
   * Tests that certain configuration options are disallowed.
   */
  #[TestWith([
    'name',
    ['alternate.yml'],
    'Recipe discovery can only scan for files named `recipe.yml`.',
  ])]
  #[TestWith([
    'depth',
    [2],
    'Recipe discovery cannot recurse into the file system.',
  ])]
  #[TestWith([
    'directories',
    [],
    'Recipe discovery can only scan for files.',
  ])]
  public function testForbiddenMethods(string $method, array $arguments, string $expected_exception): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageIs($expected_exception);

    $finder = new Finder($this->root . '/core/recipes');
    $finder->$method(...$arguments);
  }

  /**
   * Tests that invalid recipes can be safely skipped, with a log message.
   */
  public function testSkipInvalidRecipe(): void {
    $finder = new Finder(
      $this->root . '/core/tests/fixtures/recipes',
      TRUE,
      new TestLogger(),
    );
    // Convert to an array so that the generator gets run.
    iterator_to_array($finder);
    $this->assertTrue($finder->logger->hasErrorThatContains('foo: !php/enum Drupal\config_enum_test\DoesNotExistEnumValue::No'));
  }

  /**
   * Tests that recipes can be filtered in custom ways.
   */
  public function testCustomFiltering(): void {
    $finder = new Finder($this->root . '/core/recipes');

    // By default, we'll find the example recipe.
    $this->assertContains(
      'Example',
      array_column(iterator_to_array($finder), 'name'),
    );
    // But not if we explicitly filter it out.
    $finder->notPath('example');
    $this->assertNotContains(
      'Example',
      array_column(iterator_to_array($finder), 'name'),
    );
  }

  /**
   * Tests that there is an error if the finder is given invalid directories.
   *
   * @param string|array $directories
   *   The directory, or directories, to instantiate the finder with.
   */
  #[TestWith([''])]
  #[TestWith(['nonsense'])]
  #[TestWith([['core', 'nonsense']])]
  public function testInvalidDirectoriesRejected(string|array $directories): void {
    $error_directory = array_last((array) $directories);
    $this->expectException(DirectoryNotFoundException::class);
    $this->expectExceptionMessageIs("The \"$error_directory\" directory does not exist.");
    new Finder($directories);
  }

  /**
   * Tests that there is an error if Composer is misconfigured for recipes.
   */
  public function testUnknownComposerRecipeDirectory(): void {
    // By default, core's `composer.json` does not set up a recipe install path.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageIs("No recipe install location is configured in $this->root/composer.json.");
    Finder::getComposerRecipesDirectory();
  }

  /**
   * Tests that the Composer recipe directory is read correctly.
   */
  public function testComposerRecipeDirectory(): void {
    $temp_dir = $this->createTemporaryDirectory();
    mkdir($temp_dir . '/recipes/test', recursive: TRUE);

    $contents = Json::encode([
      'extra' => [
        'installer-paths' => [
          'recipes/{$name}' => ['type:' . Recipe::COMPOSER_PROJECT_TYPE],
        ],
      ],
    ]);
    file_put_contents($temp_dir . '/composer.json', $contents);

    $this->assertSame($temp_dir . '/recipes/{$name}', Finder::getComposerRecipesDirectory($temp_dir));
    // The project root is now statically cached, so if we put a recipe in
    // there, it should be discovered.
    file_put_contents($temp_dir . '/recipes/test/recipe.yml', 'name: Test!');
    $names = array_column(iterator_to_array(new Finder()), 'name');
    $this->assertContains('Test!', $names);
    // Core recipes should be discovered too, by default.
    $this->assertContains('Administrator role', $names);
  }

  /**
   * Tests that invalid recipes throw an exception by default.
   */
  public function testInvalidRecipesThrow(): void {
    $finder = new Finder($this->root . '/core/tests/fixtures/recipes');
    // This is a parse error, so we don't care about the exception type.
    $this->expectExceptionMessageIs('The enum "Drupal\config_enum_test\DoesNotExistEnumValue" is not defined at line 9 (near "foo: !php/enum Drupal\config_enum_test\DoesNotExistEnumValue::No").');
    iterator_to_array($finder);
  }

  /**
   * Tests scanning specific directories for recipes.
   */
  public function testScanSpecificDirectories(): void {
    // The finder can scan a single directory...
    $finder = new Finder($this->root . '/core/recipes', TRUE);
    $names = array_column(iterator_to_array($finder), 'name');
    $this->assertContains('Administrator role', $names);
    $this->assertContains('Editorial workflow', $names);
    $this->assertNotContains('Input Test', $names);

    // ...or several.
    $finder = new Finder([
      $this->root . '/core/recipes',
      $this->root . '/core/tests/fixtures/recipes',
    ], TRUE);
    $names = array_column(iterator_to_array($finder), 'name');
    $this->assertContains('Administrator role', $names);
    $this->assertContains('Editorial workflow', $names);
    $this->assertContains('Input Test', $names);
  }

  /**
   * Tests that the recipe finder follows symlinks.
   */
  public function testDiscoveryFollowsSymlinks(): void {
    $cookbook_dir = $this->createTemporaryDirectory();
    $target = $this->root . '/core/recipes/administrator_role';
    (new SymfonyFilesystem())->symlink($target, $cookbook_dir . '/recipe_link');

    $recipes = iterator_to_array(new Finder($cookbook_dir));
    $this->assertCount(1, $recipes);
    $this->assertSame('Administrator role', reset($recipes)->name);
  }

  /**
   * Creates a unique directory within the OS temporary directory.
   *
   * This isolates directory scanning from other tests, which can add and remove
   * directories in the OS temporary directory at the same time. The new
   * directory is removed when the test ends.
   */
  private function createTemporaryDirectory(): string {
    $directory = FileSystem::getOsTemporaryDirectory() . '/recipe_finder_test' . $this->databasePrefix;
    $this->assertTrue(mkdir($directory));
    $this->temporaryDirectory = $directory;
    return $directory;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->temporaryDirectory) {
      (new SymfonyFilesystem())->remove($this->temporaryDirectory);
    }
    parent::tearDown();
  }

}
