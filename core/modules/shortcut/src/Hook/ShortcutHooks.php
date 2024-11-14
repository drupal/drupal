<?php

namespace Drupal\shortcut\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for shortcut.
 */
class ShortcutHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.shortcut':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Shortcut module allows users to create sets of <em>shortcut</em> links to commonly-visited pages of the site. Shortcuts are contained within <em>sets</em>. Each user with <em>Select any shortcut set</em> permission can select a shortcut set created by anyone at the site. For more information, see the <a href=":shortcut">online documentation for the Shortcut module</a>.', [':shortcut' => 'https://www.drupal.org/docs/8/core/modules/shortcut']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl><dt>' . t('Administering shortcuts') . '</dt>';
        $output .= '<dd>' . t('Users with the <em>Administer shortcuts</em> permission can manage shortcut sets and edit the shortcuts within sets from the <a href=":shortcuts">Shortcuts administration page</a>.', [
          ':shortcuts' => Url::fromRoute('entity.shortcut_set.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Choosing shortcut sets') . '</dt>';
        $output .= '<dd>' . t('Users with permission to switch shortcut sets can choose a shortcut set to use from the Shortcuts tab of their user account page.') . '</dd>';
        $output .= '<dt>' . t('Adding and removing shortcuts') . '</dt>';
        $output .= '<dd>' . t('The Shortcut module creates an add/remove link for each page on your site; the link lets you add or remove the current page from the currently-enabled set of shortcuts (if your theme displays it and you have permission to edit your shortcut set). The core Claro administration theme displays this link next to the page title, as a gray or yellow star. If you click on the gray star, you will add that page to your preferred set of shortcuts. If the page is already part of your shortcut set, the link will be a yellow star, and will allow you to remove the current page from your shortcut set.') . '</dd>';
        $output .= '<dt>' . t('Displaying shortcuts') . '</dt>';
        $output .= '<dd>' . t('You can display your shortcuts by enabling the <em>Shortcuts</em> block on the <a href=":blocks">Blocks administration page</a>. Certain administrative modules also display your shortcuts; for example, the core <a href=":toolbar-help">Toolbar module</a> provides a corresponding menu link.', [
          ':blocks' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('block.admin_display')->toString() : '#',
          ':toolbar-help' => \Drupal::moduleHandler()->moduleExists('toolbar') ? Url::fromRoute('help.page', [
            'name' => 'toolbar',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.shortcut_set.collection':
      case 'shortcut.set_add':
      case 'entity.shortcut_set.edit_form':
        $user = \Drupal::currentUser();
        if ($user->hasPermission('access shortcuts') && $user->hasPermission('switch shortcut sets')) {
          $output = '<p>' . t('Define which shortcut set you are using on the <a href=":shortcut-link">Shortcuts tab</a> of your account page.', [
            ':shortcut-link' => Url::fromRoute('shortcut.set_switch', [
              'user' => $user->id(),
            ])->toString(),
          ]) . '</p>';
          return $output;
        }
    }
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar() {
    $user = \Drupal::currentUser();
    $items = [];
    $items['shortcuts'] = ['#cache' => ['contexts' => ['user.permissions']]];
    if ($user->hasPermission('access shortcuts')) {
      $shortcut_set = \Drupal::entityTypeManager()->getStorage('shortcut_set')->getDisplayedToUser($user);
      $items['shortcuts'] += [
        '#type' => 'toolbar_item',
        'tab' => [
          '#type' => 'link',
          '#title' => t('Shortcuts'),
          '#url' => $shortcut_set->toUrl('collection'),
          '#attributes' => [
            'title' => t('Shortcuts'),
            'class' => [
              'toolbar-icon',
              'toolbar-icon-shortcut',
            ],
          ],
        ],
        'tray' => [
          '#heading' => t('User-defined shortcuts'),
          'children' => [
            '#lazy_builder' => [
              'shortcut.lazy_builders:lazyLinks',
                        [],
            ],
            '#create_placeholder' => TRUE,
            '#cache' => [
              'keys' => [
                'shortcut_set_toolbar_links',
              ],
              'contexts' => [
                'user',
              ],
            ],
            '#lazy_builder_preview' => [
              '#markup' => '<a href="#" class="toolbar-tray-lazy-placeholder-link">&nbsp;</a>',
            ],
          ],
        ],
        '#weight' => -10,
        '#attached' => [
          'library' => [
            'shortcut/drupal.shortcut',
          ],
        ],
      ];
    }
    return $items;
  }

  /**
   * Implements hook_themes_installed().
   */
  #[Hook('themes_installed')]
  public function themesInstalled($theme_list) {
    // Theme settings are not configuration entities and cannot depend on modules
    // so to set a module-specific setting, we need to set it with logic.
    if (in_array('claro', $theme_list, TRUE)) {
      \Drupal::configFactory()->getEditable("claro.settings")->set('third_party_settings.shortcut.module_link', TRUE)->save(TRUE);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('user_delete')]
  public function userDelete(EntityInterface $entity) {
    // Clean up shortcut set mapping of removed user account.
    \Drupal::entityTypeManager()->getStorage('shortcut_set')->unassignUser($entity);
  }

}
