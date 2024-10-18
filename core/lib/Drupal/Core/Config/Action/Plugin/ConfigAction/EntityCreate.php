<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\Action\Exists;
use Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityCreateDeriver;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'entity_create',
  deriver: EntityCreateDeriver::class,
)]
final class EntityCreate implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a EntityCreate object.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Config\Action\Exists $exists
   *   Determines behavior of action depending on entity existence.
   */
  public function __construct(
    protected readonly ConfigManagerInterface $configManager,
    protected readonly Exists $exists,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    assert(is_array($plugin_definition) && is_array($plugin_definition['constructor_args']), '$plugin_definition contains the expected settings');
    return new static($container->get('config.manager'), ...$plugin_definition['constructor_args']);
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (!is_array($value)) {
      throw new ConfigActionException(sprintf("The value provided to create %s must be an array", $configName));
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface|null $entity */
    $entity = $this->configManager->loadConfigEntityByName($configName);
    if ($this->exists->returnEarly($configName, $entity)) {
      return;
    }

    $entity_type_manager = $this->configManager->getEntityTypeManager();
    $entity_type_id = $this->configManager->getEntityTypeIdByName($configName);
    if ($entity_type_id === NULL) {
      throw new ConfigActionException(sprintf("Cannot determine a config entity type from %s", $configName));
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);

    $id = substr($configName, strlen($entity_type->getConfigPrefix()) + 1);
    $entity_type_manager
      ->getStorage($entity_type->id())
      ->create($value + [
        $entity_type->getKey('id') => $id,
      ])
      ->save();
  }

}
