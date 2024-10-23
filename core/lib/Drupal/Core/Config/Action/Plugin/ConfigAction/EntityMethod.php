<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\Action\EntityMethodException;
use Drupal\Core\Config\Action\Exists;
use Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityMethodDeriver;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Makes config entity methods with the ActionMethod attribute into actions.
 *
 * For example, adding the ActionMethod attribute to
 * \Drupal\user\Entity\Role::grantPermission() allows permissions to be added to
 * roles via config actions.
 *
 * When calling \Drupal\Core\Config\Action\ConfigActionManager::applyAction()
 * the $data parameter is mapped to the method's arguments using the following
 * rules:
 * - If $data is not an array, the method must only have one argument or one
 *   required argument.
 * - If $data is an array and the method only accepts a single argument, the
 *   array will be passed to the first argument.
 * - If $data is an array and the method accepts more than one argument, $data
 *   will be unpacked into the method arguments.
 *
 * @internal
 *   This API is experimental.
 *
 * @see \Drupal\Core\Config\Action\Attribute\ActionMethod
 */
#[ConfigAction(
  id: 'entity_method',
  deriver: EntityMethodDeriver::class,
)]
final class EntityMethod implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a EntityMethod object.
   *
   * @param string $pluginId
   *   The config action plugin ID.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param string $method
   *   The method to call on the config entity.
   * @param \Drupal\Core\Config\Action\Exists $exists
   *   Determines behavior of action depending on entity existence.
   * @param int $numberOfParams
   *   The number of parameters the method has.
   * @param int $numberOfRequiredParams
   *   The number of required parameters the method has.
   * @param bool $pluralized
   *   Determines whether an array maps to multiple calls.
   */
  public function __construct(
    protected readonly string $pluginId,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly string $method,
    protected readonly Exists $exists,
    protected readonly int $numberOfParams,
    protected readonly int $numberOfRequiredParams,
    protected readonly bool $pluralized,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    assert(is_array($plugin_definition) && is_array($plugin_definition['constructor_args']), '$plugin_definition contains the expected settings');
    return new static(
      $plugin_id,
      $container->get('config.manager'),
      ...$plugin_definition['constructor_args']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface|null $entity */
    $entity = $this->configManager->loadConfigEntityByName($configName);
    if ($this->exists->returnEarly($configName, $entity)) {
      return;
    }

    $entity = $this->pluralized ? $this->applyPluralized($entity, $value) : $this->applySingle($entity, $value);
    $entity->save();
  }

  /**
   * Applies the action to entity treating the $values array as multiple calls.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to apply the action to.
   * @param mixed $values
   *   The values for the action to use.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The unsaved entity with the action applied.
   */
  private function applyPluralized(ConfigEntityInterface $entity, mixed $values): ConfigEntityInterface {
    if (!is_array($values)) {
      throw new EntityMethodException(sprintf('The pluralized entity method config action \'%s\' requires an array value in order to call %s::%s() multiple times', $this->pluginId, $entity->getEntityType()->getClass(), $this->method));
    }
    foreach ($values as $value) {
      $entity = $this->applySingle($entity, $value);
    }
    return $entity;
  }

  /**
   * Applies the action to entity treating the $values array a single call.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to apply the action to.
   * @param mixed $value
   *   The value for the action to use.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The unsaved entity with the action applied.
   */
  private function applySingle(ConfigEntityInterface $entity, mixed $value): ConfigEntityInterface {
    // If $value is not an array then we only support calling the method if the
    // number of parameters or required parameters is 1. If there is only 1
    // parameter and $value is an array then assume that the parameter expects
    // an array.
    if (!is_array($value) || $this->numberOfParams === 1) {
      if ($this->numberOfRequiredParams !== 1 && $this->numberOfParams !== 1) {
        throw new EntityMethodException(sprintf('Entity method config action \'%s\' requires an array value. The number of parameters or required parameters for %s::%s() is not 1', $this->pluginId, $entity->getEntityType()->getClass(), $this->method));
      }
      $result = $entity->{$this->method}($value);
    }
    else {
      $result = $entity->{$this->method}(...$value);
    }
    // If an instance of the entity (either itself, or a clone) was returned
    // by the method, return that.
    return is_a($result, get_class($entity)) ? $result : $entity;
  }

}
