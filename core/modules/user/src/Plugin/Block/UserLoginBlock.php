<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserLoginBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

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
  protected function blockAccess(AccountInterface $account) {
    $route_name = \Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_NAME);
    return ($account->isAnonymous() && !in_array($route_name, array('user.register', 'user.login', 'user.logout')));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');
    unset($form['name']['#attributes']['autofocus']);
    unset($form['name']['#description']);
    unset($form['pass']['#description']);
    $form['name']['#size'] = 15;
    $form['pass']['#size'] = 15;
    $form['#action'] = _url(current_path(), array('query' => drupal_get_destination(), 'external' => FALSE));
    // Build action links.
    $items = array();
    if (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = \Drupal::l(t('Create new account'), new Url('user.register', array(), array(
        'attributes' => array(
          'title' => t('Create a new user account.'),
          'class' => array('create-account-link'),
        ),
      )));
    }
    $items['request_password'] = \Drupal::l(t('Request new password'), new Url('user.pass', array(), array(
      'attributes' => array(
        'title' => t('Request new password via email.'),
        'class' => array('request-password-link'),
      ),
    )));
    return array(
      'user_login_form' => $form,
      'user_links' => array(
        '#theme' => 'item_list',
        '#items' => $items,
      ),
    );
  }

}
