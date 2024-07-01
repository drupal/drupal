<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Recipe\InvalidConfigException;
use Drupal\Core\Validation\Plugin\Validation\Constraint\FullyValidatableConstraint;

/**
 * @defgroup config_action_api Config Action API
 * @{
 * Information about the classes and interfaces that make up the Config Action
 * API.
 *
 * Configuration actions are plugins that manipulate simple configuration or
 * configuration entities. The configuration action plugin manager can apply
 * configuration actions. For example, the API is leveraged by recipes to create
 * roles if they do not exist already and grant permissions to those roles.
 *
 * To define a configuration action in a module you need to:
 * - Define a Config Action plugin by creating a new class that implements the
 *   \Drupal\Core\Config\Action\ConfigActionPluginInterface, in namespace
 *   Plugin\ConfigAction under your module namespace. For more information about
 *   creating plugins, see the @link plugin_api Plugin API topic. @endlink
 * - Config action plugins use the attributes defined by
 *  \Drupal\Core\Config\Action\Attribute\ConfigAction. See the
 *   @link attribute Attributes topic @endlink for more information about
 *   attributes.
 *
 * Further information and examples:
 * - \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityMethod derives
 *   configuration actions from config entity methods which have the
 *   \Drupal\Core\Config\Action\Attribute\ActionMethod attribute.
 * - \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityCreate allows you to
 *   create configuration entities if they do not exist.
 * - \Drupal\Core\Config\Action\Plugin\ConfigAction\SimpleConfigUpdate allows
 *   you to update simple configuration using a config action.
 * @}
 *
 * @internal
 *   This API is experimental.
 */
class ConfigActionManager extends DefaultPluginManager {

  /**
   * Information about all deprecated plugin IDs.
   *
   * @var string[]
   */
  private static array $deprecatedPluginIds = [
    'entity_create:ensure_exists' => [
      'replacement' => 'entity_create:createIfNotExists',
      'message' => 'The plugin ID "entity_create:ensure_exists" is deprecated in drupal:10.3.1 and will be removed in drupal:12.0.0. Use "entity_create:createIfNotExists" instead. See https://www.drupal.org/node/3458273.',
    ],
    'simple_config_update' => [
      'replacement' => 'simpleConfigUpdate',
      'message' => 'The plugin ID "simple_config_update" is deprecated in drupal:10.3.1 and will be removed in drupal:12.0.0. Use "simpleConfigUpdate" instead. See https://www.drupal.org/node/3458273.',
    ],
  ];

