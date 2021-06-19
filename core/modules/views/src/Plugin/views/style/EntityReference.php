<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

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
  protected $usesGrouping = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['search_fields'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->displayHandler->getFieldLabels(TRUE);
    $form['search_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Search fields'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->options['search_fields'],
      '#description' => $this->t('Select the field(s) that will be searched when using the autocomplete widget.'),
      '#weight' => -3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!empty($this->view->live_preview)) {
      return parent::render();
    }

    // Group the rows according to the grouping field, if specified.
    $sets = $this->renderGrouping($this->view->result, $this->options['grouping'], TRUE);

    // Grab the alias of the 'id' field added by
    // entity_reference_plugin_display.
    $id_field_alias = $this->view->storage->get('base_field');

    // @todo We don't display grouping info for now. Could be useful for select
    // widget, though.
    $results = [];
    foreach ($sets as $set) {
      $this->extractResultsFromGroup($set, $id_field_alias, $results);
    }
    return $results;
  }

  /**
   * Extract row results from groupings.
   *
   * @param array $grouping
   *   The grouping array, containing 'group' and 'rows' keys.
   * @param $id_field_alias
   *   The id field's alias.
   * @param array $results
   *   The results, returned by reference.
   * @param array $parent_groups
   *   The parent groups.
   */
  protected function extractResultsFromGroup(array $grouping, $id_field_alias, array &$results, array $parent_groups = []) {
    // If there is no grouping, $group === ''.
    if (!empty($grouping['group'])) {
      $parent_groups[] = $grouping['group'];
    }
    foreach ($grouping['rows'] as $row) {
      if (is_array($row)) {
        $this->extractResultsFromGroup($row, $id_field_alias, $results, $parent_groups);
      }
      elseif ($row instanceof ResultRow) {
        $results[$row->{$id_field_alias}] = $this->view->rowPlugin->render($row);
        // Sigh, expose grouping info while retaining BC.
        // @see \Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection::stripAdminAndAnchorTagsFromResults
        $results[$row->{$id_field_alias}]['#_entity_reference_option_groups'] = $parent_groups;
        // Sanitize HTML, remove line breaks and extra whitespace.
        $results[$row->{$id_field_alias}]['#post_render'][] = function ($html, array $elements) {
          return Xss::filterAdmin(preg_replace('/\s\s+/', ' ', str_replace("\n", '', $html)));
        };
      }
      else {
        throw new \UnexpectedValueException('Unexpected row value.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }

}
