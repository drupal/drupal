<?php

/**
 * Contains \Drupal\custom_block\Plugin\block\block\CustomBlock.
 */

namespace Drupal\custom_block\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a generic custom block type.
 *
 * @Plugin(
 *  id = "custom_block",
 *  subject = @Translation("Custom Block"),
 *  module = "custom_block",
 *  derivative = "Drupal\custom_block\Plugin\Derivative\CustomBlock",
 *  settings = {
 *    "status" = TRUE,
 *    "info" = "",
 *    "body" = "",
 *    "format" = NULL
 *   }
 * )
 */
class CustomBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::getConfig().
   */
  public function getConfig() {
    $definition = $this->getDefinition();
    $this->configuration = parent::getConfig();
    $this->configuration['status'] = $definition['settings']['status'];
    $this->configuration['info'] = $definition['settings']['info'];
    $this->configuration['body'] = $definition['settings']['body'];
    $this->configuration['format'] = $definition['settings']['format'];
    return $this->configuration;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, &$form_state) {
    // @todo Disable this field when editing an existing block and provide a
    //   separate interface for administering custom blocks.
    $form['info'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['info'],
      '#description' => t('A brief description of your block. Used on the <a href="@overview">Blocks administration page</a>. <strong>Changing this field will change the description for all copies of this block.</strong>', array('@overview' => url('admin/structure/block'))),
    );
    // @todo Disable this field when editing an existing block and provide a
    //   separate interface for administering custom blocks.
    $form['body'] = array(
      '#type' => 'text_format',
      '#title' => t('Body'),
      '#default_value' => $this->configuration['body'],
      '#format' => isset($this->configuration['format']) ? $this->configuration['format'] : filter_default_format(),
      '#description' => t('The content of the block as shown to the user. <strong>Changing this field will change the block body everywhere it is used.</strong>'),
      '#rows' => 15,
      '#required' => TRUE,
    );
    $form['title']['#description'] = t('The title of the block as shown to the user.');
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockValidate().
   */
  public function blockValidate($form, &$form_state) {
    list(, $bid) = explode(':', $form_state['entity']->get('plugin'));
    $custom_block_exists = (bool) db_query_range('SELECT 1 FROM {block_custom} WHERE bid <> :bid AND info = :info', 0, 1, array(
      ':bid' => $bid,
      ':info' => $form_state['values']['info'],
    ))->fetchField();
    if (empty($form_state['values']['info']) || $custom_block_exists) {
      form_set_error('info', t('Ensure that each block description is unique.'));
    }
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    list(, $bid) = explode(':', $this->getPluginId());
    $block = array(
      'info' => $form_state['values']['info'],
      'body' => $form_state['values']['body']['value'],
      'format' => $form_state['values']['body']['format'],
      'bid' => is_numeric($bid) ? $bid : NULL,
    );
    drupal_write_record('block_custom', $block, !is_null($block['bid']) ? array('bid') : array());
    $form_state['entity']->set('plugin', 'custom_block:' . $block['bid']);
    // Invalidate the block cache to update custom block-based derivatives.
    if (module_exists('block')) {
      drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
    }
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    // Populate the block with the user-defined block body.
    return array(
      '#theme' => 'custom_block_block',
      '#body' => $this->configuration['body'],
      '#format' => $this->configuration['format'],
    );
  }

}
