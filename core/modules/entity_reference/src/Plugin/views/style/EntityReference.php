<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\views\style\EntityReference.
 */

namespace Drupal\entity_reference\Plugin\views\style;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * EntityReference style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "entity_reference",
 *   title = @Translation("Entity Reference list"),
 *   help = @Translation("Returns results as a PHP array of labels and rendered rows."),
 *   theme = "views_view_unformatted",
 *   register_theme = FALSE,
 *   display_types = {"entity_reference"}
 * )
 */
class EntityReference extends StylePluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowPlugin.
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesFields.
   */
  protected $usesFields = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesGrouping.
   */
  protected $usesGrouping = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase\StylePluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['search_fields'] = array('default' => NULL);

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase\StylePluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->displayHandler->getFieldLabels(TRUE);
    $form['search_fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Search fields'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->options['search_fields'],
      '#description' => t('Select the field(s) that will be searched when using the autocomplete widget.'),
      '#weight' => -3,
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase\StylePluginBase::render().
   */
  public function render() {
    if (!empty($this->view->live_preview)) {
      return parent::render();
    }

    // Group the rows according to the grouping field, if specified.
    $sets = $this->renderGrouping($this->view->result, $this->options['grouping']);

    // Grab the alias of the 'id' field added by
    // entity_reference_plugin_display.
    $id_field_alias = $this->view->storage->get('base_field');

    // @todo We don't display grouping info for now. Could be useful for select
    // widget, though.
    $results = array();
    foreach ($sets as $records) {
      foreach ($records as $values) {
        // Sanitize HTML, remove line breaks and extra whitespace.
        $output = $this->view->rowPlugin->render($values);
        $output = drupal_render($output);
        $results[$values->{$id_field_alias}] = Xss::filterAdmin(preg_replace('/\s\s+/', ' ', str_replace("\n", '', $output)));
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }
}
