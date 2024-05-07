<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Validation\Plugin\Validation\Constraint\FullyValidatableConstraint;

/**
 * Extends the ConfigInstaller service for recipes.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeConfigInstaller extends ConfigInstaller {

  /**
   * {@inheritdoc}
   */
  public function installRecipeConfig(ConfigConfigurator $recipe_config): void {
    $storage = $recipe_config->getConfigStorage();

    // Build the list of new configuration to create.
    $list = array_diff($storage->listAll(), $this->getActiveStorages()->listAll());

    // If there is nothing to do.
    if (empty($list)) {
      return;
    }

    $config_to_create = $storage->readMultiple($list);

    // Sort $config_to_create in the order of the least dependent first.
    $dependency_manager = new ConfigDependencyManager();
    $dependency_manager->setData($config_to_create);
    $config_to_create = array_merge(array_flip($dependency_manager->sortAll()), $config_to_create);

    // Create the optional configuration if there is any left after filtering.
    if (!empty($config_to_create)) {
      $this->createConfiguration(StorageInterface::DEFAULT_COLLECTION, $config_to_create);
    }

    // Validation during the installer is hard. For example:
    // Drupal\ckeditor5\Plugin\Validation\Constraint\EnabledConfigurablePluginsConstraintValidator
    // ends up calling _ckeditor5_theme_css() via
    // Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition->validateDrupalAspects()
    // and this expects the theme system to be set up correctly but we're in the
    // installer so this cannot happen.
    // @todo https://www.drupal.org/i/3443603 consider adding a validation step
    //   for recipes to the installer via install_tasks().
    if (InstallerKernel::installationAttempted()) {
      return;
    }

    foreach (array_keys($config_to_create) as $name) {
      // All config objects are mappings.
      /** @var \Drupal\Core\Config\Schema\Mapping $typed_config */
      $typed_config = $this->typedConfig->createFromNameAndData($name, $this->configFactory->get($name)->getRawData());
      foreach ($typed_config->getConstraints() as $constraint) {
        // Only validate the config if it has explicitly been marked as being
        // validatable.
        if ($constraint instanceof FullyValidatableConstraint) {
          /** @var \Symfony\Component\Validator\ConstraintViolationList $violations */
          $violations = $typed_config->validate();
          if (count($violations) > 0) {
            throw new InvalidConfigException($violations, $typed_config);
          }
          break;
        }
      }
    }
  }

}
