<?php

namespace Drupal\views\Hook;

use Drupal\views\ViewsConfigUpdater;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Plugin\Derivative\ViewsLocalTask;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views.
 */
class ViewsHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.views':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Views module provides a back end to fetch information from content, user accounts, taxonomy terms, and other entities from the database and present it to the user as a grid, HTML list, table, unformatted list, etc. The resulting displays are known generally as <em>views</em>.') . '</p>';
        $output .= '<p>' . t('For more information, see the <a href=":views">online documentation for the Views module</a>.', [':views' => 'https://www.drupal.org/documentation/modules/views']) . '</p>';
        $output .= '<p>' . t('In order to create and modify your own views using the administration and configuration user interface, you will need to install either the Views UI module in core or a contributed module that provides a user interface for Views. See the <a href=":views-ui">Views UI module help page</a> for more information.', [
          ':views-ui' => \Drupal::moduleHandler()->moduleExists('views_ui') ? Url::fromRoute('help.page', [
            'name' => 'views_ui',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Adding functionality to administrative pages') . '</dt>';
        $output .= '<dd>' . t('The Views module adds functionality to some core administration pages. For example, <em>admin/content</em> uses Views to filter and sort content. With Views uninstalled, <em>admin/content</em> is more limited.') . '</dd>';
        $output .= '<dt>' . t('Expanding Views functionality') . '</dt>';
        $output .= '<dd>' . t('Contributed projects that support the Views module can be found in the <a href=":node">online documentation for Views-related contributed modules</a>.', [':node' => 'https://www.drupal.org/documentation/modules/views/add-ons']) . '</dd>';
        $output .= '<dt>' . t('Improving table accessibility') . '</dt>';
        $output .= '<dd>' . t('Views tables include semantic markup to improve accessibility. Data cells are automatically associated with header cells through id and header attributes. To improve the accessibility of your tables you can add descriptive elements within the Views table settings. The <em>caption</em> element can introduce context for a table, making it easier to understand. The <em>summary</em> element can provide an overview of how the data has been organized and how to navigate the table. Both the caption and summary are visible by default and also implemented according to HTML5 guidelines.') . '</dd>';
        $output .= '<dt>' . t('Working with multilingual views') . '</dt>';
        $output .= '<dd>' . t('If your site has multiple languages and translated entities, each result row in a view will contain one translation of each involved entity (a view can involve multiple entities if it uses relationships). You can use a filter to restrict your view to one language: without filtering, if an entity has three translations it will add three rows to the results; if you filter by language, at most one result will appear (it could be zero if that particular entity does not have a translation matching your language filter choice). If a view uses relationships, each entity in the relationship needs to be filtered separately. You can filter a view to a fixed language choice, such as English or Spanish, or to the language selected by the page the view is displayed on (the language that is selected for the page by the language detection settings either for Content or User interface).') . '</dd>';
        $output .= '<dd>' . t('Because each result row contains a specific translation of each entity, field-level filters are also relative to these entity translations. For example, if your view has a filter that specifies that the entity title should contain a particular English word, you will presumably filter out all rows containing Chinese translations, since they will not contain the English word. If your view also has a second filter specifying that the title should contain a particular Chinese word, and if you are using "And" logic for filtering, you will presumably end up with no results in the view, because there are probably not any entity translations containing both the English and Chinese words in the title.') . '</dd>';
        $output .= '<dd>' . t('Independent of filtering, you can choose the display language (the language used to display the entities and their fields) via a setting on the display. Your language choices are the same as the filter language choices, with an additional choice of "Content language of view row" and "Original language of content in view row", which means to display each entity in the result row using the language that entity has or in which it was originally created. In theory, this would give you the flexibility to filter to French translations, for instance, and then display the results in Spanish. The more usual choices would be to use the same language choices for the display language and each entity filter in the view, or to use the Row language setting for the display.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender($view) {
    // If using AJAX, send identifying data about this view.
    if ($view->ajaxEnabled() && empty($view->is_attachment) && empty($view->live_preview)) {
      $view->element['#attached']['drupalSettings']['views'] = [
        'ajax_path' => Url::fromRoute('views.ajax')->toString(),
        'ajaxViews' => [
          'views_dom_id:' . $view->dom_id => [
            'view_name' => $view->storage->id(),
            'view_display_id' => $view->current_display,
            'view_args' => Html::escape(implode('/', $view->args)),
            'view_path' => Html::escape(\Drupal::service('path.current')->getPath()),
            'view_base_path' => $view->getPath(),
            'view_dom_id' => $view->dom_id,
                    // To fit multiple views on a page, the programmer may have
                    // overridden the display's pager_element.
            'pager_element' => isset($view->pager) ? $view->pager->getPagerId() : 0,
          ],
        ],
      ];
      $view->element['#attached']['library'][] = 'views/views.ajax';
    }
    return $view;
  }

  /**
   * Implements hook_theme().
   *
   * Register views theming functions and those that are defined via views plugin
   * definitions.
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    \Drupal::moduleHandler()->loadInclude('views', 'inc', 'views.theme');
    // Some quasi clever array merging here.
    $base = ['file' => 'views.theme.inc'];
    // Our extra version of pager
    $hooks['views_mini_pager'] = $base + [
      'variables' => [
        'tags' => [],
        'quantity' => 9,
        'element' => 0,
        'pagination_heading_level' => 'h4',
        'parameters' => [],
      ],
    ];
    $variables = [
          // For displays, we pass in a dummy array as the first parameter, since
          // $view is an object but the core contextual_preprocess() function only
          // attaches contextual links when the primary theme argument is an array.
      'display' => [
        'view_array' => [],
        'view' => NULL,
        'rows' => [],
        'header' => [],
        'footer' => [],
        'empty' => [],
        'exposed' => [],
        'more' => [],
        'feed_icons' => [],
        'pager' => [],
        'title' => '',
        'attachment_before' => [],
        'attachment_after' => [],
      ],
      'style' => [
        'view' => NULL,
        'options' => NULL,
        'rows' => NULL,
        'title' => NULL,
      ],
      'row' => [
        'view' => NULL,
        'options' => NULL,
        'row' => NULL,
        'field_alias' => NULL,
      ],
      'exposed_form' => [
        'view' => NULL,
        'options' => NULL,
      ],
      'pager' => [
        'view' => NULL,
        'options' => NULL,
        'tags' => [],
        'quantity' => 9,
        'element' => 0,
        'pagination_heading_level' => 'h4',
        'parameters' => [],
      ],
    ];
    // Default view themes
    $hooks['views_view_field'] = $base + ['variables' => ['view' => NULL, 'field' => NULL, 'row' => NULL]];
    $hooks['views_view_grouping'] = $base + [
      'variables' => [
        'view' => NULL,
        'grouping' => NULL,
        'grouping_level' => NULL,
        'rows' => NULL,
        'title' => NULL,
      ],
    ];
    // Only display, pager, row, and style plugins can provide theme hooks.
    $plugin_types = ['display', 'pager', 'row', 'style', 'exposed_form'];
    $plugins = [];
    foreach ($plugin_types as $plugin_type) {
      $plugins[$plugin_type] = Views::pluginManager($plugin_type)->getDefinitions();
    }
    $module_handler = \Drupal::moduleHandler();
    // Register theme functions for all style plugins. It provides a basic auto
    // implementation of theme functions or template files by using the plugin
    // definitions (theme, theme_file, module, register_theme). Template files are
    // assumed to be located in the templates folder.
    foreach ($plugins as $type => $info) {
      foreach ($info as $def) {
        // Not all plugins have theme functions, and they can also explicitly
        // prevent a theme function from being registered automatically.
        if (!isset($def['theme']) || empty($def['register_theme'])) {
          continue;
        }
        // For each theme registration, we have a base directory to check for the
        // templates folder. This will be relative to the root of the given module
        // folder, so we always need a module definition.
        // @todo Watchdog or exception?
        if (!isset($def['provider']) || !$module_handler->moduleExists($def['provider'])) {
          continue;
        }
        $hooks[$def['theme']] = ['variables' => $variables[$type]];
        // We always use the module directory as base dir.
        $module_dir = \Drupal::service('extension.list.module')->getPath($def['provider']);
        $hooks[$def['theme']]['path'] = $module_dir;
        // For the views module we ensure views.theme.inc is included.
        if ($def['provider'] == 'views') {
          if (!isset($hooks[$def['theme']]['includes'])) {
            $hooks[$def['theme']]['includes'] = [];
          }
          if (!in_array('views.theme.inc', $hooks[$def['theme']]['includes'])) {
            $hooks[$def['theme']]['includes'][] = $module_dir . '/views.theme.inc';
          }
        }
        elseif (!empty($def['theme_file'])) {
          $hooks[$def['theme']]['file'] = $def['theme_file'];
        }
        // Whenever we have a theme file, we include it directly so we can
        // auto-detect the theme function.
        if (isset($def['theme_file'])) {
          $include = \Drupal::root() . '/' . $module_dir . '/' . $def['theme_file'];
          if (is_file($include)) {
            require_once $include;
          }
        }
        // By default any templates for a module are located in the /templates
        // directory of the module's folder. If a module wants to define its own
        // location it has to set register_theme of the plugin to FALSE and
        // implement hook_theme() by itself.
        $hooks[$def['theme']]['path'] .= '/templates';
        $hooks[$def['theme']]['template'] = Html::cleanCssIdentifier($def['theme']);
      }
    }
    $hooks['views_form_views_form'] = $base + ['render element' => 'form'];
    $hooks['views_exposed_form'] = $base + ['render element' => 'form'];
    return $hooks;
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_node_alter')]
  public function themeSuggestionsNodeAlter(array &$suggestions, array $variables): void {
    $node = $variables['elements']['#node'];
    if (!empty($node->view) && $node->view->storage->id()) {
      $suggestions[] = 'node__view__' . $node->view->storage->id();
      if (!empty($node->view->current_display)) {
        $suggestions[] = 'node__view__' . $node->view->storage->id() . '__' . $node->view->current_display;
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_comment_alter')]
  public function themeSuggestionsCommentAlter(array &$suggestions, array $variables): void {
    $comment = $variables['elements']['#comment'];
    if (!empty($comment->view) && $comment->view->storage->id()) {
      $suggestions[] = 'comment__view__' . $comment->view->storage->id();
      if (!empty($comment->view->current_display)) {
        $suggestions[] = 'comment__view__' . $comment->view->storage->id() . '__' . $comment->view->current_display;
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_container_alter')]
  public function themeSuggestionsContainerAlter(array &$suggestions, array $variables): void {
    if (!empty($variables['element']['#type']) && $variables['element']['#type'] == 'more_link' && !empty($variables['element']['#view']) && $variables['element']['#view'] instanceof ViewExecutable) {
      $suggestions = array_merge(
            $suggestions,
            // Theme suggestions use the reverse order compared to #theme hooks.
            array_reverse($variables['element']['#view']->buildThemeFunctions('container__more_link'))
        );
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for 'field_config'.
   */
  #[Hook('field_config_insert')]
  public function fieldConfigInsert(EntityInterface $field) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_config'.
   */
  #[Hook('field_config_update')]
  public function fieldConfigUpdate(EntityInterface $entity) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_config'.
   */
  #[Hook('field_config_delete')]
  public function fieldConfigDelete(EntityInterface $entity) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('base_field_override_insert')]
  public function baseFieldOverrideInsert(EntityInterface $entity) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('base_field_override_update')]
  public function baseFieldOverrideUpdate(EntityInterface $entity) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('base_field_override_delete')]
  public function baseFieldOverrideDelete(EntityInterface $entity) {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the exposed form.
   *
   * Since the exposed form is a GET form, we don't want it to send a wide
   * variety of information.
   */
  #[Hook('form_views_exposed_form_alter')]
  public function formViewsExposedFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * This is the hook_query_alter() for queries tagged by Views and is used to
   * add in substitutions from hook_views_query_substitutions().
   */
  #[Hook('query_views_alter')]
  public function queryViewsAlter(AlterableInterface $query): void {
    $substitutions = $query->getMetaData('views_substitutions');
    $tables =& $query->getTables();
    $where =& $query->conditions();
    // Replaces substitutions in tables.
    foreach ($tables as $table_name => $table_metadata) {
      foreach ($table_metadata['arguments'] as $replacement_key => $value) {
        if (!is_array($value)) {
          if (isset($substitutions[$value])) {
            $tables[$table_name]['arguments'][$replacement_key] = $substitutions[$value];
          }
        }
        else {
          foreach ($value as $sub_key => $sub_value) {
            if (isset($substitutions[$sub_value])) {
              $tables[$table_name]['arguments'][$replacement_key][$sub_key] = $substitutions[$sub_value];
            }
          }
        }
      }
    }
    // Replaces substitutions in filter criteria.
    _views_query_tag_alter_condition($query, $where, $substitutions);
  }

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks): void {
    $container = \Drupal::getContainer();
    $local_task = ViewsLocalTask::create($container, 'views_view');
    $local_task->alterLocalTasks($local_tasks);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('view_presave')]
  public function viewPresave(ViewEntityInterface $view) {
    /** @var \Drupal\views\ViewsConfigUpdater $config_updater */
    $config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
    $config_updater->updateAll($view);
  }

}
