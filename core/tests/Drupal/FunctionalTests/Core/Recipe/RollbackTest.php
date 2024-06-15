<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Recipe\Recipe;
use Drupal\Tests\BrowserTestBase;

/**
 * @group Recipe
 */
class RollbackTest extends BrowserTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   *
   * Disable strict config schema because this test explicitly makes the
   * recipe system save invalid config, to prove that it validates it after
   * the fact and raises an error.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * @testWith ["invalid_config", "core.date_format.invalid"]
   *           ["recipe_depend_on_invalid", "core.date_format.invalid"]
   *           ["recipe_depend_on_invalid_config_and_valid_modules", "core.date_format.invalid"]
   */
  public function testRollbackForInvalidConfig(string $recipe_fixture, string $expected_invalid_config_name): void {
    $expected_core_extension_modules = $this->config('core.extension')->get('module');

    /** @var string $recipe_fixture */
    $recipe_fixture = realpath(__DIR__ . "/../../../../fixtures/recipes/$recipe_fixture");
    $process = $this->applyRecipe($recipe_fixture, 1);
    $this->assertStringContainsString("There were validation errors in $expected_invalid_config_name:", $process->getErrorOutput());
    $this->assertCheckpointsExist([
      "Backup before the '" . Recipe::createFromDirectory($recipe_fixture)->name . "' recipe.",
    ]);

    // @see invalid_config
    $date_formats = DateFormat::loadMultiple(['valid', 'invalid']);
    $this->assertEmpty($date_formats, "The recipe's imported config was not rolled back.");

    // @see recipe_depend_on_invalid_config_and_valid_module
    $this->assertSame($expected_core_extension_modules, $this->config('core.extension')->get('module'));
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
