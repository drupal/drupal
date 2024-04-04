<?php

namespace Drupal\media\Plugin\views\wizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsWizard;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Provides Views creation wizard for Media revisions.
 */
#[ViewsWizard(
  id: 'media_revision',
  title: new TranslatableMarkup('Media revisions'),
  base_table: 'media_field_revision'
)]
class MediaRevision extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'media_field_revision-created';

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

    // Add the changed field.
    $display_options['fields']['changed']['id'] = 'changed';
    $display_options['fields']['changed']['table'] = 'media_field_revision';
    $display_options['fields']['changed']['field'] = 'changed';
    $display_options['fields']['changed']['entity_type'] = 'media';
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

    // Add the name field.
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'media_field_revision';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'media';
    $display_options['fields']['name']['entity_field'] = 'name';
    $display_options['fields']['name']['label'] = '';
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['settings']['link_to_entity'] = 0;
    $display_options['fields']['name']['plugin_id'] = 'field';

    return $display_options;
  }

}
