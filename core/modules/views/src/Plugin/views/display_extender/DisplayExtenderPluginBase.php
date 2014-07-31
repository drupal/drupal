<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase.
 */

namespace Drupal\views\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_display_extender_plugins Views display extender plugins
 * @{
 * Plugins that offer additional display options across display types.
 *
 * Display extender plugins allow additional options or configuration to be
 * added to views across all display types. For example, if you wanted to allow
 * site users to add certain metadata to the rendered output of every view
 * display regardless of display type, you could provide this option as a
 * display extender.
 *
 * Display extender plugins extend
 * \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase.
 * They must be annotated with
 * \Drupal\views\Annotation\ViewsDisplayExtender annotation, and they
 * must be in namespace directory Plugin\views\display_extender.
 *
 * @ingroup views_plugins
 *
 * @see plugin_api
 * @see views_display_plugins
 */

/**
 * Base class for Views display extender plugins.
 */
abstract class DisplayExtenderPluginBase extends PluginBase {

  /**
   * Provide a form to edit options for this plugin.
   */
  public function defineOptionsAlter(&$options) { }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() { }

  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() { }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) { }

  /**
   * Static member function to list which sections are defaultable
   * and what items each section contains.
   */
  public function defaultableSections(&$sections, $section = NULL) { }

}

/**
 * @}
 */
