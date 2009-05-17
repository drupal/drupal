<?php

/**
 * @file
 * Views wizard for taxonomy term views.
 */

if (module_exists('taxonomy')) {
  $plugin = array(
    'name' => 'taxonomy_term',
    'base_table' => 'taxonomy_term_data',
    'form_wizard_class' => array(
      'file' => 'views_ui_taxonomy_term_views_wizard.class.php',
      'class' => 'ViewsUiTaxonomyTermViewsWizard',
    ),
    'title' => t('Taxonomy terms'),
    'filters' => array(
    ),
    'path_field' => array(
      'id' => 'tid',
      'table' => 'taxonomy_term_data',
      'field' => 'tid',
      'exclude' => TRUE,
      'alter' => array(
        'alter_text' => 1,
        'text' => 'taxonomy/term/[tid]',
      ),
    ),
  );
}
