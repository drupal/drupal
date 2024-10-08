<?php

namespace Drupal\user\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User login' block.
 */
#[Block(
  id: "user_login_block",
  admin_label: new TranslatableMarkup("User login"),
  category: new TranslatableMarkup("Forms")
)]
class UserLoginBlock extends BlockBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  use RedirectDestinationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new UserLoginBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    protected ?FormBuilderInterface $formBuilder = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    if (!$formBuilder) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $formBuilder argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3159776', E_USER_DEPRECATED);
      $this->formBuilder = \Drupal::service('form_builder');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    if ($account->isAnonymous() && !in_array($route_name, ['user.login', 'user.logout'])) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm(UserLoginForm::class);
    unset($form['name']['#attributes']['autofocus']);
    $form['name']['#size'] = 15;
    $form['pass']['#size'] = 15;

    // Instead of setting an actual action URL, we set the placeholder, which
    // will be replaced at the very last moment. This ensures forms with
    // dynamically generated action URLs don't have poor cacheability.
    // Use the proper API to generate the placeholder, when we have one. See
    // https://www.drupal.org/node/2562341. The placeholder uses a fixed string
    // that is
    // Crypt::hashBase64('\Drupal\user\Plugin\Block\UserLoginBlock::build');
    // This is based on the implementation in
    // \Drupal\Core\Form\FormBuilder::prepareForm(), but the user login block
    // requires different behavior for the destination query argument.
    // cspell:disable-next-line
    $placeholder = 'form_action_p_4r8ITd22yaUvXM6SzwrSe9rnQWe48hz9k1Sxto3pBvE';

    $form['#attached']['placeholders'][$placeholder] = [
      '#lazy_builder' => ['\Drupal\user\Plugin\Block\UserLoginBlock::renderPlaceholderFormAction', []],
    ];
    $form['#action'] = $placeholder;

    // Build action links.
    $items = [];
    if (\Drupal::config('user.settings')->get('register') != UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = [
        '#type' => 'link',
        '#title' => $this->t('Create new account'),
        '#url' => Url::fromRoute('user.register', [], [
          'attributes' => [
            'title' => $this->t('Create a new user account.'),
            'class' => ['create-account-link'],
          ],
        ]),
      ];
    }
    $items['request_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset your password'),
      '#url' => Url::fromRoute('user.pass', [], [
        'attributes' => [
          'title' => $this->t('Send password reset instructions via email.'),
          'class' => ['request-password-link'],
        ],
      ]),
    ];
    return [
      'user_login_form' => $form,
      'user_links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

  /**
   * #lazy_builder callback; renders a form action URL including destination.
   *
   * @return array
   *   A renderable array representing the form action.
   *
   * @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
   */
  public static function renderPlaceholderFormAction() {
    return [
      '#type' => 'markup',
      '#markup' => UrlHelper::filterBadProtocol(Url::fromRoute('<current>', [], ['query' => \Drupal::destination()->getAsArray(), 'external' => FALSE])->toString()),
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderPlaceholderFormAction'];
  }

}
