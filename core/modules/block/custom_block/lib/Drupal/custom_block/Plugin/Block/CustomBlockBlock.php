<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Block\CustomBlockBlock.
 */

namespace Drupal\custom_block\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a generic custom block type.
 *
 * @Plugin(
 *  id = "custom_block",
 *  admin_label = @Translation("Custom block"),
 *  module = "custom_block",
 *  derivative = "Drupal\custom_block\Plugin\Derivative\CustomBlock"
 * )
 */
class CustomBlockBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
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
    $options = array();
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
    if (module_exists('block')) {
      drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo Clean up when http://drupal.org/node/1874498 lands.
    list(, $uuid) = explode(':', $this->getPluginId());
    if ($block = entity_load_by_uuid('custom_block', $uuid)) {
      return entity_view($block, $this->configuration['view_mode']);
    }
    else {
      return array(
        '#markup' => t('Block with uuid %uuid does not exist. <a href="!url">Add custom block</a>.', array(
          '%uuid' => $uuid,
          '!url' => url('block/add')
        )),
        '#access' => user_access('administer blocks')
      );
    }
  }
}
