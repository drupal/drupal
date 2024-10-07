<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * @internal
 *   This API is experimental.
 */
final class ConfigConfigurator {

  public readonly ?string $recipeConfigDirectory;

  private readonly bool|array $strict;

  /**
   * @param array $config
   *   Config options for a recipe.
   * @param string $recipe_directory
   *   The path to the recipe.
   * @param \Drupal\Core\Config\StorageInterface $active_configuration
   *   The active configuration storage.
   */
  public function __construct(public readonly array $config, string $recipe_directory, StorageInterface $active_configuration) {
    $this->recipeConfigDirectory = is_dir($recipe_directory . '/config') ? $recipe_directory . '/config' : NULL;
    // @todo Consider defaulting this to FALSE in https://drupal.org/i/3478669.
    $this->strict = $config['strict'] ?? TRUE;

    $recipe_storage = $this->getConfigStorage();
    if ($this->strict === TRUE) {
      $strict_list = $recipe_storage->listAll();
    }
    else {
      $strict_list = $this->strict ?: [];
    }

    // Everything in the strict list needs to be identical in the recipe and
    // active storage.
    foreach ($strict_list as $config_name) {
      if ($active_data = $active_configuration->read($config_name)) {
        // @todo https://www.drupal.org/i/3439714 Investigate if there is any
        //   generic code in core for this.
        unset($active_data['uuid'], $active_data['_core']);
        if (empty($active_data['dependencies'])) {
          unset($active_data['dependencies']);
        }
        $recipe_data = $recipe_storage->read($config_name);
        if (empty($recipe_data['dependencies'])) {
          unset($recipe_data['dependencies']);
        }
        // Ensure we don't get a false mismatch due to differing key order.
        // @todo When https://www.drupal.org/project/drupal/issues/3230826 is
        //   fixed in core, use that API instead to sort the config data.
        self::recursiveSortByKey($active_data);
        self::recursiveSortByKey($recipe_data);
        if ($active_data !== $recipe_data) {
          throw new RecipePreExistingConfigException($config_name, sprintf("The configuration '%s' exists already and does not match the recipe's configuration", $config_name));
        }
      }
    }
  }

  /**
   * Sorts an array recursively, by key, alphabetically.
   *
   * @param mixed[] $data
   *   The array to sort, passed by reference.
   *
   * @todo Remove when https://www.drupal.org/project/drupal/issues/3230826 is
   *   fixed in core.
   */
  private static function recursiveSortByKey(array &$data): void {
    // If the array is a list, it is by definition already sorted.
    if (!array_is_list($data)) {
      ksort($data);
    }
    foreach ($data as &$value) {
      if (is_array($value)) {
        self::recursiveSortByKey($value);
      }
    }
  }

  /**
   * Gets a config storage object for reading config from the recipe.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The  config storage object for reading config from the recipe.
   */
  public function getConfigStorage(): StorageInterface {
    $storages = [];

    if ($this->recipeConfigDirectory) {
      // Config provided by the recipe should take priority over config from
      // extensions.
      $storages[] = new FileStorage($this->recipeConfigDirectory);
    }
    if (!empty($this->config['import'])) {
      /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
      $module_list = \Drupal::service('extension.list.module');
      /** @var \Drupal\Core\Extension\ThemeExtensionList $theme_list */
      $theme_list = \Drupal::service('extension.list.theme');
      foreach ($this->config['import'] as $extension => $names) {
        // If the recipe explicitly does not want to import any config from this
        // extension, skip it.
        if ($names === NULL) {
          continue;
        }
        $path = match (TRUE) {
          $module_list->exists($extension) => $module_list->getPath($extension),
          $theme_list->exists($extension) => $theme_list->getPath($extension),
          default => throw new \RuntimeException("$extension is not a theme or module")
        };

        $storage = new RecipeConfigStorageWrapper(
          new FileStorage($path . '/config/install'),
          new FileStorage($path . '/config/optional'),
        );
        // If we get here, $names is either '*', or a list of config names
        // provided by the current extension. In the latter case, we only want
        // to import the config that is in the list, so use an
        // AllowListConfigStorage to filter out the extension's other config.
        if ($names && is_array($names)) {
          $storage = new AllowListConfigStorage($storage, $names);
        }
        $storages[] = $storage;
      }
    }
    $storage = RecipeConfigStorageWrapper::createStorageFromArray($storages);

    if ($this->strict) {
      return $storage;
    }
    // If we're not in strict mode, we only want to import config that doesn't
    // exist yet in active storage.

    $names = array_diff(
      $storage->listAll(),
      \Drupal::service('config.storage')->listAll(),
    );
    return $names
      ? new AllowListConfigStorage($storage, $names)
      : new NullStorage();
  }

  /**
   * Determines if the recipe has any config or config actions to apply.
   *
   * @return bool
   *   TRUE if the recipe has any config or config actions to apply, FALSE if
   *   not.
   */
  public function hasTasks(): bool {
    return $this->recipeConfigDirectory !== NULL || count($this->config);
  }

}
