<?php

namespace Drupal\views_ui\Hook;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for views_ui.
 */
class ViewsUiThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      // Edit a view.
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
        'initial preprocess' => static::class . ':preprocessDisplayTabSetting',
      ],
      'views_ui_display_tab_bucket' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessDisplayTabBucket',
      ],
      'views_ui_rearrange_filter_form' => [
        'render element' => 'form',
        'initial preprocess' => static::class . ':preprocessRearrangeFilterForm',
      ],
      'views_ui_expose_filter_form' => [
        'render element' => 'form',
      ],
      // Legacy theme hook for displaying views info.
      'views_ui_view_info' => [
        'variables' => [
          'view' => NULL,
          'displays' => NULL,
        ],
      ],
      // List views.
      'views_ui_views_listing_table' => [
        'variables' => [
          'headers' => NULL,
          'rows' => NULL,
          'attributes' => [],
        ],
        'initial preprocess' => static::class . ':preprocessViewsListingTable',
      ],
      'views_ui_view_displays_list' => [
        'variables' => [
          'displays' => [],
        ],
      ],
      // Group of filters.
      'views_ui_build_group_filter_form' => [
        'render element' => 'form',
        'initial preprocess' => static::class . ':preprocessBuildGroupFilterForm',
      ],
      // On behalf of a plugin.
      'views_ui_style_plugin_table' => [
        'render element' => 'form',
        'initial preprocess' => static::class . ':preprocessStylePluginTable',
      ],
      // When previewing a view.
      'views_ui_view_preview_section' => [
        'variables' => [
          'view' => NULL,
          'section' => NULL,
          'content' => NULL,
          'links' => '',
        ],
        'initial preprocess' => static::class . ':preprocessViewPreviewSection',
      ],
      // Generic container wrapper, to use instead of theme_container when an id
      // is not desired.
      'views_ui_container' => [
        'variables' => [
          'children' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Prepares variables for Views UI display tab setting templates.
   *
   * Default template: views-ui-display-tab-setting.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - link: The setting's primary link.
   *   - settings_links: An array of links for this setting.
   *   - defaulted: A boolean indicating the setting is in its default state.
   *   - overridden: A boolean indicating the setting has been overridden from
   *     the default.
   *   - description: The setting's description.
   *   - description_separator: A boolean indicating a separator colon should be
   *     appended to the setting's description.
   */
  public function preprocessDisplayTabSetting(array &$variables): void {
    // Put the primary link to the left side.
    array_unshift($variables['settings_links'], $variables['link']);

    if (!empty($variables['overridden'])) {
      $variables['attributes']['title'][] = $this->t('Overridden');
    }

    // Append a colon to the description, if requested.
    if ($variables['description'] && $variables['description_separator']) {
      $variables['description'] .= $this->t(':');
    }
  }

  /**
   * Prepares variables for Views UI view listing templates.
   *
   * Default template: views-ui-view-listing-table.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - headers: An associative array containing the headers for the view
   *     listing table.
   *   - rows: An associative array containing the rows data for the view
   *     listing table.
   */
  public function preprocessViewsListingTable(array &$variables): void {
    // Convert the attributes to valid attribute objects.
    foreach ($variables['headers'] as $key => $header) {
      $variables['headers'][$key]['attributes'] = new Attribute($header['#attributes']);
    }

    if (!empty($variables['rows'])) {
      foreach ($variables['rows'] as $key => $row) {
        $variables['rows'][$key]['attributes'] = new Attribute($row['#attributes']);
      }
    }
  }

  /**
   * Prepares variables for Views UI display tab bucket templates.
   *
   * Default template: views-ui-display-tab-bucket.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #name, #overridden, #children, #title, #actions.
   */
  public function preprocessDisplayTabBucket(array &$variables): void {
    $element = $variables['element'];

    if (!empty($element['#overridden'])) {
      $variables['attributes']['title'][] = $this->t('Overridden');
    }

    $variables['name'] = $element['#name'] ?? NULL;
    $variables['overridden'] = $element['#overridden'] ?? NULL;
    $variables['content'] = $element['#children'];
    $variables['title'] = $element['#title'];
    $variables['actions'] = !empty($element['#actions']) ? $element['#actions'] : [];
  }

  /**
   * Prepares variables for Views UI build group filter form templates.
   *
   * Default template: views-ui-build-group-filter-form.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - form: A render element representing the form.
   */
  public function preprocessBuildGroupFilterForm(array &$variables): void {
    $form = $variables['form'];

    // Prepare table of options.
    $header = [
      $this->t('Default'),
      $this->t('Weight'),
      $this->t('Label'),
      $this->t('Operator'),
      $this->t('Value'),
      $this->t('Operations'),
    ];

    // Prepare default selectors.
    $form_state = new FormState();
    $form['default_group'] = Radios::processRadios($form['default_group'], $form_state, $form);
    $form['default_group_multiple'] = Checkboxes::processCheckboxes($form['default_group_multiple'], $form_state, $form);
    $form['default_group']['All']['#title'] = '';

    $rows[] = [
      ['data' => $form['default_group']['All']],
      '',
      [
        'data' => $this->configFactory->get('views.settings')->get('ui.exposed_filter_any_label') == 'old_any' ? $this->t('&lt;Any&gt;') : $this->t('- Any -'),
        'colspan' => 4,
        'class' => ['class' => 'any-default-radios-row'],
      ],
    ];
    // Remove the 'All' default_group form element because it's added to the
    // table row.
    unset($variables['form']['default_group']['All']);

    foreach (Element::children($form['group_items']) as $group_id) {
      $form['group_items'][$group_id]['value']['#title'] = '';
      $default = [
        $form['default_group'][$group_id],
        $form['default_group_multiple'][$group_id],
      ];
      // Remove these fields from the form since they are moved into the table.
      unset($variables['form']['default_group'][$group_id]);
      unset($variables['form']['default_group_multiple'][$group_id]);

      $link = [
        '#type' => 'link',
        '#url' => Url::fromRoute('<none>', [], [
          'attributes' => [
            'id' => 'views-remove-link-' . $group_id,
            'class' => [
              'views-hidden',
              'views-button-remove',
              'views-groups-remove-link',
              'views-remove-link',
            ],
            'alt' => $this->t('Remove this item'),
            'title' => $this->t('Remove this item'),
          ],
        ]),
        '#title' => new FormattableMarkup('<span>@text</span>', ['@text' => $this->t('Remove')]),
      ];
      $remove = [$form['group_items'][$group_id]['remove'], $link];
      $data = [
        'default' => ['data' => $default],
        'weight' => ['data' => $form['group_items'][$group_id]['weight']],
        'title' => ['data' => $form['group_items'][$group_id]['title']],
        'operator' => ['data' => $form['group_items'][$group_id]['operator']],
        'value' => ['data' => $form['group_items'][$group_id]['value']],
        'remove' => ['data' => $remove],
      ];
      $rows[] = ['data' => $data, 'id' => 'views-row-' . $group_id, 'class' => ['draggable']];
    }
    $variables['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['views-filter-groups'],
        'id' => 'views-filter-groups',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
    ];

    // Hide fields used in table.
    unset($variables['form']['group_items']);
  }

  /**
   * Prepares variables for Views UI rearrange filter form templates.
   *
   * Default template: views-ui-rearrange-filter-form.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - form: A render element representing the form.
   */
  public function preprocessRearrangeFilterForm(array &$variables): void {
    $form = &$variables['form'];
    $rows = $ungroupable_rows = [];
    // Enable grouping only if > 1 group.
    $variables['grouping'] = count(array_keys($form['#group_options'])) > 1;

    foreach ($form['#group_renders'] as $group_id => $contents) {
      // Header row for the group.
      if ($group_id !== 'ungroupable') {
        // Set up tabledrag so that it changes the group dropdown when rows are
        // dragged between groups.
        $options = [
          'table_id' => 'views-rearrange-filters',
          'action' => 'match',
          'relationship' => 'sibling',
          'group' => 'views-group-select',
          'subgroup' => 'views-group-select-' . $group_id,
        ];
        drupal_attach_tabledrag($form['override'], $options);

        // Title row, spanning all columns.
        $row = [];
        // Add a cell to the first row, containing the group operator.
        $row[] = [
          'class' => ['group', 'group-operator', 'container-inline'],
          'data' => $form['filter_groups']['groups'][$group_id],
          'rowspan' => max([2, count($contents) + 1]),
        ];
        // Title.
        $row[] = [
          'class' => ['group', 'group-title'],
          'data' => [
            '#prefix' => '<span>',
            '#markup' => $form['#group_options'][$group_id],
            '#suffix' => '</span>',
          ],
          'colspan' => 4,
        ];
        $rows[] = [
          'class' => ['views-group-title'],
          'data' => $row,
          'id' => 'views-group-title-' . $group_id,
        ];

        // Row which will only appear if the group has nothing in it.
        $row = [];
        $class = 'group-' . (count($contents) ? 'populated' : 'empty');
        $instructions = '<span>' . $this->t('No filters have been added.') . '</span> <span class="js-only">' . $this->t('Drag to add filters.') . '</span>';
        // When JavaScript is enabled, the button for removing the group (if
        // it's present) should be hidden, since it will be replaced by a link
        // on the client side.
        if (!empty($form['remove_groups'][$group_id]['#type']) && $form['remove_groups'][$group_id]['#type'] == 'submit') {
          $form['remove_groups'][$group_id]['#attributes']['class'][] = 'js-hide';
        }
        $row[] = [
          'colspan' => 5,
          'data' => [
            ['#markup' => $instructions],
            $form['remove_groups'][$group_id],
          ],
        ];
        $rows[] = [
          'class' => [
            'group-message',
            'group-' . $group_id . '-message',
            $class,
          ],
          'data' => $row,
          'id' => 'views-group-' . $group_id,
        ];
      }

      foreach ($contents as $id) {
        if (isset($form['filters'][$id]['name'])) {
          $row = [];
          $row[]['data'] = $form['filters'][$id]['name'];
          $form['filters'][$id]['weight']['#attributes']['class'] = ['weight'];
          $row[]['data'] = $form['filters'][$id]['weight'];
          $form['filters'][$id]['group']['#attributes']['class'] = ['views-group-select views-group-select-' . $group_id];
          $row[]['data'] = $form['filters'][$id]['group'];
          $form['filters'][$id]['removed']['#attributes']['class'][] = 'js-hide';

          $remove_link = [
            '#type' => 'link',
            '#url' => Url::fromRoute('<none>'),
            '#title' => new FormattableMarkup('<span>@text</span>', ['@text' => $this->t('Remove')]),
            '#weight' => '1',
            '#options' => [
              'attributes' => [
                'id' => 'views-remove-link-' . $id,
                'class' => [
                  'views-hidden',
                  'views-button-remove',
                  'views-groups-remove-link',
                  'views-remove-link',
                ],
                'alt' => $this->t('Remove this item'),
                'title' => $this->t('Remove this item'),
              ],
            ],
          ];
          $row[]['data'] = [
            $form['filters'][$id]['removed'],
            $remove_link,
          ];

          $row = [
            'data' => $row,
            'class' => ['draggable'],
            'id' => 'views-row-' . $id,
          ];

          if ($group_id !== 'ungroupable') {
            $rows[] = $row;
          }
          else {
            $ungroupable_rows[] = $row;
          }
        }
      }
    }

    if (!$variables['grouping']) {
      $form['filter_groups']['groups'][0]['#title'] = $this->t('Operator');
    }

    if (!empty($ungroupable_rows)) {
      $header = [
        $this->t('Ungroupable filters'),
        $this->t('Weight'),
        [
          'data' => $this->t('Group'),
          'class' => ['views-hide-label'],
        ],
        [
          'data' => $this->t('Remove'),
          'class' => ['views-hide-label'],
        ],
      ];
      $variables['ungroupable_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $ungroupable_rows,
        '#attributes' => [
          'id' => 'views-rearrange-filters-ungroupable',
          'class' => ['arrange'],
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'weight',
          ],
        ],
      ];
    }

    if (empty($rows)) {
      $rows[] = [['data' => $this->t('No fields available.'), 'colspan' => '2']];
    }

    // Set up tabledrag so that the weights are changed when rows are dragged.
    $variables['table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'views-rearrange-filters',
        'class' => ['arrange'],
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
    ];

    // When JavaScript is enabled, the button for adding a new group should be
    // hidden, since it will be replaced by a link on the client side.
    $form['actions']['add_group']['#attributes']['class'][] = 'js-hide';

  }

  /**
   * Prepares variables for style plugin table templates.
   *
   * Default template: views-ui-style-plugin-table.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - form: A render element representing the form.
   */
  public function preprocessStylePluginTable(array &$variables): void {
    $form = $variables['form'];

    $header = [
      $this->t('Field'),
      $this->t('Column'),
      $this->t('Align'),
      $this->t('Separator'),
      [
        'data' => $this->t('Sortable'),
        'align' => 'center',
      ],
      [
        'data' => $this->t('Default order'),
        'align' => 'center',
      ],
      [
        'data' => $this->t('Default sort'),
        'align' => 'center',
      ],
      [
        'data' => $this->t('Hide empty column'),
        'align' => 'center',
      ],
      [
        'data' => $this->t('Responsive'),
        'align' => 'center',
      ],
    ];
    $rows = [];
    foreach (Element::children($form['columns']) as $id) {
      $row = [];
      $row[]['data'] = $form['info'][$id]['name'];
      $row[]['data'] = $form['columns'][$id];
      $row[]['data'] = $form['info'][$id]['align'];
      $row[]['data'] = $form['info'][$id]['separator'];

      if (!empty($form['info'][$id]['sortable'])) {
        $row[] = [
          'data' => $form['info'][$id]['sortable'],
          'align' => 'center',
        ];
        $row[] = [
          'data' => $form['info'][$id]['default_sort_order'],
          'align' => 'center',
        ];
        $row[] = [
          'data' => $form['default'][$id],
          'align' => 'center',
        ];
      }
      else {
        $row[] = '';
        $row[] = '';
        $row[] = '';
      }
      $row[] = [
        'data' => $form['info'][$id]['empty_column'],
        'align' => 'center',
      ];
      $row[] = [
        'data' => $form['info'][$id]['responsive'],
        'align' => 'center',
      ];
      $rows[] = $row;
    }

    // Add the special 'None' row.
    $rows[] = [
      ['data' => $this->t('None'), 'colspan' => 6],
      ['align' => 'center', 'data' => $form['default'][-1]],
      ['colspan' => 2],
    ];

    // Unset elements from the form array that are used to build the table so
    // that they are not rendered twice.
    unset($form['default']);
    unset($form['info']);
    unset($form['columns']);

    $variables['table'] = [
      '#type' => 'table',
      '#theme' => 'table__views_ui_style_plugin_table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $variables['form'] = $form;
  }

  /**
   * Prepares variables for views UI view preview section templates.
   *
   * Default template: views-ui-view-preview-section.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - view: The view object.
   *   - section: The section name of a View (e.g. title, rows or pager).
   */
  public function preprocessViewPreviewSection(array &$variables): void {
    switch ($variables['section']) {
      case 'title':
        $variables['title'] = $this->t('Title');
        $links = $this->viewPreviewSectionDisplayCategoryLinks($variables['view'], 'title', $variables['title']);
        break;

      case 'header':
        $variables['title'] = $this->t('Header');
        $links = $this->viewPreviewSectionHandlerLinks($variables['view'], $variables['section']);
        break;

      case 'empty':
        $variables['title'] = $this->t('No results behavior');
        $links = $this->viewPreviewSectionHandlerLinks($variables['view'], $variables['section']);
        break;

      case 'exposed':
        // @todo Sorts can be exposed too, so we may need a better title.
        $variables['title'] = $this->t('Exposed Filters');
        $links = $this->viewPreviewSectionDisplayCategoryLinks($variables['view'], 'exposed_form_options', $variables['title']);
        break;

      case 'rows':
        // @todo The title needs to depend on what is being viewed.
        $variables['title'] = $this->t('Content');
        $links = $this->viewPreviewSectionRowsLinks($variables['view']);
        break;

      case 'pager':
        $variables['title'] = $this->t('Pager');
        $links = $this->viewPreviewSectionDisplayCategoryLinks($variables['view'], 'pager_options', $variables['title']);
        break;

      case 'more':
        $variables['title'] = $this->t('More');
        $links = $this->viewPreviewSectionDisplayCategoryLinks($variables['view'], 'use_more', $variables['title']);
        break;

      case 'footer':
        $variables['title'] = $this->t('Footer');
        $links = $this->viewPreviewSectionHandlerLinks($variables['view'], $variables['section']);
        break;

      case 'attachment_before':
        // @todo Add links to the attachment configuration page.
        $variables['title'] = $this->t('Attachment before');
        break;

      case 'attachment_after':
        // @todo Add links to the attachment configuration page.
        $variables['title'] = $this->t('Attachment after');
        break;
    }

    if (isset($links)) {
      $build = [
        '#theme' => 'links__contextual',
        '#links' => $links,
        '#attributes' => ['class' => ['contextual-links']],
        '#attached' => [
          'library' => ['contextual/drupal.contextual-links'],
        ],
      ];
      $variables['links'] = $build;
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for views templates.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(&$variables): void {
    $view = $variables['view'];
    // Render title for the admin preview.
    if (!empty($view->live_preview)) {
      $variables['title'] = [
        '#markup' => $view->getTitle(),
        '#allowed_tags' => Xss::getHtmlTagList(),
      ];
    }
    if (!empty($view->live_preview) && $this->moduleHandler->moduleExists('contextual')) {
      $view->setShowAdminLinks(FALSE);
      foreach ([
        'title',
        'header',
        'exposed',
        'rows',
        'pager',
        'more',
        'footer',
        'empty',
        'attachment_after',
        'attachment_before',
      ] as $section) {
        if (!empty($variables[$section])) {
          $variables[$section] = [
            '#theme' => 'views_ui_view_preview_section',
            '#view' => $view,
            '#section' => $section,
            '#content' => $variables[$section],
            '#theme_wrappers' => [
              'views_ui_container',
            ],
            '#attributes' => [
              'class' => [
                'contextual-region',
              ],
            ],
          ];
        }
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_views_ui_view_preview_section')]
  public function themeSuggestionsViewsUiViewPreviewSection(array $variables): array {
    return [
      'views_ui_view_preview_section__' . $variables['section'],
    ];
  }

  /**
   * Returns contextual links for each handler of a certain section.
   *
   * @todo Bring in relationships.
   * @todo Refactor this function to use much stuff of
   *    views_ui_edit_form_get_bucket.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable.
   * @param string $type
   *   The section type.
   * @param string|\Stringable|false $title
   *   Add a bolded title of this section.
   */
  protected function viewPreviewSectionHandlerLinks(ViewExecutable $view, string $type, string|\Stringable|false $title = FALSE): array {
    $display = $view->display_handler->display;
    $handlers = $view->display_handler->getHandlers($type);
    $links = [];

    $types = ViewExecutable::getHandlerTypes();
    if ($title) {
      $links[$type . '-title'] = [
        'title' => $types[$type]['title'],
      ];
    }

    foreach ($handlers as $id => $handler) {
      $field_name = $handler->adminLabel(TRUE);
      $links[$type . '-edit-' . $id] = [
        'title' => $this->t('Edit @section', ['@section' => $field_name]),
        'url' => Url::fromRoute('views_ui.form_handler', [
          'js' => 'nojs',
          'view' => $view->storage->id(),
          'display_id' => $display['id'],
          'type' => $type,
          'id' => $id,
        ]),
        'attributes' => ['class' => ['views-ajax-link']],
      ];
    }
    $links[$type . '-add'] = [
      'title' => $this->t('Add new'),
      'url' => Url::fromRoute('views_ui.form_add_handler', [
        'js' => 'nojs',
        'view' => $view->storage->id(),
        'display_id' => $display['id'],
        'type' => $type,
      ]),
      'attributes' => ['class' => ['views-ajax-link']],
    ];

    return $links;
  }

  /**
   * Returns a link to editing a certain display setting.
   */
  protected function viewPreviewSectionDisplayCategoryLinks(ViewExecutable $view, $type, $title): array {
    $display = $view->display_handler->display;
    $links = [
      $type . '-edit' => [
        'title' => $this->t('Edit @section', ['@section' => $title]),
        'url' => Url::fromRoute('views_ui.form_display', [
          'js' => 'nojs',
          'view' => $view->storage->id(),
          'display_id' => $display['id'],
          'type' => $type,
        ]),
        'attributes' => ['class' => ['views-ajax-link']],
      ],
    ];

    return $links;
  }

  /**
   * Returns all contextual links for the main content part of the view.
   */
  protected function viewPreviewSectionRowsLinks(ViewExecutable $view): array {
    $links = [];
    $links = array_merge($links, $this->viewPreviewSectionHandlerLinks($view, 'filter', TRUE));
    $links = array_merge($links, $this->viewPreviewSectionHandlerLinks($view, 'field', TRUE));
    $links = array_merge($links, $this->viewPreviewSectionHandlerLinks($view, 'sort', TRUE));
    $links = array_merge($links, $this->viewPreviewSectionHandlerLinks($view, 'argument', TRUE));
    $links = array_merge($links, $this->viewPreviewSectionHandlerLinks($view, 'relationship', TRUE));

    return $links;
  }

}
