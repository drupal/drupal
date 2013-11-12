<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserNewBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a "Who's new" block.
 *
 * @Block(
 *   id = "user_new_block",
 *   admin_label = @Translation("Who's new"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class UserNewBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'properties' => array(
        'administrative' => TRUE
      ),
      'whois_new_count' => 5
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
