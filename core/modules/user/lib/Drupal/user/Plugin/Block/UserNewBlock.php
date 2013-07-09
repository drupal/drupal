<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserNewBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a "Who's new" block.
 *
 * @Plugin(
 *   id = "user_new_block",
 *   admin_label = @Translation("Who's new"),
 *   module = "user"
 * )
 */
class UserNewBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    return array(
      'properties' => array(
        'administrative' => TRUE
      ),
      'whois_new_count' => 5
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return user_access('access content');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['user_block_whois_new_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of users to display'),
      '#default_value' => $this->configuration['whois_new_count'],
      '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10)),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['whois_new_count'] = $form_state['values']['user_block_whois_new_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve a list of new users who have accessed the site successfully.
    $uids = db_query_range('SELECT uid FROM {users} WHERE status <> 0 AND access <> 0 ORDER BY created DESC', 0, $this->configuration['whois_new_count'])->fetchCol();
    $build = array(
      '#theme' => 'item_list__user__new',
      '#items' => array(),
    );
    foreach (user_load_multiple($uids) as $account) {
      $username = array(
        '#theme' => 'username',
        '#account' => $account,
      );
      $build['#items'][] = drupal_render($username);
    }
    return $build;
  }

}
