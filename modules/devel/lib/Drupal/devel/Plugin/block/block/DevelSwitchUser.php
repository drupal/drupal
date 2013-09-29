<?php
/**
* @file
* Contains \Drupal\devel\Plugin\block\block\DevelSwitchUser.
*/

namespace Drupal\devel\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block for switching users.
 *
 *
 * @Plugin(
 *   id = "devel_switch_user",
 *   admin_label = @Translation("Switch user"),
 *   module = "devel"
 * )
 */
class DevelSwitchUser extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    // By default, the block will contain 12 users.
    return array(
      'list_size' => 12,
      'include_anon' => TRUE,
      'show_form' => TRUE,
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['list_size'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of users to display in the list'),
      '#default_value' => $this->configuration['list_size'],
      '#size' => '3',
      '#maxlength' => '4',
    );
    $form['include_anon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include %anonymous', array('%anonymous' => user_format_name(drupal_anonymous_user()))),
      '#default_value' => $this->configuration['include_anon'],
    );
    $form['show_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow entering any user name'),
      '#default_value' => $this->configuration['show_form'],
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['list_size'] = $form_state['values']['list_size'];
    $this->configuration['include_anon'] = $form_state['values']['include_anon'];
    $this->configuration['show_form'] = $form_state['values']['show_form'];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    return user_access('switch users');
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $links = $this->switchUserList();
    if (!empty($links)) {
      drupal_add_css(drupal_get_path('module', 'devel') . '/css/devel.css');
      $build = array(
        'devel_links' => array('#theme' => 'links', '#links' => $links),
      );
      if ($this->configuration['show_form']) {
        $form_state = array();
        $form_state['build_info']['args'] = array();
        $form_state['build_info']['callback'] = array($this, 'switchForm');
        $build['devel_form'] = drupal_build_form('devel_switch_user_form', $form_state);
      }
      return $build;
    }
  }

  /**
   * Provides the Switch user form.
   */
  public function switchForm() {
    $form['username'] = array(
      '#type' => 'textfield',
      '#description' => t('Enter username'),
      '#autocomplete_path' => 'user/autocomplete',
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#size' => 16,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Switch'),
      '#button_type' => 'primary',
    );
    $form['#attributes'] = array('class' => array('clearfix'));
    return $form;
  }

  /**
   * Provides the Switch user list.
   */
  public function switchUserList() {
    return devel_switch_user_list($this->configuration['list_size'], $this->configuration['include_anon']);
  }

}
