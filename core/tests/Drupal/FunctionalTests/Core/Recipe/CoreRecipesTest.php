<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Finder\Finder;

/**
 * Tests applying all core-provided recipes on top of the Empty profile.
 *
 * @group Recipe
 * @group #slow
 */
class CoreRecipesTest extends BrowserTestBase {

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
   * The data provider for apply recipe test.
   *
   * @return iterable<array<string>>
   *   An iterable containing paths to recipe files.
   */
  public static function providerApplyRecipe(): iterable {
    $finder = Finder::create()
      ->in([
        static::getDrupalRoot() . '/core/recipes',
      ])
      ->directories()
      // Recipes can't contain other recipes, so we don't need to search in
      // subdirectories.
      ->depth(0)
      // The Example recipe is for documentation only, and cannot be applied.
      ->notName(['example']);

    $scenarios = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $recipe */
    foreach ($finder as $recipe) {
      $name = $recipe->getBasename();
      $scenarios[$name] = [
        $recipe->getPathname(),
      ];
    }
    return $scenarios;
  }

  /**
   * Test the recipe apply.
   *
   * @param string $path
   *   The path to the recipe file.
   *
   * @dataProvider providerApplyRecipe
   */
  public function testApplyRecipe(string $path): void {
    $this->setUpCurrentUser(admin: TRUE);
    $this->applyRecipe($path);
  }

}
