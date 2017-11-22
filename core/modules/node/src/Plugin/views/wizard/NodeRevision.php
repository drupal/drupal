<?php

namespace Drupal\node\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating node revision views with the wizard.
 *
 * @ViewsWizard(
 *   id = "node_revision",
 *   base_table = "node_field_revision",
 *   title = @Translation("Content revisions")
 * )
 */
class NodeRevision extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'changed';

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::rowStyleOptions().
   *
   * Node revisions do not support full posts or teasers, so remove them.
   */
  protected function rowStyleOptions() {
    $options = parent::rowStyleOptions();
    unset($options['teasers']);
    unset($options['full_posts']);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'view all revisions';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Content revision: Created date */
    $display_options['fields']['changed']['id'] = 'changed';
    $display_options['fields']['changed']['table'] = 'node_field_revision';
    $display_options['fields']['changed']['field'] = 'changed';
    $display_options['fields']['changed']['entity_type'] = 'node';
    $display_options['fields']['changed']['entity_field'] = 'changed';
    $display_options['fields']['changed']['alter']['alter_text'] = FALSE;
    $display_options['fields']['changed']['alter']['make_link'] = FALSE;
    $display_options['fields']['changed']['alter']['absolute'] = FALSE;
    $display_options['fields']['changed']['alter']['trim'] = FALSE;
    $display_options['fields']['changed']['alter']['word_boundary'] = FALSE;
    $display_options['fields']['changed']['alter']['ellipsis'] = FALSE;
    $display_options['fields']['changed']['alter']['strip_tags'] = FALSE;
    $display_options['fields']['changed']['alter']['html'] = FALSE;
    $display_options['fields']['changed']['hide_empty'] = FALSE;
    $display_options['fields']['changed']['empty_zero'] = FALSE;
    $display_options['fields']['changed']['plugin_id'] = 'field';
    $display_options['fields']['changed']['type'] = 'timestamp';
    $display_options['fields']['changed']['settings']['date_format'] = 'medium';
    $display_options['fields']['changed']['settings']['custom_date_format'] = '';
    $display_options['fields']['changed']['settings']['timezone'] = '';

    /* Field: Content revision: Title */
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'node_field_revision';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['entity_type'] = 'node';
    $display_options['fields']['title']['entity_field'] = 'title';
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
    $display_options['fields']['title']['settings']['link_to_entity'] = 0;
    $display_options['fields']['title']['plugin_id'] = 'field';
    return $display_options;
  }

}
