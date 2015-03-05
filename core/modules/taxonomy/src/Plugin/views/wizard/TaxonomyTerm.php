<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\wizard\TaxonomyTerm.
 */

namespace Drupal\taxonomy\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Tests creating taxonomy views with the wizard.
 *
 * @ViewsWizard(
 *   id = "taxonomy_term",
 *   base_table = "taxonomy_term_field_data",
 *   title = @Translation("Taxonomy terms")
 * )
 */
class TaxonomyTerm extends WizardPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayOptions().
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access content';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Taxonomy: Term */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'taxonomy_term_field_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'taxonomy_term';
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
    $display_options['fields']['name']['link_to_taxonomy'] = 1;
    $display_options['fields']['name']['plugin_id'] = 'taxonomy';

    return $display_options;
  }

}