  /**
   * Constructs a new \Drupal\Core\Config\Action\ConfigActionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The active config storage.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfig
   *   The typed configuration manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly StorageInterface $configStorage,
    protected readonly TypedConfigManagerInterface $typedConfig,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    assert($namespaces instanceof \ArrayAccess, '$namespaces can be accessed like an array');
    // Enable this namespace to be searched for plugins.
    $namespaces[__NAMESPACE__] = 'core/lib/Drupal/Core/Config/Action';

    parent::__construct('Plugin/ConfigAction', $namespaces, $module_handler, ConfigActionPluginInterface::class, ConfigAction::class);

    $this->alterInfo('config_action');
    $this->setCacheBackend($cache_backend, 'config_action');
  }

  /**
   * Applies a config action.
   *
   * @param string $action_id
   *   The ID of the action to apply. This can be a complete configuration
   *   action plugin ID or a shorthand action ID that is available for the
   *   entity type of the provided configuration name.
   * @param string $configName
   *   The configuration name. This may be the full name of a config object, or
   *   it may contain wildcards (to target all config entities of a specific
   *   type, or a subset thereof). See
   *   ConfigActionManager::getConfigNamesMatchingExpression() for more detail.
   * @param mixed $data
   *   The data for the action.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the config action cannot be found.
   * @throws \Drupal\Core\Config\Action\ConfigActionException
   *   Thrown when the config action fails to apply.
   *
   * @see \Drupal\Core\Config\Action\ConfigActionManager::getConfigNamesMatchingExpression()
   */
  public function applyAction(string $action_id, string $configName, mixed $data): void {
    if (!$this->hasDefinition($action_id)) {
      // Get the full plugin ID from the shorthand map, if it is available.
      $entity_type = $this->configManager->getEntityTypeIdByName($configName);
      if ($entity_type) {
        $action_id = $this->getShorthandActionIdsForEntityType($entity_type)[$action_id] ?? $action_id;
      }
    }
    /** @var \Drupal\Core\Config\Action\ConfigActionPluginInterface $action */
    $action = $this->createInstance($action_id);
    foreach ($this->getConfigNamesMatchingExpression($configName) as $name) {
      $action->apply($name, $data);
      $typed_config = $this->typedConfig->createFromNameAndData($name, $this->configFactory->get($name)->getRawData());
      // All config objects are mappings.
      assert($typed_config instanceof Mapping);
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

  /**
   * Gets the names of all active config objects that match an expression.
   *
   * @param string $expression
   *   The expression to match. This may be the full name of a config object,
   *   or it may contain wildcards (to target all config entities of a specific
   *   type, or a subset thereof). For example:
   *   - `user.role.*` would target all user roles.
   *   - `user.role.anonymous` would target only the anonymous user role.
   *   - `core.entity_view_display.node.*.default` would target the default
   *     view display of every content type.
   *   - `core.entity_form_display.*.*.default` would target the default form
   *     display of every bundle of every entity type.
   *   The expression MUST begin with the prefix of a config entity type --
   *   for example, `field.field.` in the case of fields, or `user.role.` for
   *   user roles. The prefix cannot contain wildcards.
   *
   * @return string[]
   *   The names of all active config objects that match the expression.
   *
   * @throws \Drupal\Core\Config\Action\ConfigActionException
   *   Thrown if the expression does not match any known config entity type's
   *   prefix, or if the expression cannot be parsed.
   */
  private function getConfigNamesMatchingExpression(string $expression): array {
    // If there are no wildcards, we can return the config name as-is.
    if (!str_contains($expression, '.*')) {
      return [$expression];
    }

    $entity_type = $this->configManager->getEntityTypeIdByName($expression);
    if (empty($entity_type)) {
      throw new ConfigActionException("No installed config entity type uses the prefix in the expression '$expression'. Either there is a typo in the expression or this recipe should install an additional module or depend on another recipe.");
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $this->configManager->getEntityTypeManager()
      ->getDefinition($entity_type);
    $prefix = $entity_type->getConfigPrefix();

    // Convert the expression to a regular expression. We assume that * should
    // match the characters allowed by
    // \Drupal\Core\Config\ConfigBase::validateName(), which is permissive.
    $expression = str_replace('\\*', '[^.:?*<>"\'\/\\\\]+', preg_quote($expression));
    $matches = @preg_grep("/^$expression$/", $this->configStorage->listAll("$prefix."));
    if ($matches === FALSE) {
      throw new ConfigActionException("The expression '$expression' could not be parsed.");
    }
    return $matches;
  }

  /**
   * Gets a map of shorthand action IDs to plugin IDs for an entity type.
   *
   * @param string $entityType
   *   The entity type ID to get the map for.
   *
   * @return string[]
   *   An array of plugin IDs keyed by shorthand action ID for the provided
   *   entity type.
   */
  protected function getShorthandActionIdsForEntityType(string $entityType): array {
    $map = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (in_array($entityType, $definition['entity_types'], TRUE) || in_array('*', $definition['entity_types'], TRUE)) {
        $regex = '/' . PluginBase::DERIVATIVE_SEPARATOR . '([^' . PluginBase::DERIVATIVE_SEPARATOR . ']*)$/';
        $action_id = preg_match($regex, $plugin_id, $matches) ? $matches[1] : $plugin_id;
        if (isset($map[$action_id])) {
          throw new DuplicateConfigActionIdException(sprintf('The plugins \'%s\' and \'%s\' both resolve to the same shorthand action ID for the \'%s\' entity type', $plugin_id, $map[$action_id], $entityType));
        }
        $map[$action_id] = $plugin_id;
      }
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDefinitions(&$definitions): void {
    // Adds backwards compatibility for plugins that have been renamed.
    foreach (self::$deprecatedPluginIds as $legacy => $new_plugin_id) {
      $definitions[$legacy] = $definitions[$new_plugin_id['replacement']];
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);
    // Trigger deprecation notices for renamed plugins.
    if (array_key_exists($plugin_id, self::$deprecatedPluginIds)) {
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error(self::$deprecatedPluginIds[$plugin_id]['message'], E_USER_DEPRECATED);
    }
    return $instance;
  }

}
