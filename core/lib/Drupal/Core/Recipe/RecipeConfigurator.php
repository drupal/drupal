<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

/**
 * @internal
 *   This API is experimental.
 */
final class RecipeConfigurator {

  /**
   * @var \Drupal\Core\Recipe\Recipe[]
   */
  public readonly array $recipes;

  /**
   * A cache of already-loaded recipes, keyed by path.
   *
   * @var \Drupal\Core\Recipe\Recipe[]
   */
  private static array $cache = [];

  /**
   * @param string[] $recipes
   *   A list of recipes for a recipe to apply. The recipes will be applied in
   *   the order listed.
   * @param string $include_path
   *   The recipe's include path.
   */
  public function __construct(array $recipes, string $include_path) {
    $this->recipes = array_map(fn(string $name) => static::getIncludedRecipe($include_path, $name), $recipes);
  }

  /**
   * Gets an included recipe object.
   *
   * @param string $include_path
   *   The recipe's include path.
   * @param string $name
   *   The machine name of the recipe to get.
   *
   * @return \Drupal\Core\Recipe\Recipe
   *   The recipe object.
   *
   * @throws \Drupal\Core\Recipe\UnknownRecipeException
   *   Thrown when the recipe cannot be found.
   */
  public static function getIncludedRecipe(string $include_path, string $name): Recipe {
    // In order to allow recipes to include core provided recipes, $name can be
    // a Drupal root relative path to a recipe folder. For example, a recipe can
    // include the core provided 'article_tags' recipe by listing the recipe as
    // 'core/recipes/article_tags'. It is strongly recommended not to rely on
    // relative paths for including recipes. Required recipes should be put in
    // the same parent directory as the recipe being applied. Note, only linux
    // style directory separators are supported. PHP on Windows can resolve the
    // mix of directory separators.
    if (str_contains($name, '/')) {
      $path = \Drupal::root() . "/$name/recipe.yml";
    }
    else {
      $path = $include_path . "/$name/recipe.yml";
    }

    if (array_key_exists($path, static::$cache)) {
      return static::$cache[$path];
    }
    if (file_exists($path)) {
      return static::$cache[$path] = Recipe::createFromDirectory(dirname($path));
    }
    $search_path = dirname($path, 2);
    throw new UnknownRecipeException($name, $search_path, sprintf("Can not find the %s recipe, search path: %s", $name, $search_path));
  }

  /**
   * Returns all the recipes installed by this recipe.
   *
   * @return \Drupal\Core\Recipe\Recipe[]
   *   An array of all the recipes being installed.
   */
  private function listAllRecipes(): array {
    $recipes = [];
    foreach ($this->recipes as $recipe) {
      $recipes[] = $recipe;
      $recipes = array_merge($recipes, $recipe->recipes->listAllRecipes());
    }
    return array_values(array_unique($recipes, SORT_REGULAR));
  }

  /**
   * List all the extensions installed by this recipe and its dependencies.
   *
   * @return string[]
   *   All the modules and themes that will be installed by the current
   *   recipe and all the recipes it depends on.
   */
  public function listAllExtensions(): array {
    $extensions = [];
    foreach ($this->listAllRecipes() as $recipe) {
      $extensions = array_merge($extensions, $recipe->install->modules, $recipe->install->themes);
    }
    return array_values(array_unique($extensions));
  }

}
