<?php

/**
 * @file
 * Contains \Drupal\block\BlockBase.
 */

namespace Drupal\block;

use Drupal\Component\Plugin\PluginBase;
use Drupal\block\BlockInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 */
abstract class BlockBase extends PluginBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->settings() + array(
      'label' => '',
      'module' => $plugin_definition['module'],
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
      'cache' => DRUPAL_NO_CACHE,
    );
  }

  /**
   * Returns plugin-specific settings for the block.
   *
   * Block plugins only need to override this method if they override the
   * defaults provided in BlockBase::settings().
   *
   * @return array
   *   An array of block-specific settings to override the defaults provided in
   *   BlockBase::settings().
   *
   * @see \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    return array();
  }

  /**
   * Returns the configuration data for the block plugin.
   *
   * @return array
   *   The plugin configuration array from PluginBase::$configuration.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   http://drupal.org/node/1764380.
   * @todo This does not return a config object, so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function getConfig() {
    return $this->configuration;
  }

  /**
   * Sets a particular value in the block settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   http://drupal.org/node/1764380.
   * @todo This does not set a value in config(), so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfig($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * Indicates whether block-specific criteria allow access to the block.
   *
   * Blocks with access restrictions that should always be applied,
   * regardless of user-configured settings, should implement this method
   * with that access control logic.
   *
   * @return bool
   *   FALSE to deny access to the block, or TRUE to allow access.
   *
   * @see hook_block_access()
   */
  public function access() {
    // By default, the block is visible unless user-configured rules indicate
    // that it should be hidden.
    return TRUE;
  }

  /**
   * Implements \Drupal\block\BlockPluginInterface::form().
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   */
  public function form($form, &$form_state) {
    $definition = $this->getPluginDefinition();
    $form['module'] = array(
      '#type' => 'value',
      '#value' => $definition['module'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#maxlength' => 255,
      '#default_value' => !empty($this->configuration['label']) ? $this->configuration['label'] : $definition['admin_label'],
      '#required' => TRUE,
    );
    $form['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display title'),
      '#default_value' => $this->configuration['label_display'] == BlockInterface::BLOCK_LABEL_VISIBLE,
      '#return_value' => BlockInterface::BLOCK_LABEL_VISIBLE,
    );

    // Add plugin-specific settings for this block type.
    $form += $this->blockForm($form, $form_state);
    return $form;
  }

  /**
   * Returns the configuration form elements specific to this block plugin.
   *
   * Blocks that need to add form elements to the normal block configuration
   * form should implement this method.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\block\BlockBase::form()
   */
  public function blockForm($form, &$form_state) {
    return array();
  }

  /**
   * Implements \Drupal\block\BlockPluginInterface::validate().
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValdiate().
   *
   * @todo Add inline documentation to this method.
   *
   * @see \Drupal\block\BlockBase::blockValidate()
   */
  public function validate($form, &$form_state) {
    $this->blockValidate($form, $form_state);
  }

  /**
   * Adds block type-specific validation for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockBase::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   * @see \Drupal\block\BlockBase::blockSubmit()
   * @see \Drupal\block\BlockBase::validate()
   */
  public function blockValidate($form, &$form_state) {}

  /**
   * Implements \Drupal\block\BlockPluginInterface::submit().
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @todo Add inline documentation to this method.
   *
   * @see \Drupal\block\BlockBase::blockSubmit()
   */
  public function submit($form, &$form_state) {
    if (!form_get_errors()) {
      $this->configuration['label'] = $form_state['values']['label'];
      $this->configuration['label_display'] = $form_state['values']['label_display'];
      $this->configuration['module'] = $form_state['values']['module'];
      $this->blockSubmit($form, $form_state);
    }
  }

  /**
   * Adds block type-specific submission handling for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockBase::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   * @see \Drupal\block\BlockBase::blockValidate()
   * @see \Drupal\block\BlockBase::submit()
   */
  public function blockSubmit($form, &$form_state) {}
}
