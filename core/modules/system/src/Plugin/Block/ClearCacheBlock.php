<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\Form\ClearCacheForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display 'Clear cache' elements.
 */
#[Block(
  id: "system_clear_cache_block",
  admin_label: new TranslatableMarkup("Clear cache")
)]
class ClearCacheBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a ClearCacheBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return $this->formBuilder->getForm(ClearCacheForm::class);
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer site configuration');
  }

}
