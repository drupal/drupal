<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\PermissionsPerBundleDeriver;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'permissions_per_bundle',
  entity_types: ['user_role'],
  deriver: PermissionsPerBundleDeriver::class,
)]
final class PermissionsPerBundle implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    private readonly string $targetEntityType,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    assert(is_array($plugin_definition));
    $target_entity_type = $plugin_definition['target_entity_type'];

    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get(EntityTypeBundleInfoInterface::class),
      $target_entity_type,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $role = $this->configManager->loadConfigEntityByName($configName);
    if (!($role instanceof RoleInterface)) {
      throw new ConfigActionException(sprintf("Cannot determine role from %s", $configName));
    }

    assert(is_string($value) || is_array($value));
    [$permissions, $except_bundles] = self::parseValue($value);

    if (empty($permissions) || !Inspector::assertAllMatch('%bundle', $permissions, TRUE)) {
      throw new ConfigActionException(sprintf("The permissions provided %s must be an array of strings that contain '%%bundle'.", var_export($value, TRUE)));
    }

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->targetEntityType);
    foreach (array_keys($bundles) as $bundle_id) {
      if (in_array($bundle_id, $except_bundles, TRUE)) {
        continue;
      }
      /** @var string[] $actual_permissions */
      $actual_permissions = str_replace('%bundle', $bundle_id, $permissions);
      array_walk($actual_permissions, $role->grantPermission(...));
    }
    $role->save();
  }

  /**
   * Parses the value supplied to ::apply().
   *
   * @param string|array<string|string[]> $value
   *   One of:
   *   - A single string (a permission template).
   *   - An array of strings (several permission templates).
   *   - An array with a `permissions` element, and an optional `except`
   *     element, either of which can be an array or a string. `except` accepts
   *     a single bundle, or a list of bundles, to exclude from the permissions
   *     being granted.
   *
   * @return array<int, array<int<0, max>, array<string>|string>>
   *   An indexed array with two elements: the array of permissions to grant,
   *   and the list of bundles to ignore.
   */
  private static function parseValue(string|array $value): array {
    if (is_string($value)) {
      return [[$value], []];
    }

    if (array_is_list($value)) {
      return [$value, []];
    }

    $permissions = $value['permissions'] ?? [];
    $except_bundles = $value['except'] ?? [];
    return [(array) $permissions, (array) $except_bundles];
  }

}
