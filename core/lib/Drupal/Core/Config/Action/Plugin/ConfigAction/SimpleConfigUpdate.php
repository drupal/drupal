<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'simple_config_update',
  admin_label: new TranslatableMarkup('Simple configuration update'),
)]
final class SimpleConfigUpdate implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a SimpleConfigUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $config = $this->configFactory->getEditable($configName);
    // @todo https://www.drupal.org/i/3439713 Should we error if this is a
    //   config entity?
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
