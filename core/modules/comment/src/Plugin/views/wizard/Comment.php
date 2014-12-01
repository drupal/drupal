<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\wizard\Comment.
 */

namespace Drupal\comment\Plugin\views\wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating comment views with the wizard.
 *
 * @ViewsWizard(
 *   id = "comment",
 *   base_table = "comment",
 *   title = @Translation("Comments")
 * )
 */
class Comment extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

  /**
   * Set default values for the filters.
   */
  protected $filters = array(
    'status' => array(
      'value' => TRUE,
      'table' => 'comment_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
    ),
    'status_node' => array(
      'value' => TRUE,
      'table' => 'node_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'relationship' => 'node',
    ),
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::rowStyleOptions().
   */
  protected function rowStyleOptions() {
    $options = array();
    $options['comment'] = $this->t('comments');
    $options['fields'] = $this->t('fields');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function pageDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::pageDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(array('page', 'style', 'row_plugin'));
    $row_options = $form_state->getValue(array('page', 'style', 'row_options'), array());
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::blockDisplayOptions().
   */
  protected function blockDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::blockDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(array('block', 'style', 'row_plugin'));
    $row_options = $form_state->getValue(array('block', 'style', 'row_options'), array());
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Set the row style and row style plugins to the display_options.
   */
  protected  function display_options_row(&$display_options, $row_plugin, $row_options) {
    switch ($row_plugin) {
      case 'comment':
        $display_options['row']['type'] = 'entity:comment';
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

    // Add a relationship to nodes.
    $display_options['relationships']['node']['id'] = 'node';
    $display_options['relationships']['node']['table'] = 'comment_field_data';
    $display_options['relationships']['node']['field'] = 'node';
    $display_options['relationships']['node']['required'] = 1;
    $display_options['relationships']['node']['plugin_id'] = 'standard';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Comment: Title */
    $display_options['fields']['subject']['id'] = 'subject';
    $display_options['fields']['subject']['table'] = 'comment_field_data';
    $display_options['fields']['subject']['field'] = 'subject';
    $display_options['fields']['subject']['label'] = '';
    $display_options['fields']['subject']['alter']['alter_text'] = 0;
    $display_options['fields']['subject']['alter']['make_link'] = 0;
    $display_options['fields']['subject']['alter']['absolute'] = 0;
    $display_options['fields']['subject']['alter']['trim'] = 0;
    $display_options['fields']['subject']['alter']['word_boundary'] = 0;
    $display_options['fields']['subject']['alter']['ellipsis'] = 0;
    $display_options['fields']['subject']['alter']['strip_tags'] = 0;
    $display_options['fields']['subject']['alter']['html'] = 0;
    $display_options['fields']['subject']['hide_empty'] = 0;
    $display_options['fields']['subject']['empty_zero'] = 0;
    $display_options['fields']['subject']['link_to_comment'] = 1;
    $display_options['fields']['subject']['plugin_id'] = 'comment';

    return $display_options;
  }

}
