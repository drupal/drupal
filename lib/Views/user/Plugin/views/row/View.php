<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\row\View
 */

namespace Views\user\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * A row plugin which renders a user via user_view.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "user",
 *   module = "user",
 *   title = @Translation("User"),
 *   help = @Translation("Display the user with standard user view."),
 *   base = {"users"},
 *   type = "normal"
 * )
 */
class View extends RowPluginBase {

  var $base_table = 'users';
  var $base_field = 'uid';

  // Store the users to be used for pre_render.
  var $users = array();

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode'] = array('default' => 'full');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->buildOptionsForm_summary_options();
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->options['view_mode'],
     );
    $form['help']['#markup'] = t("Display the user with standard user view. It might be necessary to add a user-profile.tpl.php in your themes template folder, because the default <a href=\"@user-profile-api-link\">user-profile</a>e template don't show the username per default.", array('@user-profile-api-link' => url('http://api.drupal.org/api/drupal/modules--user--user-profile.tpl.php/7')));
  }

    /**
     * Return the main options, which are shown in the summary title.
     */
    public function buildOptionsForm_summary_options() {
      $entity_info = entity_get_info('user');
      $options = array();
      if (!empty($entity_info['view modes'])) {
        foreach ($entity_info['view modes'] as $mode => $settings) {
          $options[$mode] = $settings['label'];
        }
      }
      if (empty($options)) {
        $options = array(
          'full' => t('User account')
        );
      }

      return $options;
    }

    public function summaryTitle() {
      $options = $this->buildOptionsForm_summary_options();
      return check_plain($options[$this->options['view_mode']]);
    }

  function pre_render($values) {
    $uids = array();
    foreach ($values as $row) {
      $uids[] = $row->{$this->field_alias};
    }
    $this->users = user_load_multiple($uids);
  }

  function render($row) {
    $account = $this->users[$row->{$this->field_alias}];
    $account->view = $this->view;
    $build = user_view($account, $this->options['view_mode']);

    return drupal_render($build);
  }

}
