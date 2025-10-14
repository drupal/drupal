<?php

declare(strict_types=1);

namespace Drupal\autowire_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a block that can be autowired.
 */
#[Block(
  id: "autowire",
  admin_label: new TranslatableMarkup("Autowire block")
)]
class AutowireBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(service: 'lock')]
    protected LockBackendInterface $lock,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * Gets the lock service.
   */
  public function getLock(): LockBackendInterface {
    return $this->lock;
  }

}
