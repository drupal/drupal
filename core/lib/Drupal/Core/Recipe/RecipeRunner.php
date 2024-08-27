<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DefaultContent\Existing;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\DefaultContent\Finder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Applies a recipe.
 *
 * This class is currently static and use \Drupal::service() in order to put off
 * having to solve issues caused by container rebuilds during module install and
 * configuration import.
 *
 * @internal
 *   This API is experimental.
 *
 * @todo https://www.drupal.org/i/3439717 Determine if there is a better to
 *   inject and re-inject services.
 */
final class RecipeRunner {

  /**
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to apply.
   */
  public static function processRecipe(Recipe $recipe): void {
    static::processRecipes($recipe->recipes);
    static::processInstall($recipe->install, $recipe->config->getConfigStorage());
    static::processConfiguration($recipe);
    static::processContent($recipe->content);
    static::triggerEvent($recipe);
  }

  /**
   * Triggers the RecipeAppliedEvent.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to apply.
   * @param array<mixed>|null $context
   *   The batch context if called by a batch.
   */
  public static function triggerEvent(Recipe $recipe, ?array &$context = NULL): void {
    $event = new RecipeAppliedEvent($recipe);
    \Drupal::service(EventDispatcherInterface::class)->dispatch($event);
    $context['message'] = t('Applied %recipe recipe.', ['%recipe' => $recipe->name]);
    $context['results']['recipe'][] = $recipe->name;
  }

  /**
   * Applies any recipes listed by the recipe.
   *
   * @param \Drupal\Core\Recipe\RecipeConfigurator $recipes
   *   The list of recipes to apply.
   */
  protected static function processRecipes(RecipeConfigurator $recipes): void {
    foreach ($recipes->recipes as $recipe) {
      static::processRecipe($recipe);
    }
  }

  /**
   * Installs the extensions.
   *
   * @param \Drupal\Core\Recipe\InstallConfigurator $install
   *   The list of extensions to install.
   * @param \Drupal\Core\Config\StorageInterface $recipeConfigStorage
   *   The recipe's configuration storage. Used to override extension provided
   *   configuration.
   */
  protected static function processInstall(InstallConfigurator $install, StorageInterface $recipeConfigStorage): void {
    foreach ($install->modules as $name) {
      static::installModule($name, $recipeConfigStorage);
    }

    // Themes can depend on modules so have to be installed after modules.
    foreach ($install->themes as $name) {
      static::installTheme($name, $recipeConfigStorage);
    }
  }

  /**
   * Creates configuration and applies configuration actions.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe being applied.
   */
  protected static function processConfiguration(Recipe $recipe): void {
    $config_installer = new RecipeConfigInstaller(
      \Drupal::service('config.factory'),
      \Drupal::service('config.storage'),
      \Drupal::service('config.typed'),
      \Drupal::service('config.manager'),
      \Drupal::service('event_dispatcher'),
      NULL,
      \Drupal::service('extension.path.resolver'));

    $config = $recipe->config;
    // Create configuration that is either supplied by the recipe or listed in
    // the config.import section that does not exist.
    $config_installer->installRecipeConfig($config);

    if (!empty($config->config['actions'])) {
      $values = $recipe->input->getValues();
      // Wrap the replacement strings with `${` and `}`, which is a fairly
      // common style of placeholder.
      $keys = array_map(fn ($k) => sprintf('${%s}', $k), array_keys($values));
      $replace = array_combine($keys, $values);

      // Process the actions.
      /** @var \Drupal\Core\Config\Action\ConfigActionManager $config_action_manager */
      $config_action_manager = \Drupal::service('plugin.manager.config_action');
      foreach ($config->config['actions'] as $config_name => $actions) {
        foreach ($actions as $action_id => $data) {
          $config_action_manager->applyAction($action_id, $config_name, static::replaceInputValues($data, $replace));
        }
      }
    }
  }

