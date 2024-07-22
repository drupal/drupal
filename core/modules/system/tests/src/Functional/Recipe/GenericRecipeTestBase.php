<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Recipe;

use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Runs a series of generic tests for one recipe.
 */
abstract class GenericRecipeTestBase extends BrowserTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Returns the path of the recipe under test.
   *
   * @return string
   *   The absolute path of the recipe that contains this test.
   */
  protected function getRecipePath(): string {
    // Assume this test in located in RECIPE_DIR/tests/src/Functional.
    return dirname((new \ReflectionObject($this))->getFileName(), 4);
  }

  /**
   * Applies the recipe under test.
   */
  protected function doApply(): void {
    $this->applyRecipe($this->getRecipePath());
  }

  /**
   * Tests that this recipe can be applied multiple times.
   */
  public function testRecipeCanBeApplied(): void {
    $this->setUpCurrentUser(admin: TRUE);
    $this->doApply();
    // Apply the recipe again to prove that it is idempotent.
    $this->doApply();
  }

}
