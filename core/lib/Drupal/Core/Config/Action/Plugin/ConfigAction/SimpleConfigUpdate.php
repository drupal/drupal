<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'simpleConfigUpdate',
  admin_label: new TranslatableMarkup('Simple configuration update'),
)]
final class SimpleConfigUpdate implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ConfigManagerInterface $configManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(ConfigFactoryInterface::class),
      $container->get(ConfigManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if ($this->configManager->getEntityTypeIdByName($configName)) {
      // @todo Make this an exception in https://www.drupal.org/node/3515544.
      @trigger_error('Using the simpleConfigUpdate config action on config entities is deprecated in drupal:11.2.0 and throws an exception in drupal:12.0.0. Use the setProperties action instead. See https://www.drupal.org/node/3515543', E_USER_DEPRECATED);
    }

    $config = $this->configFactory->getEditable($configName);
    if ($config->isNew()) {
      throw new ConfigActionException(sprintf('Config %s does not exist so can not be updated', $configName));
    }

    // Expect $value to be an array whose keys are the config keys to update.
    if (!is_array($value)) {
      throw new ConfigActionException(sprintf('Config %s can not be updated because $value is not an array', $configName));
    }
    foreach ($value as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

}