  /**
   * Creates content contained in a recipe.
   *
   * @param \Drupal\Core\DefaultContent\Finder $content
   *   The content finder object for the recipe.
   */
  protected static function processContent(Finder $content): void {
    /** @var \Drupal\Core\DefaultContent\Importer $importer */
    $importer = \Drupal::service(Importer::class);
    $importer->setLogger(\Drupal::logger('recipe'));
    $importer->importContent($content, Existing::Skip);
  }

  /**
   * Converts a recipe into a series of batch operations.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to convert to batch operations.
   *
   * @return array<int, array{0: callable, 1: array{mixed}}>
   *   The array of batch operations. Each value is an array with two values.
   *   The first value is a callable and the second value are the arguments to
   *   pass to the callable.
   *
   * @see \Drupal\Core\Batch\BatchBuilder::addOperation()
   */
  public static function toBatchOperations(Recipe $recipe): array {
    $modules = [];
    $themes = [];
    $recipes = [];
    return static::toBatchOperationsRecipe($recipe, $recipes, $modules, $themes);
  }

  /**
   * Helper method to convert a recipe to batch operations.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to convert to batch operations.
   * @param string[] $recipes
   *   The paths of the recipes that have already been converted to batch operations.
   * @param string[] $modules
   *   The modules that will already be installed due to previous recipes in the
   *   batch.
   * @param string[] $themes
   *   The themes that will already be installed due to previous recipes in the
   *   batch.
   *
   * @return array<int, array{0: callable, 1: array{mixed}}>
   *   The array of batch operations. Each value is an array with two values.
   *   The first value is a callable and the second value are the arguments to
   *   pass to the callable.
   */
  protected static function toBatchOperationsRecipe(Recipe $recipe, array $recipes, array &$modules, array &$themes): array {
    if (in_array($recipe->path, $recipes, TRUE)) {
      return [];
    }
    $steps = [];
    $recipes[] = $recipe->path;

    foreach ($recipe->recipes->recipes as $sub_recipe) {
      $steps = array_merge($steps, static::toBatchOperationsRecipe($sub_recipe, $recipes, $modules, $themes));
    }
    $steps = array_merge($steps, static::toBatchOperationsInstall($recipe, $modules, $themes));
    if ($recipe->config->hasTasks()) {
      $steps[] = [[RecipeRunner::class, 'installConfig'], [$recipe]];
    }
    if (!empty($recipe->content->data)) {
      $steps[] = [[RecipeRunner::class, 'installContent'], [$recipe]];
    }
    $steps[] = [[RecipeRunner::class, 'triggerEvent'], [$recipe]];

    return $steps;
  }

  /**
   * Converts a recipe's install tasks to batch operations.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to convert install tasks to batch operations.
   * @param string[] $modules
   *   The modules that will already be installed due to previous recipes in the
   *   batch.
   * @param string[] $themes
   *   The themes that will already be installed due to previous recipes in the
   *   batch.
   *
   * @return array<int, array{0: callable, 1: array{mixed}}>
   *   The array of batch operations. Each value is an array with two values.
   *   The first value is a callable and the second value are the arguments to
   *   pass to the callable.
   */
  protected static function toBatchOperationsInstall(Recipe $recipe, array &$modules, array &$themes): array {
    foreach ($recipe->install->modules as $name) {
      if (in_array($name, $modules, TRUE)) {
        continue;
      }
      $modules[] = $name;
      $steps[] = [[RecipeRunner::class, 'installModule'], [$name, $recipe]];
    }
    foreach ($recipe->install->themes as $name) {
      if (in_array($name, $themes, TRUE)) {
        continue;
      }
      $themes[] = $name;
      $steps[] = [[RecipeRunner::class, 'installTheme'], [$name, $recipe]];
    }
    return $steps ?? [];
  }

