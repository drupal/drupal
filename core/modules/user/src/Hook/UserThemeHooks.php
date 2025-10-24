<?php

namespace Drupal\user\Hook;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for user.
 */
class UserThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected AccountInterface $currentUser,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'user' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessUser',
      ],
      'username' => [
        'variables' => [
          'account' => NULL,
          'attributes' => [],
          'link_options' => [],
        ],
        'initial preprocess' => static::class . ':preprocessUsername',
      ],
    ];
  }

  /**
   * Prepares variables for user templates.
   *
   * Default template: user.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the user information and any
   *     fields attached to the user. Properties used:
   *     - #user: A \Drupal\user\Entity\User object. The user account of the
   *       profile being viewed.
   *   - attributes: HTML attributes for the containing element.
   */
  public function preprocessUser(array &$variables): void {
    $variables['user'] = $variables['elements']['#user'];
    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

  /**
   * Prepares variables for username templates.
   *
   * Default template: username.html.twig.
   *
   * Modules that make any changes to variables like 'name' or 'extra' must
   * ensure that the final string is safe.
   *
   * @param array $variables
   *   An associative array containing:
   *   - account: The user account (\Drupal\Core\Session\AccountInterface).
   */
  public function preprocessUsername(array &$variables): void {
    $account = $variables['account'] ?: new AnonymousUserSession();

    $variables['extra'] = '';
    $variables['uid'] = $account->id();
    if (empty($variables['uid'])) {
      if ($this->themeSettingsProvider->getSetting('features.comment_user_verification')) {
        $variables['extra'] = ' (' . $this->t('not verified') . ')';
      }
    }

    // Set the name to a formatted name that is safe for printing and
    // that won't break tables by being too long. Keep an un-shortened,
    // unsanitized version, in case other preprocess functions want to implement
    // their own shortening logic or add markup. If they do so, they must ensure
    // that $variables['name'] is safe for printing.
    $name = $account->getDisplayName();
    $variables['name_raw'] = $account->getAccountName();
    if (mb_strlen($name) > 20) {
      $name = Unicode::truncate($name, 15, FALSE, TRUE);
      $variables['truncated'] = TRUE;
    }
    else {
      $variables['truncated'] = FALSE;
    }
    $variables['name'] = $name;
    if ($account instanceof AccessibleInterface) {
      $variables['profile_access'] = $account->access('view');
    }
    else {
      $variables['profile_access'] = $this->currentUser->hasPermission('access user profiles');
    }

    $external = FALSE;
    // Populate link path and attributes if appropriate.
    if ($variables['uid'] && $variables['profile_access']) {
      // We are linking to a local user.
      $variables['attributes']['title'] = $this->t('View user profile.');
      $variables['link_path'] = 'user/' . $variables['uid'];
    }
    elseif (!empty($account->homepage)) {
      // Like the 'class' attribute, the 'rel' attribute can hold a
      // space-separated set of values, so initialize it as an array to make it
      // easier for other preprocess functions to append to it.
      $variables['attributes']['rel'] = 'nofollow';
      $variables['link_path'] = $account->homepage;
      $variables['homepage'] = $account->homepage;
      $external = TRUE;
    }
    // We have a link path, so we should generate a URL.
    if (isset($variables['link_path'])) {
      if ($external) {
        $variables['attributes']['href'] = Url::fromUri($variables['link_path'], $variables['link_options'])
          ->toString();
      }
      else {
        $variables['attributes']['href'] = Url::fromRoute('entity.user.canonical', [
          'user' => $variables['uid'],
        ])->toString();
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'user') {
      switch ($variables['elements']['#plugin_id']) {
        case 'user_login_block':
          $variables['attributes']['role'] = 'form';
          break;
      }
    }
  }

}
