<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\wizard\Node.
 */

namespace Drupal\node\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating node views with the wizard.
 *
 * @ViewsWizard(
 *   id = "node",
 *   base_table = "node",
 *   title = @Translation("Content")
 * )
 */
class Node extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'node_field_data-created';

  /**
   * Set default values for the path field options.
   */
  protected $pathField = array(
    'id' => 'nid',
    'table' => 'node',
    'field' => 'nid',
    'exclude' => TRUE,
    'link_to_node' => FALSE,
    'alter' => array(
      'alter_text' => TRUE,
      'text' => 'node/[nid]'
    )
  );

  /**
   * Set default values for the filters.
   */
  protected $filters = array(
    'status' => array(
      'value' => TRUE,
      'table' => 'node_field_data',
      'field' => 'status',
      'provider' => 'node'
    )
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::getAvailableSorts().
   *
   * @return array
   */
  public function getAvailableSorts() {
    // You can't execute functions in properties, so override the method
    return array(
      'node_field_data-title:DESC' => t('Title')
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::rowStyleOptions().
   */
  protected function rowStyleOptions() {
    $options = array();
    $options['teasers'] = t('teasers');
    $options['full_posts'] = t('full posts');
    $options['titles'] = t('titles');
    $options['titles_linked'] = t('titles (linked)');
    $options['fields'] = t('fields');
    return $options;
  }

  /**
   * Adds the style options to the wizard form.
   *
   * @param array $form
   *   The full wizard form array.
   * @param array $form_state
   *   The current state of the wizard form.
   * @param string $type
   *   The display ID (e.g. 'page' or 'block').
   */
  protected function buildFormStyle(array &$form, array &$form_state, $type) {
    parent::buildFormStyle($form, $form_state, $type);
    $style_form =& $form['displays'][$type]['options']['style'];
    // Some style plugins don't support row plugins so stop here if that's the
    // case.
    if (!isset($style_form['row_plugin']['#default_value'])) {
      return;
    }
    $row_plugin = $style_form['row_plugin']['#default_value'];
    switch ($row_plugin) {
      case 'full_posts':
      case 'teasers':
        $style_form['row_options']['links'] = array(
          '#type' => 'select',
          '#title' => t('Should links be displayed below each node'),
          '#title_display' => 'invisible',
          '#options' => array(
            1 => t('with links (allow users to add comments, etc.)'),
            0 => t('without links'),
          ),
          '#default_value' => 1,
        );
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayOptions().
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['provider'] = 'user';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    // Add the title field, so that the display has content if the user switches
    // to a row style that uses fields.
    /* Field: Content: Title */
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'node_field_data';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['provider'] = 'node';
    $display_options['fields']['title']['label'] = '';
    $display_options['fields']['title']['alter']['alter_text'] = 0;
    $display_options['fields']['title']['alter']['make_link'] = 0;
    $display_options['fields']['title']['alter']['absolute'] = 0;
    $display_options['fields']['title']['alter']['trim'] = 0;
    $display_options['fields']['title']['alter']['word_boundary'] = 0;
    $display_options['fields']['title']['alter']['ellipsis'] = 0;
    $display_options['fields']['title']['alter']['strip_tags'] = 0;
    $display_options['fields']['title']['alter']['html'] = 0;
    $display_options['fields']['title']['hide_empty'] = 0;
    $display_options['fields']['title']['empty_zero'] = 0;
    $display_options['fields']['title']['link_to_node'] = 1;

    return $display_options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayFiltersUser().
   */
  protected function defaultDisplayFiltersUser(array $form, array &$form_state) {
    $filters = parent::defaultDisplayFiltersUser($form, $form_state);

    if (!empty($form_state['values']['show']['tagged_with']['tids'])) {
      $filters['tid'] = array(
        'id' => 'tid',
        'table' => 'taxonomy_index',
        'field' => 'tid',
        'value' => $form_state['values']['show']['tagged_with']['tids'],
        'vocabulary' => $form_state['values']['show']['tagged_with']['vocabulary'],
      );
      // If the user entered more than one valid term in the autocomplete
      // field, they probably intended both of them to be applied.
      if (count($form_state['values']['show']['tagged_with']['tids']) > 1) {
        $filters['tid']['operator'] = 'and';
        // Sort the terms so the filter will be displayed as it normally would
        // on the edit screen.
        sort($filters['tid']['value']);
      }
    }

    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  protected function pageDisplayOptions(array $form, array &$form_state) {
    $display_options = parent::pageDisplayOptions($form, $form_state);
    $row_plugin = isset($form_state['values']['page']['style']['row_plugin']) ? $form_state['values']['page']['style']['row_plugin'] : NULL;
    $row_options = isset($form_state['values']['page']['style']['row_options']) ? $form_state['values']['page']['style']['row_options'] : array();
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockDisplayOptions(array $form, array &$form_state) {
    $display_options = parent::blockDisplayOptions($form, $form_state);
    $row_plugin = isset($form_state['values']['block']['style']['row_plugin']) ? $form_state['values']['block']['style']['row_plugin'] : NULL;
    $row_options = isset($form_state['values']['block']['style']['row_options']) ? $form_state['values']['block']['style']['row_options'] : array();
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Set the row style and row style plugins to the display_options.
   */
  protected  function display_options_row(&$display_options, $row_plugin, $row_options) {
    switch ($row_plugin) {
      case 'full_posts':
        $display_options['row']['type'] = 'entity:node';
        $display_options['row']['options']['build_mode'] = 'full';
        $display_options['row']['options']['links'] = !empty($row_options['links']);
        break;
      case 'teasers':
        $display_options['row']['type'] = 'entity:node';
        $display_options['row']['options']['build_mode'] = 'teaser';
        $display_options['row']['options']['links'] = !empty($row_options['links']);
        break;
      case 'titles_linked':
        $display_options['row']['type'] = 'fields';
        $display_options['field']['title']['link_to_node'] = 1;
        break;
      case 'titles':
        $display_options['row']['type'] = 'fields';
        $display_options['field']['title']['link_to_node'] = 0;
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::buildFilters().
   *
   * Add some options for filter by taxonomy terms.
   */
  protected function buildFilters(&$form, &$form_state) {
    parent::buildFilters($form, $form_state);

    $selected_bundle = static::getSelected($form_state, array('show', 'type'), 'all', $form['displays']['show']['type']);

    // Add the "tagged with" filter to the view.

    // We construct this filter using taxonomy_index.tid (which limits the
    // filtering to a specific vocabulary) rather than taxonomy_term_data.name
    // (which matches terms in any vocabulary). This is because it is a more
    // commonly-used filter that works better with the autocomplete UI, and
    // also to avoid confusion with other vocabularies on the site that may
    // have terms with the same name but are not used for free tagging.

    // The downside is that if there *is* more than one vocabulary on the site
    // that is used for free tagging, the wizard will only be able to make the
    // "tagged with" filter apply to one of them (see below for the method it
    // uses to choose).

    // Find all "tag-like" taxonomy fields associated with the view's
    // entities. If a particular entity type (i.e., bundle) has been
    // selected above, then we only search for taxonomy fields associated
    // with that bundle. Otherwise, we use all bundles.
    $bundles = array_keys(entity_get_bundles($this->entityTypeId));
    // Double check that this is a real bundle before using it (since above
    // we added a dummy option 'all' to the bundle list on the form).
    if (isset($selected_bundle) && in_array($selected_bundle, $bundles)) {
      $bundles = array($selected_bundle);
    }
    $tag_fields = array();
    foreach ($bundles as $bundle) {
      $display = entity_get_form_display($this->entityTypeId, $bundle, 'default');
      $taxonomy_fields = array_filter(\Drupal::entityManager()->getFieldDefinitions($this->entityTypeId, $bundle), function ($field_definition) {
        return $field_definition->getType() == 'taxonomy_term_reference';
      });
      foreach ($taxonomy_fields as $field_name => $instance) {
        $widget = $display->getComponent($field_name);
        // We define "tag-like" taxonomy fields as ones that use the
        // "Autocomplete term widget (tagging)" widget.
        if ($widget['type'] == 'taxonomy_autocomplete') {
          $tag_fields[] = $field_name;
        }
      }
    }
    $tag_fields = array_unique($tag_fields);
    if (!empty($tag_fields)) {
      // If there is more than one "tag-like" taxonomy field available to
      // the view, we can only make our filter apply to one of them (as
      // described above). We choose 'field_tags' if it is available, since
      // that is created by the Standard install profile in core and also
      // commonly used by contrib modules; thus, it is most likely to be
      // associated with the "main" free-tagging vocabulary on the site.
      if (in_array('field_tags', $tag_fields)) {
        $tag_field_name = 'field_tags';
      }
      else {
        $tag_field_name = reset($tag_fields);
      }
      // Add the autocomplete textfield to the wizard.
      $form['displays']['show']['tagged_with'] = array(
        '#type' => 'textfield',
        '#title' => t('tagged with'),
        '#autocomplete_route_name' => 'taxonomy.autocomplete',
        '#autocomplete_route_parameters' => array(
          'entity_type' => $this->entityTypeId,
          'field_name' => $tag_field_name,
        ),
        '#size' => 30,
        '#maxlength' => 1024,
        '#entity_type' => $this->entityTypeId,
        '#field_name' => $tag_field_name,
        '#element_validate' => array('views_ui_taxonomy_autocomplete_validate'),
      );
    }
  }

}
