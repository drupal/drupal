<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserOnlineBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a "Who's online" block.
 *
 * @todo Move this block to the Statistics module and remove its dependency on
 *   {users}.access.
 *
 * @Block(
 *   id = "user_online_block",
 *   admin_label = @Translation("Who's online"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class UserOnlineBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'properties' => array(
        'administrative' => TRUE
      ),
      'seconds_online' => 900,
      'max_list_count' => 10
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $period = drupal_map_assoc(array(30, 60, 120, 180, 300, 600, 900, 1800, 2700, 3600, 5400, 7200, 10800, 21600, 43200, 86400), 'format_interval');
    $form['user_block_seconds_online'] = array(
      '#type' => 'select',
      '#title' => t('User activity'),
      '#default_value' => $this->configuration['seconds_online'],
      '#options' => $period,
      '#description' => t('A user is considered online for this long after they have last viewed a page.')
    );
    $form['user_block_max_list_count'] = array(
      '#type' => 'select',
      '#title' => t('User list length'),
      '#default_value' => $this->configuration['max_list_count'],
      '#options' => drupal_map_assoc(array(0, 5, 10, 15, 20, 25, 30, 40, 50, 75, 100)),
      '#description' => t('Maximum number of currently online users to display.')
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['seconds_online'] = $form_state['values']['user_block_seconds_online'];
    $this->configuration['max_list_count'] = $form_state['values']['user_block_max_list_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Count users active within the defined period.
    $interval = REQUEST_TIME - $this->configuration['seconds_online'];

    // Perform database queries to gather online user lists.
    $authenticated_count = db_query("SELECT COUNT(uid) FROM {users} WHERE access >= :timestamp", array(':timestamp' => $interval))->fetchField();

    $build = array(
      '#theme' => 'item_list__user__online',
      '#prefix' => '<p>' . format_plural($authenticated_count, 'There is currently 1 user online.', 'There are currently @count users online.') . '</p>',
    );

    // Display a list of currently online users.
    $max_users = $this->configuration['max_list_count'];
    if ($authenticated_count && $max_users) {
      $uids = db_query_range('SELECT uid FROM {users} WHERE access >= :interval AND uid > 0 ORDER BY access DESC', 0, $max_users, array(':interval' => $interval))->fetchCol();
      foreach (user_load_multiple($uids) as $account) {
        $username = array(
          '#theme' => 'username',
          '#account' => $account,
        );
        $build['#items'][] = drupal_render($username);
      }
    }

    return $build;
  }

}
