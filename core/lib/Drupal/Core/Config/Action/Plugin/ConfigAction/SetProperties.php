<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'setProperties',
  admin_label: new TranslatableMarkup('Set property of a config entity'),
  entity_types: ['*'],
)]
final class SetProperties implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get(ConfigManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $values): void {
    $entity = $this->configManager->loadConfigEntityByName($configName);
    assert($entity instanceof ConfigEntityInterface);

    assert(is_array($values));
    assert(!array_is_list($values));

    // Don't allow the ID or UUID to be changed.
    $entity_keys = $entity->getEntityType()->getKeys();
    $forbidden_keys = array_filter([
      $entity_keys['id'],
      $entity_keys['uuid'],
    ]);

    foreach ($values as $property_name => $value) {
      if (in_array($property_name, $forbidden_keys, TRUE)) {
        throw new ConfigActionException("Entity key '$property_name' cannot be changed by the setProperties config action.");
      }
      $parts = explode('.', $property_name);

      $property_value = $entity->get($parts[0]);
      if (count($parts) > 1) {
        if (isset($property_value) && !is_array($property_value)) {
          throw new ConfigActionException('The setProperties config action can only set nested values on arrays.');
        }
        $property_value ??= [];
        NestedArray::setValue($property_value, array_slice($parts, 1), $value);
      }
      else {
        $property_value = $value;
      }
      $entity->set($parts[0], $property_value);
    }
    $entity->save();
  }

}
