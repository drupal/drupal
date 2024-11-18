<?php

namespace Drupal\views_ui\Hook;

use Drupal\views\Entity\View;
use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_ui.
 */
class ViewsUiHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.views_ui':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Views UI module provides an interface for managing views for the <a href=":views">Views module</a>. For more information, see the <a href=":handbook">online documentation for the Views UI module</a>.', [
          ':views' => Url::fromRoute('help.page', [
            'name' => 'views',
          ])->toString(),
          ':handbook' => 'https://www.drupal.org/documentation/modules/views_ui',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Creating and managing views') . '</dt>';
        $output .= '<dd>' . t('Views can be created from the <a href=":list">Views list page</a> by using the "Add view" action. Existing views can be managed from the <a href=":list">Views list page</a> by locating the view in the "Enabled" or "Disabled" list and selecting the desired operation action, for example "Edit".', [
          ':list' => Url::fromRoute('entity.view.collection', [
            'name' => 'views_ui',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Enabling and disabling views') . '<dt>';
        $output .= '<dd>' . t('Views can be enabled or disabled from the <a href=":list">Views list page</a>. To enable a view, find the view within the "Disabled" list and select the "Enable" operation. To disable a view find the view within the "Enabled" list and select the "Disable" operation.', [
          ':list' => Url::fromRoute('entity.view.collection', [
            'name' => 'views_ui',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Exporting and importing views') . '</dt>';
        $output .= '<dd>' . t('Views can be exported and imported as configuration files by using the <a href=":config">Configuration Manager module</a>.', [
          ':config' => \Drupal::moduleHandler()->moduleExists('config') ? Url::fromRoute('help.page', [
            'name' => 'config',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['view']->setFormClass('edit', 'Drupal\views_ui\ViewEditForm')->setFormClass('add', 'Drupal\views_ui\ViewAddForm')->setFormClass('preview', 'Drupal\views_ui\ViewPreviewForm')->setFormClass('duplicate', 'Drupal\views_ui\ViewDuplicateForm')->setFormClass('delete', 'Drupal\Core\Entity\EntityDeleteForm')->setFormClass('break_lock', 'Drupal\views_ui\Form\BreakLockForm')->setListBuilderClass('Drupal\views_ui\ViewListBuilder')->setLinkTemplate('edit-form', '/admin/structure/views/view/{view}')->setLinkTemplate('edit-display-form', '/admin/structure/views/view/{view}/edit/{display_id}')->setLinkTemplate('preview-form', '/admin/structure/views/view/{view}/preview/{display_id}')->setLinkTemplate('duplicate-form', '/admin/structure/views/view/{view}/duplicate')->setLinkTemplate('delete-form', '/admin/structure/views/view/{view}/delete')->setLinkTemplate('enable', '/admin/structure/views/view/{view}/enable')->setLinkTemplate('disable', '/admin/structure/views/view/{view}/disable')->setLinkTemplate('break-lock-form', '/admin/structure/views/view/{view}/break-lock')->setLinkTemplate('collection', '/admin/structure/views');
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
          // Edit a view
      'views_ui_display_tab_setting' => [
        'variables' => [
          'description' => '',
          'link' => '',
          'settings_links' => [],
          'overridden' => FALSE,
          'defaulted' => FALSE,
          'description_separator' => TRUE,
          'class' => [],
        ],
        'file' => 'views_ui.theme.inc',
      ],
      'views_ui_display_tab_bucket' => [
        'render element' => 'element',
        'file' => 'views_ui.theme.inc',
      ],
      'views_ui_rearrange_filter_form' => [
        'render element' => 'form',
        'file' => 'views_ui.theme.inc',
      ],
      'views_ui_expose_filter_form' => [
        'render element' => 'form',
        'file' => 'views_ui.theme.inc',
      ],
          // Legacy theme hook for displaying views info.
      'views_ui_view_info' => [
        'variables' => [
          'view' => NULL,
          'displays' => NULL,
        ],
        'file' => 'views_ui.theme.inc',
      ],
          // List views.
      'views_ui_views_listing_table' => [
        'variables' => [
          'headers' => NULL,
          'rows' => NULL,
          'attributes' => [],
        ],
        'file' => 'views_ui.theme.inc',
      ],
      'views_ui_view_displays_list' => [
        'variables' => [
          'displays' => [],
        ],
      ],
          // Group of filters.
      'views_ui_build_group_filter_form' => [
        'render element' => 'form',
        'file' => 'views_ui.theme.inc',
      ],
          // On behalf of a plugin
      'views_ui_style_plugin_table' => [
        'render element' => 'form',
        'file' => 'views_ui.theme.inc',
      ],
          // When previewing a view.
      'views_ui_view_preview_section' => [
        'variables' => [
          'view' => NULL,
          'section' => NULL,
          'content' => NULL,
          'links' => '',
        ],
        'file' => 'views_ui.theme.inc',
      ],
          // Generic container wrapper, to use instead of theme_container when an id
          // is not desired.
      'views_ui_container' => [
        'variables' => [
          'children' => NULL,
          'attributes' => [],
        ],
        'file' => 'views_ui.theme.inc',
      ],
    ];
  }

  /**
   * Implements hook_views_plugins_display_alter().
   */
  #[Hook('views_plugins_display_alter')]
  public function viewsPluginsDisplayAlter(&$plugins): void {
    // Attach contextual links to each display plugin. The links will point to
    // paths underneath "admin/structure/views/view/{$view->id()}" (i.e., paths
    // for editing and performing other contextual actions on the view).
    foreach ($plugins as &$display) {
      $display['contextual links']['entity.view.edit_form'] = [
        'route_name' => 'entity.view.edit_form',
        'route_parameters_names' => [
          'view' => 'id',
        ],
      ];
    }
  }

  /**
   * Implements hook_contextual_links_view_alter().
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items): void {
    // Remove contextual links from being rendered, when so desired, such as
    // within a View preview.
    if (views_ui_contextual_links_suppress()) {
      $element['#links'] = [];
    }
    elseif (!empty($element['#links']['entityviewedit-form'])) {
      $display_id = $items['entity.view.edit_form']['metadata']['display_id'];
      $route_parameters = $element['#links']['entityviewedit-form']['url']->getRouteParameters() + ['display_id' => $display_id];
      $element['#links']['entityviewedit-form']['url'] = Url::fromRoute('entity.view.edit_display_form', $route_parameters);
    }
  }

  /**
   * Implements hook_views_analyze().
   *
   * This is the basic views analysis that checks for very minimal problems.
   * There are other analysis tools in core specific sections, such as
   * node.views.inc as well.
   */
  #[Hook('views_analyze')]
  public function viewsAnalyze(ViewExecutable $view) {
    $ret = [];
    // Check for something other than the default display:
    if (count($view->displayHandlers) < 2) {
      $ret[] = Analyzer::formatMessage(t('This view has only a default display and therefore will not be placed anywhere on your site; perhaps you want to add a page or a block display.'), 'warning');
    }
    // If a display has a path, check that it does not match an existing path
    // alias. This results in the path alias not working.
    foreach ($view->displayHandlers as $display) {
      if (empty($display)) {
        continue;
      }
      if ($display->hasPath() && ($path = $display->getOption('path'))) {
        $normal_path = \Drupal::service('path_alias.manager')->getPathByAlias($path);
        if ($path != $normal_path) {
          $ret[] = Analyzer::formatMessage(t('You have configured display %display with a path which is an path alias as well. This might lead to unwanted effects so better use an internal path.', ['%display' => $display->display['display_title']]), 'warning');
        }
      }
    }
    return $ret;
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) : array {
    $operations = [];
    if ($entity instanceof BlockInterface) {
      $plugin = $entity->getPlugin();
      if ($plugin->getBaseId() === 'views_block') {
        $view_id_parts = explode('-', $plugin->getDerivativeId());
        $view_id = $view_id_parts[0] ?? '';
        $display_id = $view_id_parts[1] ?? '';
        $view = View::load($view_id);
        if ($view && $view->access('edit')) {
          $operations['view-edit'] = [
            'title' => t('Edit view'),
            'url' => Url::fromRoute('entity.view.edit_display_form', [
              'view' => $view_id,
              'display_id' => $display_id,
            ]),
            'weight' => 50,
          ];
        }
      }
    }
    return $operations;
  }

}
