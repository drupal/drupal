<?php

namespace Drupal\views\Plugin\views;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface for all views plugins.
 */
interface ViewsPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Returns the plugin provider.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Return the human readable name of the display.
   *
   * This appears on the ui beside each plugin and beside the settings link.
   */
  public function pluginTitle();

  /**
   * Returns the usesOptions property.
   */
  public function usesOptions();

  /**
   * Filter out stored options depending on the defined options.
   *
   * @param array $storage
   *   The stored options.
   */
  public function filterByDefinedOptions(array &$storage);

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Returns the summary of the settings in the display.
   */
  public function summaryTitle();

  /**
   * Moves form elements into fieldsets for presentation purposes.
   *
   * Many views forms use #tree = TRUE to keep their values in a hierarchy for
   * easier storage. Moving the form elements into fieldsets during form
   * building would break up that hierarchy. Therefore, we wait until the
   * pre_render stage, where any changes we make affect presentation only and
   * aren't reflected in $form_state->getValues().
   *
   * @param array $form
   *   The form build array to alter.
   *
   * @return array
   *   The form build array.
   */
  public static function preRenderAddFieldsetMarkup(array $form);

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition);

  /**
   * Initialize the plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The display handler.
   * @param array $options
   *   The options configured for this plugin.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL);

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Adds elements for available core tokens to a form.
   *
   * @param array $form
   *   The form array to alter, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function globalTokenForm(&$form, FormStateInterface $form_state);

  /**
   * Returns an array of available token replacements.
   *
   * @param bool $prepared
   *   Whether to return the raw token info for each token or an array of
   *   prepared tokens for each type. E.g. "[view:name]".
   * @param array $types
   *   An array of additional token types to return, defaults to 'site' and
   *   'view'.
   *
   * @return array
   *   An array of available token replacement info or tokens, grouped by type.
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = []);

  /**
   * Flattens the structure of form elements.
   *
   * If a form element has #flatten = TRUE, then all of it's children get moved
   * to the same level as the element itself. So $form['to_be_flattened'][$key]
   * becomes $form[$key], and $form['to_be_flattened'] gets unset.
   *
   * @param array $form
   *   The form build array to alter.
   *
   * @return array
   *   The form build array.
   */
  public static function preRenderFlattenData($form);

  /**
   * Returns a string with any core tokens replaced.
   *
   * @param string $string
   *   The string to preform the token replacement on.
   * @param array $options
   *   An array of options, as passed to \Drupal\Core\Utility\Token::replace().
   *
   * @return string
   *   The tokenized string.
   */
  public function globalTokenReplace($string = '', array $options = []);

  /**
   * Clears a plugin.
   */
  public function destroy();

  /**
   * Validate that the plugin is correct and can be saved.
   *
   * @return
   *   An array of error strings to tell the user what is wrong with this
   *   plugin.
   */
  public function validate();

  /**
   * Add anything to the query that we might need to.
   */
  public function query();

  /**
   * Unpack options over our existing defaults, drilling down into arrays
   * so that defaults don't get totally blown away.
   */
  public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE);

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Provide a full list of possible theme templates used by this style.
   */
  public function themeFunctions();

}
