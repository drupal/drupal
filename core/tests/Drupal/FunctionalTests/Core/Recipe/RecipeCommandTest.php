<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Recipe\RecipeCommand
 * @group Recipe
 *
 * BrowserTestBase is used for a proper Drupal install.
 */
class RecipeCommandTest extends BrowserTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Disable strict config schema because this test explicitly makes the
   * recipe system save invalid config, to prove that it validates it after
   * the fact and raises an error.
   */
  protected $strictConfigSchema = FALSE;

  public function testRecipeCommand(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('node'), 'The node module is not installed');
    $this->assertCheckpointsExist([]);

    $process = $this->applyRecipe('core/tests/fixtures/recipes/install_node_with_config');
    $this->assertSame(0, $process->getExitCode());
    $this->assertStringContainsString("Applied Install node with config recipe.", $process->getErrorOutput());
    $this->assertStringContainsString('Install node with config applied successfully', $process->getOutput());
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('node'), 'The node module is installed');
    $this->assertCheckpointsExist(["Backup before the 'Install node with config' recipe."]);

    // Ensure recipes can be applied without affecting pre-existing checkpoints.
    $process = $this->applyRecipe('core/tests/fixtures/recipes/install_two_modules');
    $this->assertSame(0, $process->getExitCode());
    $this->assertStringContainsString("Applied Install two modules recipe.", $process->getErrorOutput());
    $this->assertStringContainsString('Install two modules applied successfully', $process->getOutput());
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('node'), 'The node module is installed');
    $this->assertCheckpointsExist([
      "Backup before the 'Install node with config' recipe.",
      "Backup before the 'Install two modules' recipe.",
    ]);

    // Ensure recipes that fail have an exception message.
    $process = $this->applyRecipe('core/tests/fixtures/recipes/invalid_config', 1);
    $this->assertStringContainsString("There were validation errors in core.date_format.invalid", $process->getErrorOutput());
    $this->assertCheckpointsExist([
      "Backup before the 'Install node with config' recipe.",
      "Backup before the 'Install two modules' recipe.",
      // Although the recipe command tried to create a checkpoint, it did not
      // actually happen, because of https://drupal.org/i/3408523.
    ]);

    // Create a checkpoint so we can test what happens when a recipe does not
    // create a checkpoint before applying.
    \Drupal::service('config.storage.checkpoint')->checkpoint('Test log message');
    $process = $this->applyRecipe('core/tests/fixtures/recipes/no_extensions');
    $this->assertSame(0, $process->getExitCode());
    $this->assertStringContainsString("Applied No extensions recipe.", $process->getErrorOutput());
    $this->assertCheckpointsExist([
      "Backup before the 'Install node with config' recipe.",
      "Backup before the 'Install two modules' recipe.",
      "Test log message",
    ]);
    $this->assertStringContainsString('[notice] A backup checkpoint was not created because nothing has changed since the "Test log message" checkpoint was created.', $process->getOutput());
  }

  /**
   * Tests that errors during config rollback won't steamroll validation errors.
   */
  public function testExceptionOnRollback(): void {
    $process = $this->applyRecipe('core/tests/fixtures/recipes/config_rollback_exception', 1);

    // The error from the config importer should be visible.
    $output = $process->getOutput();
    $this->assertStringContainsString('There were errors validating the config synchronization.', $output);
    $this->assertStringContainsString('Provides a filter plugin that is in use', $output);
    // And the exception that actually *caused* the error should be visible too.
    $this->assertStringContainsString('There were validation errors in system.image:', $process->getErrorOutput());
  }

  /**
   * Tests the recipe command with a non-existent directory.
   */
  public function testErrorOnNonExistentDirectory(): void {
    $process = $this->applyRecipe('core/tests/fixtures/recipes/does_not_exist', 1);

    // The directory error should be the only error visible.
    $output = trim(preg_replace('/\s+/', ' ', $process->getOutput()));
    $this->assertSame('[ERROR] The supplied path core/tests/fixtures/recipes/does_not_exist is not a directory', $output);
    $this->assertEmpty($process->getErrorOutput());
  }

  /**
   * Asserts that the current set of checkpoints matches the given labels.
   *
   * @param string[] $expected_labels
   *   The labels of every checkpoint that is expected to exist currently, in
   *   the expected order.
   */
  private function assertCheckpointsExist(array $expected_labels): void {
    $checkpoints = \Drupal::service('config.checkpoints');
    $labels = array_map(fn (Checkpoint $c) => $c->label, iterator_to_array($checkpoints));
    $this->assertSame($expected_labels, array_values($labels));
  }

}
