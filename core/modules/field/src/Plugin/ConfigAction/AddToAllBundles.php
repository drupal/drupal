<?php

declare(strict_types=1);

namespace Drupal\field\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a field to all bundles of its target entity type.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'field_storage_config:addToAllBundles',
  admin_label: new TranslatableMarkup('Add a field to all bundles'),
  entity_types: ['field_storage_config'],
)]
final class AddToAllBundles implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    private readonly ConfigManagerInterface $configManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(EntityTypeManagerInterface::class),
      $container->get(EntityTypeBundleInfoInterface::class),
      $container->get(ConfigManagerInterface::class),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    assert(is_array($value));

    $field_storage = $this->configManager->loadConfigEntityByName($configName);
    assert($field_storage instanceof FieldStorageConfigInterface);

    $storage = $this->entityTypeManager->getStorage('field_config');

    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $field_name = $field_storage->getName();

    $existing_fields = $storage->getQuery()
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_name)
      ->execute();

    // Get all bundles of the target entity type.
    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
    foreach ($bundles as $bundle) {
      $id = "$entity_type_id.$bundle.$field_name";
      if (in_array($id, $existing_fields, TRUE)) {
        if (empty($value['fail_if_exists'])) {
          continue;
        }
        throw new ConfigActionException(sprintf('Field %s already exists.', $id));
      }
      $storage->create([
        'label' => $value['label'],
        'bundle' => $bundle,
        'description' => $value['description'],
        'field_storage' => $field_storage,
      ])->save();
    }
  }

}
