<?php

/**
 * @file
 * Contains \Drupal\block\BlockPluginInterface.
 */

namespace Drupal\block;

/**
 * Defines the required interface for all block plugins.
 *
 * @todo Add detailed documentation here explaining the block system's
 *   architecture and the relationships between the various objects, including
 *   brif references to the important components that are not coupled to the
 *   interface.
 *
 * @see \Drupal\block\BlockBase
 */
interface BlockPluginInterface {

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
   * Constructs the block configuration form.
   *
   * This method allows base implementations to add a generic configuration
   * form for extending block plugins.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\block\BlockFormController::form()
   * @see \Drupal\block\BlockInterace::validate()
   * @see \Drupal\block\BlockInterace::submit()
   */
  public function form($form, &$form_state);

  /**
   * Handles form validation for the block configuration form.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockFormController::validate()
   * @see \Drupal\block\BlockInterace::form()
   * @see \Drupal\block\BlockInterace::submit()
   */
  public function validate($form, &$form_state);

  /**
   * Handles form submissions for the block configuration form.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockFormController::submit()
   * @see \Drupal\block\BlockInterace::form()
   * @see \Drupal\block\BlockInterace::validate()
   */
  public function submit($form, &$form_state);

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockRenderController
   */
  public function build();

}
