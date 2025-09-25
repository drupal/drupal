<?php

namespace Drupal\views\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.views':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Views module provides a back end to fetch information from content, user accounts, taxonomy terms, and other entities from the database and present it to the user as a grid, HTML list, table, unformatted list, etc. The resulting displays are known generally as <em>views</em>.') . '</p>';
        $output .= '<p>' . $this->t('For more information, see the <a href=":views">online documentation for the Views module</a>.', [':views' => 'https://www.drupal.org/documentation/modules/views']) . '</p>';
        $output .= '<p>' . $this->t('In order to create and modify your own views using the administration and configuration user interface, you will need to install either the Views UI module in core or a contributed module that provides a user interface for Views. See the <a href=":views-ui">Views UI module help page</a> for more information.', [
          ':views-ui' => \Drupal::moduleHandler()->moduleExists('views_ui') ? Url::fromRoute('help.page', [
            'name' => 'views_ui',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Adding functionality to administrative pages') . '</dt>';
        $output .= '<dd>' . $this->t('The Views module adds functionality to some core administration pages. For example, <em>admin/content</em> uses Views to filter and sort content. With Views uninstalled, <em>admin/content</em> is more limited.') . '</dd>';
        $output .= '<dt>' . $this->t('Expanding Views functionality') . '</dt>';
        $output .= '<dd>' . $this->t('Contributed projects that support the Views module can be found in the <a href=":node">online documentation for Views-related contributed modules</a>.', [':node' => 'https://www.drupal.org/documentation/modules/views/add-ons']) . '</dd>';
        $output .= '<dt>' . $this->t('Improving table accessibility') . '</dt>';
        $output .= '<dd>' . $this->t('Views tables include semantic markup to improve accessibility. Data cells are automatically associated with header cells through id and header attributes. To improve the accessibility of your tables you can add descriptive elements within the Views table settings. The <em>caption</em> element can introduce context for a table, making it easier to understand. The <em>summary</em> element can provide an overview of how the data has been organized and how to navigate the table. Both the caption and summary are visible by default and also implemented according to HTML5 guidelines.') . '</dd>';
        $output .= '<dt>' . $this->t('Working with multilingual views') . '</dt>';
        $output .= '<dd>' . $this->t('If your site has multiple languages and translated entities, each result row in a view will contain one translation of each involved entity (a view can involve multiple entities if it uses relationships). You can use a filter to restrict your view to one language: without filtering, if an entity has three translations it will add three rows to the results; if you filter by language, at most one result will appear (it could be zero if that particular entity does not have a translation matching your language filter choice). If a view uses relationships, each entity in the relationship needs to be filtered separately. You can filter a view to a fixed language choice, such as English or Spanish, or to the language selected by the page the view is displayed on (the language that is selected for the page by the language detection settings either for Content or User interface).') . '</dd>';
        $output .= '<dd>' . $this->t('Because each result row contains a specific translation of each entity, field-level filters are also relative to these entity translations. For example, if your view has a filter that specifies that the entity title should contain a particular English word, you will presumably filter out all rows containing Chinese translations, since they will not contain the English word. If your view also has a second filter specifying that the title should contain a particular Chinese word, and if you are using "And" logic for filtering, you will presumably end up with no results in the view, because there are probably not any entity translations containing both the English and Chinese words in the title.') . '</dd>';
        $output .= '<dd>' . $this->t('Independent of filtering, you can choose the display language (the language used to display the entities and their fields) via a setting on the display. Your language choices are the same as the filter language choices, with an additional choice of "Content language of view row" and "Original language of content in view row", which means to display each entity in the result row using the language that entity has or in which it was originally created. In theory, this would give you the flexibility to filter to French translations, for instance, and then display the results in Spanish. The more usual choices would be to use the same language choices for the display language and each entity filter in the view, or to use the Row language setting for the display.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender($view): void {
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
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_node_alter')]
  public function themeSuggestionsNodeAlter(array &$suggestions, array $variables): void {
    $node = $variables['elements']['#node'];
    if (!empty($node->view) && $node->view->storage->id()) {
      $suggestions[] = 'node__view__' . $node->view->storage->id();
      $suggestions['__DEPRECATED']['node__view__' . $node->view->storage->id()] = 'Theme suggestion node__view__' . $node->view->storage->id() . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3541462';
      if (!empty($node->view->current_display)) {
        $suggestions[] = 'node__view__' . $node->view->storage->id() . '__' . $node->view->current_display;
        $suggestions['__DEPRECATED']['node__view__' . $node->view->storage->id() . '__' . $node->view->current_display] = 'Theme suggestion node__view__' . $node->view->storage->id() . '__' . $node->view->current_display . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3541462';
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
      $suggestions['__DEPRECATED']['comment__view__' . $comment->view->storage->id()] = 'Theme suggestion comment__view__' . $comment->view->storage->id() . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3541462';
      if (!empty($comment->view->current_display)) {
        $suggestions[] = 'comment__view__' . $comment->view->storage->id() . '__' . $comment->view->current_display;
        $suggestions['__DEPRECATED']['comment__view__' . $comment->view->storage->id() . '__' . $comment->view->current_display] = 'Theme suggestion comment__view__' . $comment->view->storage->id() . '__' . $comment->view->current_display . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3541462';
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
  public function fieldConfigInsert(EntityInterface $field): void {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_config'.
   */
  #[Hook('field_config_update')]
  public function fieldConfigUpdate(EntityInterface $entity): void {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_config'.
   */
  #[Hook('field_config_delete')]
  public function fieldConfigDelete(EntityInterface $entity): void {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('base_field_override_insert')]
  public function baseFieldOverrideInsert(EntityInterface $entity): void {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('base_field_override_update')]
  public function baseFieldOverrideUpdate(EntityInterface $entity): void {
    Views::viewsData()->clear();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('base_field_override_delete')]
  public function baseFieldOverrideDelete(EntityInterface $entity): void {
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
  public function viewPresave(ViewEntityInterface $view): void {
    /** @var \Drupal\views\ViewsConfigUpdater $config_updater */
    $config_updater = \Drupal::service(ViewsConfigUpdater::class);
    $config_updater->updateAll($view);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for blocks.
   */
  #[Hook('block_presave')]
  public function blockPresave(BlockInterface $block): void {
    if (str_starts_with($block->getPluginId(), 'views_block:')) {
      $settings = $block->get('settings');
      if (isset($settings['items_per_page']) && $settings['items_per_page'] === 'none') {
        @trigger_error('Saving a views block with "none" items per page is deprecated in drupal:11.2.0 and removed in drupal:12.0.0. To use the items per page defined by the view, use NULL. See https://www.drupal.org/node/3522240', E_USER_DEPRECATED);
        $settings['items_per_page'] = NULL;
        $block->set('settings', $settings);
      }
    }
  }

}
