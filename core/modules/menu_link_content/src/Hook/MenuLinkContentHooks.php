<?php

namespace Drupal\menu_link_content\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\path_alias\PathAliasInterface;
use Drupal\system\MenuInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for menu_link_content.
 */
class MenuLinkContentHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.menu_link_content':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Custom Menu Links module allows users to create menu links. These links can be translated if multiple languages are used for the site.');
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          $output .= ' ' . $this->t('It is required by the Menu UI module, which provides an interface for managing menus and menu links. For more information, see the <a href=":menu-help">Menu UI module help page</a> and the <a href=":drupal-org-help">online documentation for the Custom Menu Links module</a>.', [
            ':menu-help' => Url::fromRoute('help.page', [
              'name' => 'menu_ui',
            ])->toString(),
            ':drupal-org-help' => 'https://www.drupal.org/documentation/modules/menu_link',
          ]);
        }
        else {
          $output .= ' ' . $this->t('For more information, see the <a href=":drupal-org-help">online documentation for the Custom Menu Links module</a>. If you install the Menu UI module, it provides an interface for managing menus and menu links.', [':drupal-org-help' => 'https://www.drupal.org/documentation/modules/menu_link']);
        }
        $output .= '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // @todo Moderation is disabled for custom menu links until when we have an UI
    //   for them.
    //   @see https://www.drupal.org/project/drupal/issues/2350939
    $entity_types['menu_link_content']->setHandlerClass('moderation', '');
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('menu_delete')]
  public function menuDelete(MenuInterface $menu): void {
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $menu_links = $storage->loadByProperties(['menu_name' => $menu->id()]);
    $storage->delete($menu_links);
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for 'path_alias'.
   */
  #[Hook('path_alias_insert')]
  public function pathAliasInsert(PathAliasInterface $path_alias): void {
    _menu_link_content_update_path_alias($path_alias->getAlias());
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'path_alias'.
   */
  #[Hook('path_alias_update')]
  public function pathAliasUpdate(PathAliasInterface $path_alias): void {
    if ($path_alias->getAlias() != $path_alias->getOriginal()->getAlias()) {
      _menu_link_content_update_path_alias($path_alias->getAlias());
      _menu_link_content_update_path_alias($path_alias->getOriginal()->getAlias());
    }
    elseif ($path_alias->getPath() != $path_alias->getOriginal()->getPath()) {
      _menu_link_content_update_path_alias($path_alias->getAlias());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'path_alias'.
   */
  #[Hook('path_alias_delete')]
  public function pathAliasDelete(PathAliasInterface $path_alias): void {
    _menu_link_content_update_path_alias($path_alias->getAlias());
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $entity_type_id = $entity->getEntityTypeId();
    foreach ($entity->uriRelationships() as $rel) {
      $url = $entity->toUrl($rel);
      // Entities can provide uri relationships that are not routed, in this
      // case getRouteParameters() will throw an exception.
      if (!$url->isRouted()) {
        continue;
      }
      $route_parameters = $url->getRouteParameters();
      if (!isset($route_parameters[$entity_type_id])) {
        // Do not delete links which do not relate to this exact entity. For
        // example, "collection", "add-form", etc.
        continue;
      }
      // Delete all MenuLinkContent links that point to this entity route.
      $result = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $route_parameters);
      if ($result) {
        foreach ($result as $id => $instance) {
          if ($instance->isDeletable() && str_starts_with($id, 'menu_link_content:')) {
            $instance->deleteLink();
          }
        }
      }
    }
  }

}
