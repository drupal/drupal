<?php

namespace Drupal\toolbar;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Fills out the placeholders generated in ToolbarHooks::userToolbar().
 */
class UserToolbarLinkBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a UserToolbarLinkBuilder object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(AccountProxyInterface $account) {
    $this->account = $account;
  }

  /**
   * Lazy builder callback for rendering toolbar links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderToolbarLinks() {
    $links = [
      'account' => [
        'title' => $this->t('View profile'),
        'url' => Url::fromRoute('user.page'),
        'attributes' => [
          'title' => $this->t('User account'),
        ],
      ],
      'account_edit' => [
        'title' => $this->t('Edit profile'),
        'url' => Url::fromRoute('entity.user.edit_form', ['user' => $this->account->id()]),
        'attributes' => [
          'title' => $this->t('Edit user account'),
        ],
      ],
      'logout' => [
        'title' => $this->t('Log out'),
        'url' => Url::fromRoute('user.logout'),
      ],
    ];
    $build = [
      '#theme' => 'links__toolbar_user',
      '#links' => $links,
      '#attributes' => [
        'class' => ['toolbar-menu'],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    return $build;
  }

  /**
   * Lazy builder callback for rendering the username.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderDisplayName() {
    return [
      '#plain_text' => $this->account->getDisplayName(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderToolbarLinks', 'renderDisplayName'];
  }

}
