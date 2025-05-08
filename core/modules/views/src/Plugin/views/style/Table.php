<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\wizard\WizardInterface;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "table",
  title: new TranslatableMarkup("Table"),
  help: new TranslatableMarkup("Displays rows in a table."),
  theme: "views_view_table",
  display_types: ["normal"],
)]
class Table extends StylePluginBase implements CacheableDependencyInterface {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Should field labels be enabled by default.
   *
   * @var bool
   */
  protected $defaultFieldLabels = TRUE;

  /**
   * Contains the current active sort column.
   *
   * @var string
   */
  public $active;

  /**
   * Contains the current active sort order, either desc or asc.
   *
   * @var string
   */
  public $order;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['columns'] = ['default' => []];
    $options['class'] = ['default' => ''];
    $options['default'] = ['default' => ''];
    $options['info'] = ['default' => []];
    $options['override'] = ['default' => TRUE];
    $options['sticky'] = ['default' => FALSE];
    $options['order'] = ['default' => 'asc'];
    $options['caption'] = ['default' => ''];
    $options['summary'] = ['default' => ''];
    $options['description'] = ['default' => ''];
    $options['empty_table'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSort() {
    $order = $this->view->getRequest()->query->get('order');
    if (!isset($order) && ($this->options['default'] == -1 || empty($this->view->field[$this->options['default']]))) {
      return TRUE;
    }

    // If a sort we don't know anything about gets through, exit gracefully.
    if (isset($order) && empty($this->view->field[$order])) {
      return TRUE;
    }

    // Let the builder know whether or not we're overriding the default sorts.
    return empty($this->options['override']);
  }

  /**
   * Add our actual sort criteria.
   */
  public function buildSortPost() {
    $query = $this->view->getRequest()->query;
    $order = $query->get('order');
    if (!isset($order)) {
      // Check for a 'default' clickSort. If there isn't one, exit gracefully.
      if (empty($this->options['default'])) {
        return;
      }
      $sort = $this->options['default'];
      if (!empty($this->options['info'][$sort]['default_sort_order'])) {
        $this->order = $this->options['info'][$sort]['default_sort_order'];
      }
      else {
        $this->order = !empty($this->options['order']) ? $this->options['order'] : 'asc';
      }
    }
    else {
      $sort = $order;
      // Store the $order for later use.
      $request_sort = $query->get('sort');
      $this->order = !empty($request_sort) ? strtolower($request_sort) : 'asc';
    }

    // If a sort we don't know anything about gets through, exit gracefully.
    if (empty($this->view->field[$sort])) {
      return;
    }

    // Ensure $this->order is valid.
    if ($this->order != 'asc' && $this->order != 'desc') {
      $this->order = 'asc';
    }

    // Store the $sort for later use.
    $this->active = $sort;

    // Tell the field to click sort.
    $this->view->field[$sort]->clickSort($this->order);
  }

  /**
   * Sanitizes the columns.
   *
   * Normalize a list of columns based upon the fields that are
   * available. This compares the fields stored in the style handler
   * to the list of fields actually in the view, removing fields that
   * have been removed and adding new fields in their own column.
   *
   * - Each field must be in a column.
   * - Each column must be based upon a field, and that field
   *   is somewhere in the column.
   * - Any fields not currently represented must be added.
   * - Columns must be re-ordered to match the fields.
   *
   * @param string[][] $columns
   *   An array of all fields; the key is the id of the field and the
   *   value is the id of the column the field should be in.
   * @param string[] $fields
   *   The fields to use for the columns. If not provided, they will
   *   be requested from the current display. The running render should
   *   send the fields through, as they may be different than what the
   *   display has listed due to access control or other changes.
   *
   * @return array
   *   An array of all the sanitized columns.
   */
  public function sanitizeColumns($columns, $fields = NULL) {
    $sanitized = [];
    if ($fields === NULL) {
      $fields = $this->displayHandler->getOption('fields');
    }
    // Pre-configure the sanitized array so that the order is retained.
    foreach ($fields as $field => $info) {
      // Set to itself so that if it isn't touched, it gets column
      // status automatically.
      $sanitized[$field] = $field;
    }

    foreach ($columns as $field => $column) {
      // first, make sure the field still exists.
      if (!isset($sanitized[$field])) {
        continue;
      }

      // If the field is the column, mark it so, or the column
      // it's set to is a column, that's ok
      if ($field == $column || $columns[$column] == $column && !empty($sanitized[$column])) {
        $sanitized[$field] = $column;
      }
      // Since we set the field to itself initially, ignoring
      // the condition is ok; the field will get its column
      // status back.
    }

    return $sanitized;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = [
        '#markup' => '<div class="messages messages--error">' . $this->t('You need at least one field before you can configure your table settings') . '</div>',
      ];
      return;
    }

    $form['class'] = [
      '#title' => $this->t('Table CSS classes'),
      '#type' => 'textfield',
      '#description' => $this->t('Classes to provide on the table. Separate multiple classes with a space. Example: classA classB'),
      '#default_value' => $this->options['class'],
    ];

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override normal sorting if click sorting is used'),
      '#default_value' => !empty($this->options['override']),
    ];

