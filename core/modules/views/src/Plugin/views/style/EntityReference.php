<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;

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
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['search_fields'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->displayHandler->getFieldLabels(TRUE);
    $form['search_fields'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Search fields'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->options['search_fields'],
      '#description' => $this->t('Select the field(s) that will be searched when using the autocomplete widget.'),
      '#weight' => -3,
    );
  }

  /**
   * {@inheritdoc}
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
        $results[$values->{$id_field_alias}] = $this->view->rowPlugin->render($values);
        // Sanitize HTML, remove line breaks and extra whitespace.
        $results[$values->{$id_field_alias}]['#post_render'][] = function ($html, array $elements) {
          return Xss::filterAdmin(preg_replace('/\s\s+/', ' ', str_replace("\n", '', $html)));
        };
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
