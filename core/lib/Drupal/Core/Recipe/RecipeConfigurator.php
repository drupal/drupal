<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

/**
 * @internal
 *   This API is experimental.
 */
final class RecipeConfigurator {

  public readonly array $recipes;

  /**
   * @param string[] $recipes
   *   A list of recipes for a recipe to apply. The recipes will be applied in
   *   the order listed.
   * @param \Drupal\Core\Recipe\RecipeDiscovery $recipeDiscovery
   *   Recipe discovery.
   */
  public function __construct(array $recipes, RecipeDiscovery $recipeDiscovery) {
    $this->recipes = array_map([$recipeDiscovery, 'getRecipe'], $recipes);
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
