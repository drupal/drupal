<?php
/**
* @file
* Contains \Drupal\devel\Plugin\block\block\DevelExecutePHP.
*/

namespace Drupal\devel\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block for executing PHP code.
 *
 *
 * @Plugin(
 *   id = "devel_execute_php",
 *   admin_label = @Translation("Execute PHP"),
 *   module = "devel"
 * )
 */
class DevelExecutePHP extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    return user_access('execute php code');
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $form_state = array();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['callback'] = array($this, 'executePhpForm');
    $form = drupal_build_form('devel_execute_block_form', $form_state);
    return array($form);
  }

  /**
   * Build the execute PHP block form.
   */
  public function executePhpForm() {
    $form['execute'] = array(
      '#type' => 'details',
      '#title' => t('Execute PHP Code'),
      '#collapsed' => (!isset($_SESSION['devel_execute_code'])),
    );
    $form['#submit'] = array('devel_execute_form_submit');
    return array_merge_recursive($form, devel_execute_form());
  }

}