    $form['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Drupal style "sticky" table headers'),
      '#default_value' => !empty($this->options['sticky']),
      '#description' => $this->t('(Sticky header effects will not be active for preview below, only on live output.)'),
    ];

    $form['caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption for the table'),
      '#description' => $this->t('A title semantically associated with your table for increased accessibility.'),
      '#default_value' => $this->options['caption'],
      '#maxlength' => 255,
    ];

    $form['accessibility_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Table details'),
    ];

    $form['summary'] = [
      '#title' => $this->t('Summary title'),
      '#type' => 'textfield',
      '#default_value' => $this->options['summary'],
      '#fieldset' => 'accessibility_details',
    ];

    $form['description'] = [
      '#title' => $this->t('Table description'),
      '#type' => 'textarea',
      '#description' => $this->t('Provide additional details about the table to increase accessibility.'),
      '#default_value' => $this->options['description'],
      '#states' => [
        'visible' => [
          'input[name="style_options[summary]"]' => ['filled' => TRUE],
        ],
      ],
      '#fieldset' => 'accessibility_details',
    ];

    // Note: views UI registers this theme handler on our behalf. Your module
    // will have to register your theme handlers if you do stuff like this.
    $form['#theme'] = 'views_ui_style_plugin_table';

    $columns = $this->sanitizeColumns($this->options['columns']);

    // Create an array of allowed columns from the data we know:
    $field_names = $this->displayHandler->getFieldLabels();

    if (isset($this->options['default'])) {
      $default = $this->options['default'];
      if (!isset($columns[$default])) {
        $default = -1;
      }
    }
    else {
      $default = -1;
    }

    foreach ($columns as $field => $column) {
      $column_selector = ':input[name="style_options[columns][' . $field . ']"]';

      $form['columns'][$field] = [
        '#title' => $this->t('Columns for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => $field_names,
        '#default_value' => $column,
      ];
      if ($handlers[$field]->clickSortable()) {
        $form['info'][$field]['sortable'] = [
          '#title' => $this->t('Sortable for @field', ['@field' => $field]),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => !empty($this->options['info'][$field]['sortable']),
          '#states' => [
            'visible' => [
              $column_selector => ['value' => $field],
            ],
          ],
        ];
        $form['info'][$field]['default_sort_order'] = [
          '#title' => $this->t('Default sort order for @field', ['@field' => $field]),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#options' => ['asc' => $this->t('Ascending'), 'desc' => $this->t('Descending')],
          '#default_value' => !empty($this->options['info'][$field]['default_sort_order']) ? $this->options['info'][$field]['default_sort_order'] : 'asc',
          '#states' => [
            'visible' => [
              $column_selector => ['value' => $field],
              ':input[name="style_options[info][' . $field . '][sortable]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        // Provide an ID so we can have such things.
        $radio_id = Html::getUniqueId('edit-default-' . $field);
        $form['default'][$field] = [
          '#title' => $this->t('Default sort for @field', ['@field' => $field]),
          '#title_display' => 'invisible',
          '#type' => 'radio',
          '#return_value' => $field,
          '#parents' => ['style_options', 'default'],
          '#id' => $radio_id,
          // Because 'radio' doesn't fully support '#id' =(
          '#attributes' => ['id' => $radio_id],
          '#default_value' => $default,
          '#states' => [
            'visible' => [
              $column_selector => ['value' => $field],
            ],
          ],
        ];
      }
      $form['info'][$field]['align'] = [
        '#title' => $this->t('Alignment for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#default_value' => !empty($this->options['info'][$field]['align']) ? $this->options['info'][$field]['align'] : '',
        '#options' => [
          '' => $this->t('None'),
          'views-align-left' => $this->t('Left', [], ['context' => 'Text alignment']),
          'views-align-center' => $this->t('Center', [], ['context' => 'Text alignment']),
          'views-align-right' => $this->t('Right', [], ['context' => 'Text alignment']),
        ],
        '#states' => [
          'visible' => [
            $column_selector => ['value' => $field],
          ],
        ],
      ];
      $form['info'][$field]['separator'] = [
        '#title' => $this->t('Separator for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#size' => 10,
        '#default_value' => $this->options['info'][$field]['separator'] ?? '',
        '#states' => [
          'visible' => [
            $column_selector => ['value' => $field],
          ],
        ],
      ];
      $form['info'][$field]['empty_column'] = [
        '#title' => $this->t('Hide empty column for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'checkbox',
        '#default_value' => $this->options['info'][$field]['empty_column'] ?? FALSE,
        '#states' => [
          'visible' => [
            $column_selector => ['value' => $field],
          ],
        ],
      ];
      $form['info'][$field]['responsive'] = [
        '#title' => $this->t('Responsive setting for @field', ['@field' => $field]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#default_value' => $this->options['info'][$field]['responsive'] ?? '',
        '#options' => ['' => $this->t('High'), RESPONSIVE_PRIORITY_MEDIUM => $this->t('Medium'), RESPONSIVE_PRIORITY_LOW => $this->t('Low')],
        '#states' => [
          'visible' => [
            $column_selector => ['value' => $field],
          ],
        ],
      ];

      // Markup for the field name
      $form['info'][$field]['name'] = [
        '#markup' => $field_names[$field],
      ];
    }

    // Provide a radio for no default sort
    $form['default'][-1] = [
      '#title' => $this->t('No default sort'),
      '#title_display' => 'invisible',
      '#type' => 'radio',
      '#return_value' => -1,
      '#parents' => ['style_options', 'default'],
      '#id' => 'edit-default-0',
      '#default_value' => $default,
    ];

    $form['empty_table'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the empty text in the table'),
      '#default_value' => $this->options['empty_table'],
      '#description' => $this->t('Per default the table is hidden for an empty view. With this option it is possible to show an empty table with the text in it.'),
    ];

    $form['description_markup'] = [
      '#markup' => '<div class="js-form-item form-item description">' . $this->t('Place fields into columns; you may combine multiple fields into the same column. If you do, the separator in the column specified will be used to separate the fields. Check the sortable box to make that column click sortable, and check the default sort radio to determine which column will be sorted by default, if any. You may control column order and field labels in the fields section.') . '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return parent::evenEmpty() || !empty($this->options['empty_table']);
  }

  /**
   * {@inheritdoc}
   */
  public function wizardSubmit(&$form, FormStateInterface $form_state, WizardInterface $wizard, &$display_options, $display_type) {
    // If any of the displays use the table style, make sure that the fields
    // always have a labels by unsetting the override.
    foreach ($display_options['default']['fields'] as &$field) {
      unset($field['label']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];

    foreach ($this->options['info'] as $info) {
      if (!empty($info['sortable'])) {
        // The rendered link needs to play well with any other query parameter
        // used on the page, like pager and exposed filter.
        $contexts[] = 'url.query_args';
        break;
      }
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
