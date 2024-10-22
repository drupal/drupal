<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * User navigation block lazy builder.
 *
 * @internal The navigation module is experimental.
 */
final class UserLazyBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * Constructs an UserLazyBuilder object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(
    protected readonly AccountProxyInterface $account,
  ) {}

  /**
   * Lazy builder callback for rendering navigation links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderNavigationLinks() {
    return [
      '#theme' => 'menu_region__footer',
      '#items' => $this->userOperationLinks(),
      '#menu_name' => 'user',
      '#title' => $this->account->getDisplayName(),
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
    ];
  }

  /**
   * Returns the user operation links in navigation expected format.
   *
   * @param bool $include_edit
   *   (Optional) Whether to include the edit account link or not.
   *
   * @return array
   *   List of operation links for the current user.
   */
  public function userOperationLinks(bool $include_edit = TRUE): array {
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

    if (!$include_edit) {
      unset($links['account_edit']);
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderNavigationLinks'];
  }

}