  /**
   * Installs a module for a recipe.
   *
   * @param string $module
   *   The name of the module to install.
   * @param \Drupal\Core\Config\StorageInterface|\Drupal\Core\Recipe\Recipe $recipeConfigStorage
   *   The recipe or recipe's config storage.
   * @param array<mixed>|null $context
   *   The batch context if called by a batch.
   */
  public static function installModule(string $module, StorageInterface|Recipe $recipeConfigStorage, ?array &$context = NULL): void {
    if ($recipeConfigStorage instanceof Recipe) {
      $recipeConfigStorage = $recipeConfigStorage->config->getConfigStorage();
    }
    // Disable configuration entity install but use the config directory from
    // the module.
    \Drupal::service('config.installer')->setSyncing(TRUE);
    $default_install_path = \Drupal::service('extension.list.module')->get($module)->getPath() . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    // Allow the recipe to override simple configuration from the module.
    $storage = new RecipeOverrideConfigStorage(
      $recipeConfigStorage,
      new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION)
    );
    \Drupal::service('config.installer')->setSourceStorage($storage);

    \Drupal::service('module_installer')->install([$module]);
    \Drupal::service('config.installer')->setSyncing(FALSE);
    $context['message'] = t('Installed %module module.', ['%module' => \Drupal::service('extension.list.module')->getName($module)]);
    $context['results']['module'][] = $module;
  }

  /**
   * Installs a theme for a recipe.
   *
   * @param string $theme
   *   The name of the theme to install.
   * @param \Drupal\Core\Config\StorageInterface|\Drupal\Core\Recipe\Recipe $recipeConfigStorage
   *   The recipe or recipe's config storage.
   * @param array<mixed>|null $context
   *   The batch context if called by a batch.
   */
  public static function installTheme(string $theme, StorageInterface|Recipe $recipeConfigStorage, ?array &$context = NULL): void {
    if ($recipeConfigStorage instanceof Recipe) {
      $recipeConfigStorage = $recipeConfigStorage->config->getConfigStorage();
    }
    // Disable configuration entity install.
    \Drupal::service('config.installer')->setSyncing(TRUE);
    $default_install_path = \Drupal::service('extension.list.theme')->get($theme)->getPath() . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    // Allow the recipe to override simple configuration from the theme.
    $storage = new RecipeOverrideConfigStorage(
      $recipeConfigStorage,
      new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION)
    );
    \Drupal::service('config.installer')->setSourceStorage($storage);

    \Drupal::service('theme_installer')->install([$theme]);
    \Drupal::service('config.installer')->setSyncing(FALSE);
    $context['message'] = t('Installed %theme theme.', ['%theme' => \Drupal::service('extension.list.theme')->getName($theme)]);
    $context['results']['theme'][] = $theme;
  }

  /**
   * Installs a config for a recipe.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to install config for.
   * @param array<mixed>|null $context
   *   The batch context if called by a batch.
   */
  public static function installConfig(Recipe $recipe, ?array &$context = NULL): void {
    static::processConfiguration($recipe);
    $context['message'] = t('Installed configuration for %recipe recipe.', ['%recipe' => $recipe->name]);
    $context['results']['config'][] = $recipe->name;
  }

  /**
   * Installs a content for a recipe.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe to install content for.
   * @param array<mixed>|null $context
   *   The batch context if called by a batch.
   */
  public static function installContent(Recipe $recipe, ?array &$context = NULL): void {
    static::processContent($recipe->content);
    $context['message'] = t('Created content for %recipe recipe.', ['%recipe' => $recipe->name]);
    $context['results']['content'][] = $recipe->name;
  }

  /**
   * @param mixed $data
   *   The data that will have placeholders replaced.
   * @param array<string, mixed> $replace
   *   An array whose keys are the placeholders to be replaced, and whose values
   *   are the replacements.
   *
   * @return mixed
   *   The passed data, with placeholders replaced.
   */
  private static function replaceInputValues(mixed $data, array $replace): mixed {
    if (is_string($data)) {
      $data = str_replace(array_keys($replace), $replace, $data);
    }
    elseif (is_array($data)) {
      foreach ($data as $key => $value) {
        $data[$key] = static::replaceInputValues($value, $replace);
      }
    }
    return $data;
  }

}
