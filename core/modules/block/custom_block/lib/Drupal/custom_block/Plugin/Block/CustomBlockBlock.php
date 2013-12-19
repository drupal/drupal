<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Block\CustomBlockBlock.
 */

namespace Drupal\custom_block\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\block\Plugin\Type\BlockManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block type.
 *
 * @Block(
 *  id = "custom_block",
 *  admin_label = @Translation("Custom block"),
 *  category = @Translation("Custom"),
 *  derivative = "Drupal\custom_block\Plugin\Derivative\CustomBlock"
 * )
 */
class CustomBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Plugin Block Manager.
   *
   * @var \Drupal\block\Plugin\Type\BlockManager.
   */
  protected $blockManager;

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface.
   */
  protected $moduleHandler;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * Constructs a new CustomBlockBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\block\Plugin\Type\BlockManager
   *   The Plugin Block Manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The Module Handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, BlockManager $block_manager, ModuleHandlerInterface $module_handler, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->blockManager = $block_manager;
    $this->moduleHandler = $module_handler;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'status' => TRUE,
      'info' => '',
      'view_mode' => 'full',
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, &$form_state) {
    $options = array('default' => t('Default'));
    $view_modes = entity_get_view_modes('custom_block');
    foreach ($view_modes as $view_mode => $detail) {
      $options[$view_mode] = $detail['label'];
    }
    $form['custom_block']['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#description' => t('Output the block in this view mode.'),
      '#default_value' => $this->configuration['view_mode']
    );
    $form['title']['#description'] = t('The title of the block as shown to the user.');
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    // Invalidate the block cache to update custom block-based derivatives.
    if ($this->moduleHandler->moduleExists('block')) {
      $this->configuration['view_mode'] = $form_state['values']['custom_block']['view_mode'];
      $this->blockManager->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $uuid = $this->getDerivativeId();
    if ($block = entity_load_by_uuid('custom_block', $uuid)) {
      return entity_view($block, $this->configuration['view_mode']);
    }
    else {
      return array(
        '#markup' => t('Block with uuid %uuid does not exist. <a href="!url">Add custom block</a>.', array(
          '%uuid' => $uuid,
          '!url' => url('block/add')
        )),
        '#access' => $this->account->hasPermission('administer blocks')
      );
    }
  }
}
