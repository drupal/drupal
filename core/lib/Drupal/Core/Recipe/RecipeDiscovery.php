<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

/**
 * @internal
 *   This API is experimental.
 */
final class RecipeDiscovery {

  /**
   * Constructs a recipe discovery object.
   *
   * @param string $path
   *   The path will be searched folders containing a recipe.yml. There will be
   *   no traversal further into the directory structure.
   */
  public function __construct(protected string $path) {
  }

  /**
   * Gets a recipe object.
   *
   * @param string $name
   *   The machine name of the recipe to find.
   *
   * @return \Drupal\Core\Recipe\Recipe
   *   The recipe object.
   *
   * @throws \Drupal\Core\Recipe\UnknownRecipeException
   *   Thrown when the recipe cannot be found.
   */
  public function getRecipe(string $name): Recipe {
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
      $path = $this->path . "/$name/recipe.yml";
    }

    if (file_exists($path)) {
      return Recipe::createFromDirectory(dirname($path));
    }
    $search_path = dirname($path, 2);
    throw new UnknownRecipeException($name, $search_path, sprintf("Can not find the %s recipe, search path: %s", $name, $search_path));
  }

}
