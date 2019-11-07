<?php

namespace Drupal\path_alias;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\EventSubscriber\PathSubscriber;
use Drupal\Core\Path\AliasManager as CoreAliasManager;
use Drupal\Core\Path\AliasRepository as CoreAliasRepository;
use Drupal\Core\Path\AliasWhitelist as CoreAliasWhitelist;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\path_alias\EventSubscriber\PathAliasSubscriber;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;

/**
 * Path Alias service provider.
 *
 * Updates core path alias service definitions to use the new classes provided
 * by "path_alias" and marks the old ones as deprecated. The "path_alias.*"
 * services defined in "core.services.yml" will bridge the gap.
 *
 * @see https://www.drupal.org/node/3092086
 *
 * @todo Remove this once core fully supports "path_alias" as an optional
 *   module. See https://www.drupal.org/node/3092090.
 */
class PathAliasServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $service_map = [
      'path_subscriber' => [
        'class' => PathAliasSubscriber::class,
        'core_class' => PathSubscriber::class,
        'new_service' => 'path_alias.subscriber',
      ],
      'path_processor_alias' => [
        'class' => AliasPathProcessor::class,
        'core_class' => PathProcessorAlias::class,
        'new_service' => 'path_alias.path_processor',
      ],
      'path.alias_manager' => [
        'class' => AliasManager::class,
        'core_class' => CoreAliasManager::class,
        'new_service' => 'path_alias.manager',
      ],
      'path.alias_whitelist' => [
        'class' => AliasWhitelist::class,
        'core_class' => CoreAliasWhitelist::class,
        'new_service' => 'path_alias.whitelist',
      ],
      'path_alias.repository' => [
        'class' => AliasRepository::class,
        'core_class' => CoreAliasRepository::class,
        'new_service' => 'path_alias.repository',
      ],
    ];

    // Replace services only if core classes are implementing them to avoid
    // overriding customizations not relying on decoration.
    foreach ($service_map as $id => $info) {
      // Mark legacy services as "deprecated".
      $definition = $id !== $info['new_service'] && $container->hasDefinition($id) ? $container->getDefinition($id) : NULL;
      if ($definition && $definition->getClass() === $info['core_class']) {
        $definition->setDeprecated(TRUE, 'The "%service_id%" service is deprecated. Use "' . $info['new_service'] . '" instead. See https://drupal.org/node/3092086');
      }
      // Also the new service's class is initially set to the legacy one, to
      // avoid errors when the "path_alias" module is not enabled yet. Here we
      // need to replace that as well.
      $definition = $container->getDefinition($info['new_service']);
      if ($definition && $definition->getClass() === $info['core_class']) {
        $definition->setClass($info['class']);
      }
    }
  }

}
