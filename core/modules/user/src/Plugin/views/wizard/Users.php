<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\wizard\Users.
 */

namespace Drupal\user\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating user views with the wizard.
 *
 * @ViewsWizard(
 *   id = "users",
 *   base_table = "users_field_data",
 *   title = @Translation("Users")
 * )
 */
class Users extends WizardPluginBase {

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
      'table' => 'users_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'entity_type' => 'user',
      'entity_field' => 'status',
    )
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayOptions().
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access user profiles';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: User: Name */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'users_field_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'user';
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
    $display_options['fields']['name']['link_to_user'] = 1;
    $display_options['fields']['name']['overwrite_anonymous'] = 0;
    $display_options['fields']['name']['plugin_id'] = 'user_name';

    return $display_options;
  }

}
