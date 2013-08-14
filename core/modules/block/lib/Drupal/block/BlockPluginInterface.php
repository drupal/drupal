<?php

/**
 * @file
 * Contains \Drupal\block\BlockPluginInterface.
 */

namespace Drupal\block;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the required interface for all block plugins.
 *
 * @todo Add detailed documentation here explaining the block system's
 *   architecture and the relationships between the various objects, including
 *   brif references to the important components that are not coupled to the
 *   interface.
 */
interface BlockPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns the default settings for this block plugin.
   *
   * @return array
   *   An associative array of block settings for this block, keyed by the
   *   setting name.
   *
   * @todo Consider merging this with the general plugin configuration member
   *   variable and its getter/setter in http://drupal.org/node/1764380.
   */
  public function settings();

  /**
   * Indicates whether the block should be shown.
   *
   * This method allows base implementations to add general access restrictions
   * that should apply to all extending block plugins.
   *
   * @return bool
   *   TRUE if the block should be shown, or FALSE otherwise.
   *
   * @see \Drupal\block\BlockAccessController
   */
  public function access();

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockRenderController
   */
  public function build();

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
   * @todo This does not set a value in \Drupal::config(), so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfigurationValue($key, $value);

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
   */
  public function blockForm($form, &$form_state);

  /**
   * Adds block type-specific validation for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockPluginInterface::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockPluginInterface::blockForm()
   * @see \Drupal\block\BlockPluginInterface::blockSubmit()
   */
  public function blockValidate($form, &$form_state);

  /**
   * Adds block type-specific submission handling for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockPluginInterface::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockPluginInterface::blockForm()
   * @see \Drupal\block\BlockPluginInterface::blockValidate()
   */
  public function blockSubmit($form, &$form_state);

  /**
   * Suggests a machine name to identify an instance of this block.
   *
   * The block plugin need not verify that the machine name is at all unique. It
   * is only responsible for providing a baseline suggestion; calling code is
   * responsible for ensuring whatever uniqueness is required for the use case.
   *
   * @return string
   *   The suggested machine name.
   */
  public function getMachineNameSuggestion();

}
