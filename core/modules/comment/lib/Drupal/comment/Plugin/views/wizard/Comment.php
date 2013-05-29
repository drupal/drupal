<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\wizard\Comment.
 */

namespace Drupal\comment\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating comment views with the wizard.
 *
 * @Plugin(
 *   id = "comment",
 *   module = "comment",
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
   * Set default values for the path field options.
   */
  protected $pathField = array(
    'id' => 'cid',
    'table' => 'comment',
    'field' => 'cid',
    'exclude' => TRUE,
    'link_to_comment' => FALSE,
    'alter' => array(
      'alter_text' => TRUE,
      'text' => 'comment/[cid]#comment-[cid]'
    ),
  );

  /**
   * Set default values for the filters.
   */
  protected $filters = array(
    'status' => array(
      'value' => TRUE,
      'table' => 'comment',
      'field' => 'status'
    ),
    'status_node' => array(
      'value' => TRUE,
      'table' => 'node_field_data',
      'field' => 'status',
      'relationship' => 'nid'
    )
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::rowStyleOptions().
   */
  protected function rowStyleOptions() {
    $options = array();
    $options['comment'] = t('comments');
    $options['fields'] = t('fields');
    return $options;
  }

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
      case 'comment':
        $style_form['row_options']['links'] = array(
          '#type' => 'select',
          '#title_display' => 'invisible',
          '#title' => t('Should links be displayed below each comment'),
          '#options' => array(
            1 => t('with links (allow users to reply to the comment, etc.)'),
            0 => t('without links'),
          ),
          '#default_value' => 1,
        );
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::page_display_options().
   */
  protected function page_display_options(array $form, array &$form_state) {
    $display_options = parent::page_display_options($form, $form_state);
    $row_plugin = isset($form_state['values']['page']['style']['row_plugin']) ? $form_state['values']['page']['style']['row_plugin'] : NULL;
    $row_options = isset($form_state['values']['page']['style']['row_options']) ? $form_state['values']['page']['style']['row_options'] : array();
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::page_display_options().
   */
  protected function block_display_options(array $form, array &$form_state) {
    $display_options = parent::block_display_options($form, $form_state);
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
      case 'comment':
        $display_options['row']['type'] = 'comment';
        $display_options['row']['options']['links'] = !empty($row_options['links']);
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
    $display_options['relationships']['nid']['id'] = 'nid';
    $display_options['relationships']['nid']['table'] = 'comment';
    $display_options['relationships']['nid']['field'] = 'nid';
    $display_options['relationships']['nid']['required'] = 1;

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Comment: Title */
    $display_options['fields']['subject']['id'] = 'subject';
    $display_options['fields']['subject']['table'] = 'comment';
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

    return $display_options;
  }

}
