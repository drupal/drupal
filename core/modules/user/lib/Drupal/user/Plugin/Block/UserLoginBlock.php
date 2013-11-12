<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserLoginBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\block\BlockBase;

/**
 * Provides a 'User login' block.
 *
 * @Block(
 *   id = "user_login_block",
 *   admin_label = @Translation("User login"),
 *   category = @Translation("Forms")
 * )
 */
class UserLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return (!$account->id() && !(arg(0) == 'user' && !is_numeric(arg(1))));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = drupal_get_form('Drupal\user\Form\UserLoginForm');
    unset($form['name']['#attributes']['autofocus']);
    unset($form['name']['#description']);
    unset($form['pass']['#description']);
    $form['name']['#size'] = 15;
    $form['pass']['#size'] = 15;
    $form['#action'] = url(current_path(), array('query' => drupal_get_destination(), 'external' => FALSE));
    // Build action links.
    $items = array();
    if (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = l(t('Create new account'), 'user/register', array(
        'attributes' => array(
          'title' => t('Create a new user account.'),
          'class' => array('create-account-link'),
        ),
      ));
    }
    $items['request_password'] = l(t('Request new password'), 'user/password', array(
      'attributes' => array(
        'title' => t('Request new password via e-mail.'),
        'class' => array('request-password-link'),
      ),
    ));
    return array(
      'user_login_form' => $form,
      'user_links' => array(
        '#theme' => 'item_list',
        '#items' => $items,
      ),
    );
  }

}
