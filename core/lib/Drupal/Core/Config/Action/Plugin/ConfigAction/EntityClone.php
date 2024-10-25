<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'cloneAs',
  admin_label: new TranslatableMarkup('Clone entity with a new ID'),
  entity_types: ['*'],
)]
final class EntityClone implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly ConfigActionManager $configActionManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get('plugin.manager.config_action'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (!is_array($value)) {
      $value = ['id' => $value];
    }
    assert(is_string($value['id']));

    $value += ['fail_if_exists' => FALSE];
    assert(is_bool($value['fail_if_exists']));

    // If the original doesn't exist, there's nothing to clone.
    $original = $this->configManager->loadConfigEntityByName($configName);
    if (empty($original)) {
      throw new ConfigActionException("Cannot clone '$configName' because it does not exist.");
    }
    $clone = $original->createDuplicate();
    $clone->set($original->getEntityType()->getKey('id'), $value['id']);

    $create_action = 'entity_create:' . ($value['fail_if_exists'] ? 'create' : 'createIfNotExists');
    // Use the config action manager to invoke the create action on the clone,
    // so that it will be validated.
    $this->configActionManager->applyAction($create_action, $clone->getConfigDependencyName(), $clone->toArray());
  }

}
