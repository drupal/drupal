<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Block\UserLoginBlock.
 */

namespace Drupal\user\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\Form\UserLoginForm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'User login' block.
 *
 * @Plugin(
 *   id = "user_login_block",
 *   admin_label = @Translation("User login"),
 *   module = "user"
 * )
 */
class UserLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The DI Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new UserLoginBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI Container.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ContainerInterface $container, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->container = $container;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container,
      $container->get('request')
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return (!$GLOBALS['user']->id() && !(arg(0) == 'user' && !is_numeric(arg(1))));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = drupal_get_form(UserLoginForm::create($this->container), $this->request);
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
