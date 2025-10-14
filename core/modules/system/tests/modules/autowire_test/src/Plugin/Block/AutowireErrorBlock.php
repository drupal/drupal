<?php

declare(strict_types=1);

namespace Drupal\autowire_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block that cannot be autowired.
 */
#[Block(
  id: "autowire_error",
  admin_label: new TranslatableMarkup("Autowire error")
)]
class AutowireErrorBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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

}
