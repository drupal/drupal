<?php

declare(strict_types=1);

namespace Drupal\config_test;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide dynamic permissions for testing permission dependencies on config.
 */
class ConfigTestPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Permissions callback.
   *
   * @return array
   *   The list of permissions.
   */
  public function configTestPermissions(): array {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('config_test')->loadMultiple();
    $permissions = [];
    foreach ($entities as $entity) {
      $config_name = $entity->getConfigDependencyName();
      $permissions["permission with $config_name dependency"] = [
        'title' => $this->t('Permission with a dependency on config test entity %id', [
          '%id' => $entity->id(),
        ]),
        'dependencies' => [$entity->getConfigDependencyKey() => [$config_name]],
      ];
    }
    return $permissions;
  }

}
