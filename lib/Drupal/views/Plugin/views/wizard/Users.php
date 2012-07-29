<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\wizard\Users.
 */

namespace Drupal\views\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating user views with the wizard.
 *
 * @Plugin(
 *   plugin_id = "users",
 *   base_table = "users",
 *   created_column = "created",
 *   title = @Translation("Users"),
 *   filters = {
 *     "status" = {
 *       "value" = 1,
 *       "table" = "users",
 *       "field" = "status"
 *     }
 *   },
 *   path_field = {
 *     "id" = "uid",
 *     "table" = "users",
 *     "field" = "uid",
 *     "exclude" = TRUE,
 *     "link_to_user" = FALSE,
 *     "alter" = {
 *       "alter_text" = 1,
 *       "text" = "user/[uid]"
 *     }
 *   }
 * )
 */
class Users extends WizardPluginBase {
  protected function default_display_options($form, $form_state) {
    $display_options = parent::default_display_options($form, $form_state);

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['perm'] = 'access user profiles';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: User: Name */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'users';
    $display_options['fields']['name']['field'] = 'name';
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

    return $display_options;
  }
}
