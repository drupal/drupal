<?php

namespace Drupal\menu_ui\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\system\MenuInterface;
use Drupal\system\Entity\Menu;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for menu_ui.
 */
class MenuUiHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.menu_ui':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Menu UI module provides an interface for managing menus. A menu is a hierarchical collection of links, which can be within or external to the site, generally used for navigation. For more information, see the <a href=":menu">online documentation for the Menu UI module</a>.', [
          ':menu' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/menu-ui-module',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing menus') . '</dt>';
        $output .= '<dd>' . t('Users with the <em>Administer menus and menu links</em> permission can add, edit, and delete custom menus on the <a href=":menu">Menus page</a>. Custom menus can be special site menus, menus of external links, or any combination of internal and external links. You may create an unlimited number of additional menus, each of which will automatically have an associated block (if you have the <a href=":block_help">Block module</a> installed). By selecting <em>Edit menu</em>, you can add, edit, or delete links for a given menu. The links listing page provides a drag-and-drop interface for controlling the order of links, and creating a hierarchy within the menu.', [
          ':block_help' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('help.page', [
            'name' => 'block',
          ])->toString() : '#',
          ':menu' => Url::fromRoute('entity.menu.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Displaying menus') . '</dt>';
        $output .= '<dd>' . t('If you have the Block module installed, then each menu that you create is rendered in a block that you enable and position on the <a href=":blocks">Block layout page</a>. In some <a href=":themes">themes</a>, the main menu and possibly the secondary menu will be output automatically; you may be able to disable this behavior on the <a href=":themes">theme\'s settings page</a>.', [
          ':blocks' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('block.admin_display')->toString() : '#',
          ':themes' => Url::fromRoute('system.themes_page')->toString(),
          ':theme_settings' => Url::fromRoute('system.theme_settings')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    if ($route_name == 'entity.menu.add_form' && \Drupal::moduleHandler()->moduleExists('block') && \Drupal::currentUser()->hasPermission('administer blocks')) {
      return '<p>' . t('You can enable the newly-created block for this menu on the <a href=":blocks">Block layout page</a>.', [':blocks' => Url::fromRoute('block.admin_display')->toString()]) . '</p>';
    }
    elseif ($route_name == 'entity.menu.collection' && \Drupal::moduleHandler()->moduleExists('block') && \Drupal::currentUser()->hasPermission('administer blocks')) {
      return '<p>' . t('Each menu has a corresponding block that is managed on the <a href=":blocks">Block layout page</a>.', [':blocks' => Url::fromRoute('block.admin_display')->toString()]) . '</p>';
    }
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['menu']->setFormClass('add', 'Drupal\menu_ui\MenuForm')->setFormClass('edit', 'Drupal\menu_ui\MenuForm')->setFormClass('delete', 'Drupal\menu_ui\Form\MenuDeleteForm')->setListBuilderClass('Drupal\menu_ui\MenuListBuilder')->setLinkTemplate('add-form', '/admin/structure/menu/add')->setLinkTemplate('delete-form', '/admin/structure/menu/manage/{menu}/delete')->setLinkTemplate('edit-form', '/admin/structure/menu/manage/{menu}')->setLinkTemplate('add-link-form', '/admin/structure/menu/manage/{menu}/add')->setLinkTemplate('collection', '/admin/structure/menu');
    if (isset($entity_types['node'])) {
      $entity_types['node']->addConstraint('MenuSettings', []);
    }
  }

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter() for 'system_menu_block'.
   */
  #[Hook('block_view_system_menu_block_alter')]
  public function blockViewSystemMenuBlockAlter(array &$build, BlockPluginInterface $block): void {
    if ($block->getBaseId() == 'system_menu_block') {
      $menu_name = $block->getDerivativeId();
      $build['#contextual_links']['menu'] = ['route_parameters' => ['menu' => $menu_name]];
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\node\NodeForm.
   *
   * Adds menu item fields to the node form.
   *
   * @see menu_ui_form_node_form_submit()
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state) : void {
    // Generate a list of possible parents (not including this link or descendants).
    // @todo This must be handled in a #process handler.
    $node = $form_state->getFormObject()->getEntity();
    $defaults = menu_ui_get_menu_link_defaults($node);
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node->type->entity;
    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
    $type_menus_ids = $node_type->getThirdPartySetting('menu_ui', 'available_menus', ['main']);
    if (empty($type_menus_ids)) {
      return;
    }
    /** @var \Drupal\system\MenuInterface[] $type_menus */
    $type_menus = Menu::loadMultiple($type_menus_ids);
    $available_menus = [];
    foreach ($type_menus as $menu) {
      $available_menus[$menu->id()] = $menu->label();
    }
    if ($defaults['id']) {
      $default = $defaults['menu_name'] . ':' . $defaults['parent'];
    }
    else {
      $default = $node_type->getThirdPartySetting('menu_ui', 'parent', 'main:');
    }
    $parent_element = $menu_parent_selector->parentSelectElement($default, $defaults['id'], $available_menus);
    // If no possible parent menu items were found, there is nothing to display.
    if (empty($parent_element)) {
      return;
    }
    $form['menu'] = [
      '#type' => 'details',
      '#title' => t('Menu settings'),
      '#access' => \Drupal::currentUser()->hasPermission('administer menu'),
      '#open' => (bool) $defaults['id'],
      '#group' => 'advanced',
      '#attached' => [
        'library' => [
          'menu_ui/drupal.menu_ui',
        ],
      ],
      '#tree' => TRUE,
      '#weight' => -2,
      '#attributes' => [
        'class' => [
          'menu-link-form',
        ],
      ],
    ];
    $form['menu']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Provide a menu link'),
      '#default_value' => (int) (bool) $defaults['id'],
    ];
    $form['menu']['link'] = [
      '#type' => 'container',
      '#parents' => [
        'menu',
      ],
      '#states' => [
        'invisible' => [
          'input[name="menu[enabled]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    // Populate the element with the link data.
    foreach (['id', 'entity_id'] as $key) {
      $form['menu']['link'][$key] = ['#type' => 'value', '#value' => $defaults[$key]];
    }
    $form['menu']['link']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Menu link title'),
      '#default_value' => $defaults['title'],
      '#maxlength' => $defaults['title_max_length'],
    ];
    $form['menu']['link']['description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $defaults['description'],
      '#description' => t('Shown when hovering over the menu link.'),
      '#maxlength' => $defaults['description_max_length'],
    ];
    $form['menu']['link']['menu_parent'] = $parent_element;
    $form['menu']['link']['menu_parent']['#title'] = t('Parent link');
    $form['menu']['link']['menu_parent']['#attributes']['class'][] = 'menu-parent-select';
    $form['menu']['link']['weight'] = [
      '#type' => 'number',
      '#title' => t('Weight'),
      '#default_value' => $defaults['weight'],
      '#description' => t('Menu links with lower weights are displayed before links with higher weights.'),
    ];
    foreach (array_keys($form['actions']) as $action) {
      if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = 'menu_ui_form_node_form_submit';
      }
    }
    $form['#entity_builders'][] = 'menu_ui_node_builder';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for \Drupal\node\NodeTypeForm.
   *
   * Adds menu options to the node type form.
   *
   * @see NodeTypeForm::form()
   * @see menu_ui_form_node_type_form_builder()
   */
  #[Hook('form_node_type_form_alter')]
  public function formNodeTypeFormAlter(&$form, FormStateInterface $form_state) : void {
    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
    $menu_options = array_map(function (MenuInterface $menu) {
        return $menu->label();
    }, Menu::loadMultiple());
    asort($menu_options);
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = $form_state->getFormObject()->getEntity();
    $form['menu'] = [
      '#type' => 'details',
      '#title' => t('Menu settings'),
      '#attached' => [
        'library' => [
          'menu_ui/drupal.menu_ui.admin',
        ],
      ],
      '#group' => 'additional_settings',
    ];
    $form['menu']['menu_options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Available menus'),
      '#default_value' => $type->getThirdPartySetting('menu_ui', 'available_menus', [
        'main',
      ]),
      '#options' => $menu_options,
      '#description' => t('Content of this type can be placed in the selected menus.'),
    ];
    // @todo See if we can avoid pre-loading all options by changing the form or
    //   using a #process callback. https://www.drupal.org/node/2310319
    //   To avoid an 'illegal option' error after saving the form we have to load
    //   all available menu parents. Otherwise, it is not possible to dynamically
    //   add options to the list using ajax.
    $options_cacheability = new CacheableMetadata();
    $options = $menu_parent_selector->getParentSelectOptions('', NULL, $options_cacheability);
    $form['menu']['menu_parent'] = [
      '#type' => 'select',
      '#title' => t('Default parent link'),
      '#default_value' => $type->getThirdPartySetting('menu_ui', 'parent', 'main:'),
      '#options' => $options,
      '#description' => t('Choose the menu link to be the default parent for a new link in the content authoring form.'),
      '#attributes' => [
        'class' => [
          'menu-title-select',
        ],
      ],
    ];
    $options_cacheability->applyTo($form['menu']['menu_parent']);
    $form['#validate'][] = 'menu_ui_form_node_type_form_validate';
    $form['#entity_builders'][] = 'menu_ui_form_node_type_form_builder';
  }

  /**
   * Implements hook_system_breadcrumb_alter().
   */
  #[Hook('system_breadcrumb_alter')]
  public function systemBreadcrumbAlter(Breadcrumb $breadcrumb, RouteMatchInterface $route_match, array $context): void {
    // Custom breadcrumb behavior for editing menu links, we append a link to
    // the menu in which the link is found.
    if ($route_match->getRouteName() == 'menu_ui.link_edit' && ($menu_link = $route_match->getParameter('menu_link_plugin'))) {
      if ($menu_link instanceof MenuLinkInterface) {
        // Add a link to the menu admin screen.
        $menu = Menu::load($menu_link->getMenuName());
        $breadcrumb->addLink(Link::createFromRoute($menu->label(), 'entity.menu.edit_form', ['menu' => $menu->id()]));
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return ['menu_link_form' => ['render element' => 'form']];
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) : array {
    $operations = [];
    if ($entity instanceof BlockInterface) {
      $plugin = $entity->getPlugin();
      if ($plugin->getBaseId() === 'system_menu_block') {
        $menu = Menu::load($plugin->getDerivativeId());
        if ($menu && $menu->access('edit')) {
          $operations['menu-edit'] = ['title' => t('Edit menu'), 'url' => $menu->toUrl('edit-form'), 'weight' => 50];
        }
      }
    }
    return $operations;
  }

}
