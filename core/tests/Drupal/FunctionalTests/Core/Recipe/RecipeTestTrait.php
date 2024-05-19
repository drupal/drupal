<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Recipe\Recipe;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Contains helper methods for interacting with recipes in functional tests.
 */
trait RecipeTestTrait {

  /**
   * Creates a recipe in a temporary directory.
   *
   * @param string|array<mixed> $data
   *   The contents of recipe.yml. If passed as an array, will be encoded to
   *   YAML.
   * @param string|null $machine_name
   *   The machine name for the recipe. Will be used as the directory name.
   *
   * @return \Drupal\Core\Recipe\Recipe
   *   The recipe object.
   */
  protected function createRecipe(string|array $data, ?string $machine_name = NULL): Recipe {
    if (is_array($data)) {
      $data = Yaml::encode($data);
    }
    $recipes_dir = $this->siteDirectory . '/recipes';
    if ($machine_name === NULL) {
      $dir = uniqid($recipes_dir . '/');
    }
    else {
      $dir = $recipes_dir . '/' . $machine_name;
    }
    mkdir($dir, recursive: TRUE);
    file_put_contents($dir . '/recipe.yml', $data);

    return Recipe::createFromDirectory($dir);
  }

  /**
   * Applies a recipe to the site.
   *
   * @param string $path
   *   The path of the recipe to apply. Must be a directory.
   * @param int $expected_exit_code
   *   The expected exit code of the `drupal recipe` process. Defaults to 0,
   *   which indicates that no error occurred.
   *
   * @return \Symfony\Component\Process\Process
   *   The `drupal recipe` command process, after having run.
   */
  protected function applyRecipe(string $path, int $expected_exit_code = 0): Process {
    assert($this instanceof BrowserTestBase);

    $arguments = [
      (new PhpExecutableFinder())->find(),
      'core/scripts/drupal',
      'recipe',
      $path,
    ];
    $process = (new Process($arguments))
      ->setWorkingDirectory($this->getDrupalRoot())
      ->setEnv([
        'DRUPAL_DEV_SITE_PATH' => $this->siteDirectory,
        // Ensure that the command boots Drupal into a state where it knows it's
        // a test site.
        // @see drupal_valid_test_ua()
        'HTTP_USER_AGENT' => drupal_generate_test_ua($this->databasePrefix),
      ])
      ->setTimeout(500);

    $process->run();
    $this->assertSame($expected_exit_code, $process->getExitCode(), $process->getErrorOutput());
    // Applying a recipe:
    // - creates new checkpoints, hence the "state" service in the test runner
    //   is outdated
    // - may install modules, which would cause the entire container in the test
    //   runner to be outdated.
    // Hence the entire environment must be rebuilt for assertions to target the
    // actual post-recipe-application result.
    // @see \Drupal\Core\Config\Checkpoint\LinearHistory::__construct()
    $this->rebuildAll();
    return $process;
  }

}
