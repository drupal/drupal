<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * @defgroup views_filter_handlers Views filter handler plugins
 * @{
 * Plugins that handle views filtering.
 *
 * Filter handler plugins extend
 * \Drupal\views\Plugin\views\filter\FilterPluginBase. They must be attributed
 * with \Drupal\views\Attribute\ViewsFilter attribute, and they must be in
 * namespace directory Plugin\views\filter.
 *
 * The following items can go into a hook_views_data() implementation in a
 * filter section to affect how the filter handler will behave:
 * - allow empty: If true, the 'IS NULL' and 'IS NOT NULL' operators become
 *   available as standard operators.
 *
 * You can refine the behavior of filters by setting the following Boolean
 * member variables to TRUE in your plugin class:
 * - $alwaysMultiple: Disable the possibility of forcing a single value.
 * - $no_operator: Disable the possibility of using operators.
 * - $always_required: Disable the possibility of allowing an exposed input to
 *   be optional.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base class for Views filters handler plugins.
 */
abstract class FilterPluginBase extends HandlerBase implements CacheableDependencyInterface {

  /**
   * A list of restricted identifiers.
   *
   * This list contains strings that could cause clashes with other site
   * operations when used as a filter identifier.
   *
   * @var array
   */
  const RESTRICTED_IDENTIFIERS = [
    'value',
    'q',
    'destination',
    '_format',
    '_wrapper_format',
    'token',
  ];

  /**
   * The value.
   *
   * Contains the actual value of the field,either configured in the views ui
   * or entered in the exposed filters.
   *
   * @var mixed
   */
  public $value = NULL;

  /**
   * Contains the operator which is used on the query.
   *
   * @var string
   */
  public $operator = '=';

  /**
   * Contains the information of the selected item in a grouped filter.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $group_info = NULL;

  /**
   * Disable the possibility to force a single value.
   *
   * @var bool
   */
  protected $alwaysMultiple = FALSE;

  /**
   * Disable the possibility to use operators.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $no_operator = FALSE;

  /**
   * Disable the possibility to allow an exposed input to be optional.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $always_required = FALSE;

  /**
   * Keyed array by alias of table relations.
   *
   * @var string[]
   */
  public ?array $tableAliases;

  /**
   * Overrides \Drupal\views\Plugin\views\HandlerBase::init().
   *
   * Provide some extra help to get the operator/value easier to use.
   *
   * This likely has to be overridden by filters which are more complex
   * than simple operator/value.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->operator = $this->options['operator'];
    $this->value = $this->options['value'];
    $this->group_info = $this->options['group_info']['default_group'];

    // Set the default value of the operator ID.
    if (!empty($options['exposed']) && !empty($options['expose']['operator']) && !isset($options['expose']['operator_id'])) {
      $this->options['expose']['operator_id'] = $options['expose']['operator'];
    }

    if ($this->multipleExposedInput()) {
      $this->group_info = array_filter($options['group_info']['default_group_multiple']);
      $this->options['expose']['multiple'] = TRUE;
    }

    // If there are relationships in the view, allow empty should be true
    // so that we can do IS NULL checks on items. Not all filters respect
    // allow empty, but string and numeric do and that covers enough.
    if ($this->view->display_handler->getOption('relationships')) {
      $this->definition['allow empty'] = TRUE;
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator'] = ['default' => '='];
    $options['value'] = ['default' => ''];
    $options['group'] = ['default' => '1'];
    $options['exposed'] = ['default' => FALSE];
    $options['expose'] = [
      'contains' => [
        'operator_id' => ['default' => FALSE],
        'label' => ['default' => ''],
        'description' => ['default' => ''],
        'use_operator' => ['default' => FALSE],
        'operator' => ['default' => ''],
        'operator_limit_selection' => ['default' => FALSE],
        'operator_list' => ['default' => []],
        'identifier' => ['default' => ''],
        'required' => ['default' => FALSE],
        'remember' => ['default' => FALSE],
        'multiple' => ['default' => FALSE],
        'remember_roles' => [
          'default' => [
            RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID,
          ],
        ],
      ],
    ];

    // A group is a combination of a filter, an operator and a value
    // operating like a single filter.
    // Users can choose from a select box which group they want to apply.
    // Views will filter the view according to the defined values.
    // Because it acts as a standard filter, we have to define
    // an identifier and other settings like the widget and the label.
    // This settings are saved in another array to allow users to switch
    // between a normal filter and a group of filters with a single click.
    $options['is_grouped'] = ['default' => FALSE];
    $options['group_info'] = [
      'contains' => [
        'label' => ['default' => ''],
        'description' => ['default' => ''],
        'identifier' => ['default' => ''],
        'optional' => ['default' => TRUE],
        'widget' => ['default' => 'select'],
        'multiple' => ['default' => FALSE],
        'remember' => ['default' => 0],
        'default_group' => ['default' => 'All'],
        'default_group_multiple' => ['default' => []],
        'group_items' => ['default' => []],
      ],
    ];

    return $options;
  }

  /**
   * Display the filter on the administrative summary.
   */
  public function adminSummary() {
    return $this->operator . ' ' . $this->value;
  }

  /**
   * Determine if a filter can be exposed.
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * Determine if a filter can be converted into a group.
   *
   * Only exposed filters with operators available can be converted into groups.
   */
  protected function canBuildGroup() {
    return $this->isExposed() && (count($this->operatorOptions()) > 0);
  }

  /**
   * Returns TRUE if the exposed filter works like a grouped filter.
   */
  public function isAGroup() {
    return $this->isExposed() && !empty($this->options['is_grouped']);
  }

  /**
   * Provide the basic form which calls through to subforms.
   *
   * If overridden, it is best to call through to the parent,
   * or to at least make sure all of the functions in this form
   * are called.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->canExpose()) {
      $this->showExposeButton($form, $form_state);
    }
    if ($this->canBuildGroup()) {
      $this->showBuildGroupButton($form, $form_state);
    }
    $form['clear_markup_start'] = [
      '#markup' => '<div class="clearfix">',
    ];
    if ($this->isAGroup()) {
      if ($this->canBuildGroup()) {
        $form['clear_markup_start'] = [
          '#markup' => '<div class="clearfix">',
        ];
        // Render the build group form.
        $this->showBuildGroupForm($form, $form_state);
        $form['clear_markup_end'] = [
          '#markup' => '</div>',
        ];
      }
    }
    else {
      // Add the subform from operatorForm().
      $this->showOperatorForm($form, $form_state);
      // Add the subform from valueForm().
      $this->showValueForm($form, $form_state);
      $form['clear_markup_end'] = [
        '#markup' => '</div>',
      ];
      if ($this->canExpose()) {
        // Add the subform from buildExposeForm().
        $this->showExposeForm($form, $form_state);
      }
    }
  }

  /**
   * Simple validate handler.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $this->operatorValidate($form, $form_state);
    $this->valueValidate($form, $form_state);
    if (!empty($this->options['exposed']) && !$this->isAGroup()) {
      $this->validateExposeForm($form, $form_state);
    }
    if ($this->isAGroup()) {
      $this->buildGroupValidate($form, $form_state);
    }
  }

  /**
   * Simple submit handler.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Do not store these values.
    $form_state->unsetValue('expose_button');
    $form_state->unsetValue('group_button');

    if (!$this->isAGroup()) {
      $this->operatorSubmit($form, $form_state);
      $this->valueSubmit($form, $form_state);
    }
    if (!empty($this->options['exposed'])) {
      $this->submitExposeForm($form, $form_state);
    }
    if ($this->isAGroup()) {
      $this->buildGroupSubmit($form, $form_state);
    }
  }

  /**
   * Shortcut to display the operator form.
   */
  public function showOperatorForm(&$form, FormStateInterface $form_state) {
    $this->operatorForm($form, $form_state);
    $form['operator']['#prefix'] = '<div class="views-group-box views-left-30">';
    $form['operator']['#suffix'] = '</div>';
  }

  /**
   * Options form subform for setting the operator.
   *
   * This may be overridden by child classes, and it must
   * define $form['operator'];
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see buildOptionsForm()
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    $options = $this->operatorOptions();
    if (!empty($options)) {
      $form['operator'] = [
        '#type' => count($options) < 10 ? 'radios' : 'select',
        '#title' => $this->t('Operator'),
        '#default_value' => $this->operator,
        '#options' => $options,
      ];
    }
  }

  /**
   * Provide a list of options for the default operator form.
   *
   * Should be overridden by classes that don't override operatorForm.
   */
  public function operatorOptions() {
    return [];
  }

  /**
   * Validate the operator form.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function operatorValidate($form, FormStateInterface $form_state) {}

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function operatorSubmit($form, FormStateInterface $form_state) {}

  /**
   * Shortcut to display the value form.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function showValueForm(&$form, FormStateInterface $form_state) {
    $this->valueForm($form, $form_state);
    if (empty($this->no_operator)) {
      $form['value']['#prefix'] = '<div class="views-group-box views-right-70">' . ($form['value']['#prefix'] ?? '');
      $form['value']['#suffix'] = ($form['value']['#suffix'] ?? '') . '</div>';
    }
  }

  /**
   * Options form subform for setting options.
   *
   * This should be overridden by all child classes and it must
   * define $form['value']
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see buildOptionsForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];
  }

  /**
   * Validate the options form.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function valueValidate($form, FormStateInterface $form_state) {}

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {}

  /**
   * Shortcut to display the exposed options form.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function showBuildGroupForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['is_grouped'])) {
      return;
    }

    $this->buildExposedFiltersGroupForm($form, $form_state);

    // When we click the expose button, we add new gadgets to the form but they
    // have no data in POST so their defaults get wiped out. This prevents
    // these defaults from getting wiped out. This setting will only be TRUE
    // during a 2nd pass rerender.
    if ($form_state->get('force_build_group_options')) {
      foreach (Element::children($form['group_info']) as $id) {
        if (isset($form['group_info'][$id]['#default_value']) && !isset($form['group_info'][$id]['#value'])) {
          $form['group_info'][$id]['#value'] = $form['group_info'][$id]['#default_value'];
        }
      }
    }
  }

  /**
   * Shortcut to display the build_group/hide button.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function showBuildGroupButton(&$form, FormStateInterface $form_state) {

    $form['group_button'] = [
      '#prefix' => '<div class="views-grouped clearfix">',
      '#suffix' => '</div>',
      // Should always come after the description and the relationship.
      '#weight' => -190,
    ];

    $grouped_description = $this->t('Grouped filters allow a choice between predefined operator|value pairs.');
    $form['group_button']['radios'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['js-only']],
    ];
    $form['group_button']['radios']['radios'] = [
      '#title' => $this->t('Filter type to expose'),
      '#description' => $grouped_description,
      '#type' => 'radios',
      '#options' => [
        $this->t('Single filter'),
        $this->t('Grouped filters'),
      ],
    ];

    if (empty($this->options['is_grouped'])) {
      $form['group_button']['markup'] = [
        '#markup' => '<div class="description grouped-description">' . $grouped_description . '</div>',
      ];
      $form['group_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Grouped filters'),
        '#submit' => [[$this, 'buildGroupForm']],
      ];
      $form['group_button']['radios']['radios']['#default_value'] = 0;
    }
    else {
      $form['group_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Single filter'),
        '#submit' => [[$this, 'buildGroupForm']],
      ];
      $form['group_button']['radios']['radios']['#default_value'] = 1;
    }
  }

  /**
   * Displays the Build Group form.
   */
  public function buildGroupForm($form, FormStateInterface $form_state) {
    $item = &$this->options;
    // flip. If the filter was a group, set back to a standard filter.
    $item['is_grouped'] = empty($item['is_grouped']);

    // If necessary, set new defaults:
    if ($item['is_grouped']) {
      $this->buildGroupOptions();
    }

    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');
    $view->getExecutable()->setHandler($display_id, $type, $id, $item);

    $view->addFormToStack($form_state->get('form_key'), $display_id, $type, $id, TRUE, TRUE);

    $view->cacheSet();
    $form_state->set('rerender', TRUE);
    $form_state->setRebuild();
    $form_state->get('force_build_group_options', TRUE);
  }

  /**
   * Shortcut to display the expose/hide button.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function showExposeButton(&$form, FormStateInterface $form_state) {
    $form['expose_button'] = [
      '#prefix' => '<div class="views-expose clearfix">',
      '#suffix' => '</div>',
      // Should always come after the description and the relationship.
      '#weight' => -200,
    ];

    // Add a checkbox for JS users, which will have behavior attached to it
    // so it can replace the button.
    $form['expose_button']['checkbox'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['js-only']],
    ];
    $form['expose_button']['checkbox']['checkbox'] = [
      '#title' => $this->t('Expose this filter to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    ];

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = [
        '#markup' => '<div class="description exposed-description">' . $this->t('This filter is not exposed. Expose it to allow the users to change it.') . '</div>',
      ];
      $form['expose_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Expose filter'),
        '#submit' => [[$this, 'displayExposedForm']],
      ];
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = [
        '#markup' => '<div class="description exposed-description">' . $this->t('This filter is exposed. If you hide it, users will not be able to change it.') . '</div>',
      ];
      $form['expose_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Hide filter'),
        '#submit' => [[$this, 'displayExposedForm']],
      ];
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

  /**
   * Options form subform for exposed filter options.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see buildOptionsForm()
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    $form['#theme'] = 'views_ui_expose_filter_form';
    // #flatten will move everything from $form['expose'][$key] to $form[$key]
    // prior to rendering. That's why the preRender for it needs to run first,
    // so that when the next preRender (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], [static::class, 'preRenderFlattenData']);
    $form['expose']['#flatten'] = TRUE;

    if (empty($this->always_required)) {
      $form['expose']['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Required'),
        '#default_value' => $this->options['expose']['required'],
      ];
    }
    else {
      $form['expose']['required'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }
    $form['expose']['label'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => $this->t('Label'),
      '#size' => 40,
    ];

    $form['expose']['description'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['description'],
      '#title' => $this->t('Description'),
      '#size' => 60,
    ];

    if (!empty($form['operator']['#type'])) {
      // Increase the width of the left (operator) column.
      $form['operator']['#prefix'] = '<div class="views-group-box views-left-40">';
      $form['operator']['#suffix'] = '</div>';
      $form['value']['#prefix'] = '<div class="views-group-box views-right-60">';
      $form['value']['#suffix'] = '</div>';

      $form['expose']['use_operator'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expose operator'),
        '#description' => $this->t('Allow the user to choose the operator.'),
        '#default_value' => !empty($this->options['expose']['use_operator']),
      ];

      $operators = $this->operatorOptions();
      if (!empty($operators) && count($operators) > 1) {
        $form['expose']['operator_limit_selection'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Limit the available operators'),
          '#description' => $this->t('Limit the available operators to be shown on the exposed filter.'),
          '#default_value' => !empty($this->options['expose']['operator_limit_selection']),
          '#states' => [
            'visible' => [
              ':input[name="options[expose][use_operator]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['expose']['operator_list'] = [
          '#type' => 'select',
          '#title' => $this->t('Restrict operators to'),
          '#default_value' => $this->options['expose']['operator_list'],
          '#options' => $operators,
          '#multiple' => TRUE,
          '#description' => $this->t('Selecting none will make all of them available.'),
          '#states' => [
            'visible' => [
              ':input[name="options[expose][operator_limit_selection]"]' => ['checked' => TRUE],
              ':input[name="options[expose][use_operator]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

      $form['expose']['operator_id'] = [
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['operator_id'],
        '#title' => $this->t('Operator identifier'),
        '#size' => 40,
        '#description' => $this->t('This will appear in the URL after the ? to identify this operator.'),
        '#states' => [
          'visible' => [
            ':input[name="options[expose][use_operator]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    else {
      $form['expose']['operator_id'] = [
        '#type' => 'value',
        '#value' => '',
      ];
    }

    if (empty($this->alwaysMultiple)) {
      $form['expose']['multiple'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow multiple selections'),
        '#description' => $this->t('Enable to allow users to select multiple items.'),
        '#default_value' => $this->options['expose']['multiple'],
      ];
    }
    $form['expose']['remember'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember the last selection'),
      '#description' => $this->t('Enable to remember the last selection made by the user.'),
      '#default_value' => $this->options['expose']['remember'],
    ];

    $role_options = array_map(fn(RoleInterface $role) => Html::escape($role->label()), Role::loadMultiple());
    $form['expose']['remember_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('Remember exposed selection only for the selected user role(s). If you select no roles, the exposed data will never be stored.'),
      '#default_value' => $this->options['expose']['remember_roles'],
      '#options' => $role_options,
      '#states' => [
        'invisible' => [
          ':input[name="options[expose][remember]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['expose']['identifier'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['identifier'],
      '#title' => $this->t('Filter identifier'),
      '#size' => 40,
      '#description' => $this->t('This will appear in the URL after the ? to identify this filter. Cannot be blank. Only letters, digits and the dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~") characters are allowed. @reserved_identifiers are reserved words and cannot be used.',
        ['@reserved_identifiers' => '"' . implode('", "', self::RESTRICTED_IDENTIFIERS) . '"']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'preRenderFlattenData';
    return $callbacks;
  }

  /**
   * Validate the options form.
   */
  public function validateExposeForm($form, FormStateInterface $form_state) {
    $identifier = $form_state->getValue(['options', 'expose', 'identifier']);
    $this->validateIdentifier($identifier, $form_state, $form['expose']['identifier']);

    $limit_operators = $form_state->getValue(['options', 'expose', 'operator_limit_selection']);
    $operators_selected = $form_state->getValue(['options', 'expose', 'operator_list']);
    $selected_operator = $form_state->getValue(['options', 'operator']);
    if ($limit_operators && !in_array($selected_operator, $operators_selected, TRUE)) {
      $form_state->setError(
        $form['expose']['operator_list'],
        $this->t('You selected the "@operator" operator as the default value but is not included in the list of limited operators.', ['@operator' => $this->operatorOptions()[$selected_operator]]));
    }
  }

  /**
   * Determines if the given grouped filter entry has a valid value.
   *
   * @param array $group
   *   A group entry as defined by buildGroupForm().
   *
   * @return bool
   */
  protected function hasValidGroupedValue(array $group) {
    if (!method_exists($this, 'operators')) {
      throw new \LogicException(get_class($this) . '::operators() not implemented');
    }
    $operators = $this->operators();
    if ($operators[$group['operator']]['values'] == 0) {
      // Some filters, such as "is empty," do not require a value to be
      // specified in order to be valid entries.
      return TRUE;
    }
    else {
      if (is_string($group['value'])) {
        return trim($group['value']) != '';
      }
      elseif (is_array($group['value'])) {
        // Some filters allow multiple options to be selected (for example, node
        // types). Ensure at least the minimum number of values is present for
        // this entry to be considered valid.
        $min_values = $operators[$group['operator']]['values'];
        $actual_values = count(array_filter($group['value'], [static::class, 'arrayFilterZero']));
        return $actual_values >= $min_values;
      }
    }
    return FALSE;
  }

  /**
   * Validate the build group options form.
   */
  protected function buildGroupValidate($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty(['options', 'group_info'])) {
      $identifier = $form_state->getValue(['options', 'group_info', 'identifier']);
      $this->validateIdentifier($identifier, $form_state, $form['group_info']['identifier']);
    }

    if ($group_items = $form_state->getValue(['options', 'group_info', 'group_items'])) {
      foreach ($group_items as $id => $group) {
        if (empty($group['remove'])) {
          $has_valid_value = $this->hasValidGroupedValue($group);
          if ($has_valid_value && $group['title'] == '') {
            if (!method_exists($this, 'operators')) {
              throw new \LogicException(get_class($this) . '::operators() not implemented');
            }
            if (!$this instanceof FilterOperatorsInterface) {
              @trigger_error('Implementing operators() in class ' . get_class($this) . ' without it implementing \Drupal\views\Plugin\views\filter\FilterOperatorsInterface is deprecated in drupal:10.3.0 and will throw a LogicException in drupal:12.0.0. See https://www.drupal.org/node/3412013', E_USER_DEPRECATED);
            }
            $operators = $this->operators();
            if ($operators[$group['operator']]['values'] == 0) {
              $form_state->setError($form['group_info']['group_items'][$id]['title'], $this->t('A label is required for the specified operator.'));
            }
            else {
              $form_state->setError($form['group_info']['group_items'][$id]['title'], $this->t('A label is required if the value for this item is defined.'));
            }
          }
          if (!$has_valid_value && $group['title'] != '') {
            $form_state->setError($form['group_info']['group_items'][$id]['value'], $this->t('A value is required if the label for this item is defined.'));
          }
        }
      }
    }
  }

  /**
   * Validates a filter identifier.
   *
   * Sets the form error if $form_state is passed or an error string if
   * $form_state is not passed.
   *
   * @param string $identifier
   *   The identifier to check.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   (optional) The current state of the form.
   * @param array $form_group
   *   (optional) The form element to set any errors on.
   *
   * @return string
   */
  protected function validateIdentifier($identifier, ?FormStateInterface $form_state = NULL, &$form_group = []) {
    $error = '';
    if (empty($identifier)) {
      $error = $this->t('The identifier is required if the filter is exposed.');
    }
    elseif (in_array($identifier, self::RESTRICTED_IDENTIFIERS)) {
      $error = $this->t('This identifier is not allowed.');
    }
    elseif (preg_match('/[^a-zA-Z0-9_~\.\-]+/', $identifier)) {
      $error = $this->t('This identifier has illegal characters.');
    }

    if ($form_state && !$this->view->display_handler->isIdentifierUnique($form_state->get('id'), $identifier)) {
      $error = $this->t('This identifier is used by another handler.');
    }

    if (!empty($form_state) && !empty($error)) {
      $form_state->setError($form_group, $error);
    }
    return $error;
  }

  /**
   * Save new group items, re-enumerates and remove groups marked to delete.
   */
  protected function buildGroupSubmit($form, FormStateInterface $form_state) {
    $groups = [];
    $group_items = $form_state->getValue(['options', 'group_info', 'group_items']);
    uasort($group_items, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    // Filter out removed items.

    // Start from 1 to avoid problems with #default_value in the widget.
    $new_id = 1;
    $new_default = 'All';
    foreach ($group_items as $id => $group) {
      if (empty($group['remove'])) {
        // Don't store this.
        unset($group['remove']);
        unset($group['weight']);
        $groups[$new_id] = $group;

        if ($form_state->getValue(['options', 'group_info', 'default_group']) == $id) {
          $new_default = $new_id;
        }
      }
      $new_id++;
    }
    if ($new_default != 'All') {
      $form_state->setValue(['options', 'group_info', 'default_group'], $new_default);
    }
    $filter_default_multiple = $form_state->getValue(['options', 'group_info', 'default_group_multiple']);
    $form_state->setValue(['options', 'group_info', 'default_group_multiple'], array_filter($filter_default_multiple));

    $form_state->setValue(['options', 'group_info', 'group_items'], $groups);
  }

  /**
   * Provide default options for exposed filters.
   */
  public function defaultExposeOptions() {
    $this->options['expose'] = [
      'use_operator' => FALSE,
      'operator' => $this->options['id'] . '_op',
      'operator_limit_selection' => FALSE,
      'operator_list' => [],
      'identifier' => $this->options['id'],
      'label' => $this->definition['title'],
      'description' => NULL,
      'remember' => FALSE,
      'multiple' => FALSE,
      'required' => FALSE,
    ];
  }

  /**
   * Provide default options for exposed filters.
   */
  protected function buildGroupOptions() {
    $this->options['group_info'] = [
      'label' => $this->definition['title'],
      'description' => NULL,
      'identifier' => $this->options['id'],
      'optional' => TRUE,
      'widget' => 'select',
      'multiple' => FALSE,
      'remember' => FALSE,
      'default_group' => 'All',
      'default_group_multiple' => [],
      'group_items' => [],
    ];
  }

  /**
   * Builds a group form.
   *
   * The form contains a group of operator or values to apply as a single
   * filter.
   */
  public function groupForm(&$form, FormStateInterface $form_state) {
    if (!empty($this->options['group_info']['optional']) && !$this->multipleExposedInput()) {
      $groups = ['All' => $this->t('- Any -')];
    }
    foreach ($this->options['group_info']['group_items'] as $id => $group) {
      if (!empty($group['title'])) {
        $groups[$id] = $group['title'];
      }
    }

    if (count($groups)) {
      $value = $this->options['group_info']['identifier'];

      $form[$value] = [
        '#title' => $this->options['group_info']['label'],
        '#type' => $this->options['group_info']['widget'],
        '#default_value' => $this->group_info,
        '#options' => $groups,
      ];
      if (!empty($this->options['group_info']['multiple'])) {
        if (count($groups) < 5) {
          $form[$value]['#type'] = 'checkboxes';
        }
        else {
          $form[$value]['#type'] = 'select';
          $form[$value]['#size'] = 5;
          $form[$value]['#multiple'] = TRUE;
        }
        unset($form[$value]['#default_value']);
        $user_input = $form_state->getUserInput();
        if (empty($user_input[$value])) {
          $user_input[$value] = $this->group_info;
          $form_state->setUserInput($user_input);
        }
      }

      $this->options['expose']['label'] = '';
    }
  }

  /**
   * Render our chunk of the exposed filter form when selecting.
   *
   * You can override this if it doesn't do what you expect.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    // Build the exposed form, when its based on an operator.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $this->options['expose']['operator_id'];
      $this->operatorForm($form, $form_state);

      // Limit the exposed operators if needed.
      if (!empty($this->options['expose']['operator_limit_selection']) &&
          !empty($this->options['expose']['operator_list'])) {

        $options = $this->operatorOptions();
        $operator_list = $this->options['expose']['operator_list'];
        $form['operator']['#options'] = array_intersect_key($options, $operator_list);
      }
      $form[$operator] = $form['operator'];

      $this->exposedTranslate($form[$operator], 'operator');

      unset($form['operator']);

      // When the operator and value forms are both in play, enclose them within
      // a wrapper.
      if (!empty($this->options['expose']['identifier'])) {
        $wrapper = $this->options['expose']['identifier'] . '_wrapper';
        $this->buildValueWrapper($form, $wrapper);
        $form[$operator]['#title_display'] = 'invisible';

        $form[$wrapper][$operator] = $form[$operator];
        unset($form[$operator]);
      }
    }

    // Build the form and set the value based on the identifier.
    if (!empty($this->options['expose']['identifier'])) {
      $value = $this->options['expose']['identifier'];
      $this->valueForm($form, $form_state);
      $form[$value] = $form['value'];

      if (isset($form[$value]['#title']) && !empty($form[$value]['#type']) && $form[$value]['#type'] != 'checkbox') {
        unset($form[$value]['#title']);
      }

      $this->exposedTranslate($form[$value], 'value');

      if ($value != 'value') {
        unset($form['value']);
      }

      // When the operator and value forms are both in play, enclose them within
      // a wrapper, for usability. Also wrap if the value form is comprised of
      // multiple elements.
      if ((!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) || count(Element::children($form[$value]))) {
        $wrapper = $value . '_wrapper';
        $this->buildValueWrapper($form, $wrapper);
        $form[$wrapper][$value] = $form[$value];
        unset($form[$value]);
      }
    }
  }

  /**
   * Builds wrapper for value and operator forms.
   *
   * @param array $form
   *   The form.
   * @param string $wrapper_identifier
   *   The key to use for the wrapper element.
   */
  protected function buildValueWrapper(&$form, $wrapper_identifier) {
    // If both the field and the operator are exposed, this will end up being
    // called twice. We don't want to wipe out what's already there, so if it
    // exists already, do nothing.
    if (!isset($form[$wrapper_identifier])) {
      $form[$wrapper_identifier] = [
        '#type' => 'fieldset',
      ];

      $exposed_info = $this->exposedInfo();
      if (!empty($exposed_info['label'])) {
        $form[$wrapper_identifier]['#title'] = $exposed_info['label'];
      }
      if (!empty($exposed_info['description'])) {
        $form[$wrapper_identifier]['#description'] = $exposed_info['description'];
      }
    }
  }

  /**
   * Build the form to let users create the group of exposed filters.
   *
   * This form is displayed when users click on button 'Build group'.
   *
   * @param array $form
   *   An alterable, associative array containing the structure of the form,
   *   passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildExposedFiltersGroupForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed']) || empty($this->options['is_grouped'])) {
      return;
    }
    $form['#theme'] = 'views_ui_build_group_filter_form';

    // #flatten will move everything from $form['group_info'][$key] to $form[$key]
    // prior to rendering. That's why the preRender for it needs to run first,
    // so that when the next preRender (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], [static::class, 'preRenderFlattenData']);
    $form['group_info']['#flatten'] = TRUE;

    if (!empty($this->options['group_info']['identifier'])) {
      $identifier = $this->options['group_info']['identifier'];
    }
    else {
      $identifier = 'group_' . $this->options['expose']['identifier'];
    }
    $form['group_info']['identifier'] = [
      '#type' => 'textfield',
      '#default_value' => $identifier,
      '#title' => $this->t('Filter identifier'),
      '#size' => 40,
      '#description' => $this->t('This will appear in the URL after the ? to identify this filter. Cannot be blank. Only letters, digits and the dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~") characters are allowed. @reserved_identifiers are reserved words and cannot be used.',
        ['@reserved_identifiers' => '"' . implode('", "', self::RESTRICTED_IDENTIFIERS) . '"']),
    ];
    $form['group_info']['label'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['group_info']['label'],
      '#title' => $this->t('Label'),
      '#size' => 40,
    ];
    $form['group_info']['description'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['group_info']['description'],
      '#title' => $this->t('Description'),
      '#size' => 60,
    ];
    $form['group_info']['optional'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Optional'),
      '#description' => $this->t('This exposed filter is optional and will have added options to allow it not to be set.'),
      '#default_value' => $this->options['group_info']['optional'],
    ];
    $form['group_info']['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple selections'),
      '#description' => $this->t('Enable to allow users to select multiple items.'),
      '#default_value' => $this->options['group_info']['multiple'],
    ];
    $form['group_info']['widget'] = [
      '#type' => 'radios',
      '#default_value' => $this->options['group_info']['widget'],
      '#title' => $this->t('Widget type'),
      '#options' => [
        'radios' => $this->t('Radios'),
        'select' => $this->t('Select'),
      ],
      '#description' => $this->t('Select which kind of widget will be used to render the group of filters'),
    ];
    $form['group_info']['remember'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember'),
      '#description' => $this->t('Remember the last setting the user gave this filter.'),
      '#default_value' => $this->options['group_info']['remember'],
    ];

    // The string '- Any -' will not be rendered.
    // @see theme_views_ui_build_group_filter_form()
    $groups = ['All' => $this->t('- Any -')];

    // Provide 3 options to start when we are in a new group.
    if (count($this->options['group_info']['group_items']) == 0) {
      $this->options['group_info']['group_items'] = array_fill(1, 3, []);
    }

    // After the general settings, comes a table with all the existent groups.
    $default_weight = 0;
    foreach ($this->options['group_info']['group_items'] as $item_id => $item) {
      if (!$form_state->isValueEmpty(['options', 'group_info', 'group_items', $item_id, 'remove'])) {
        continue;
      }
      // Each rows contains three widgets:
      // a) The title, where users define how they identify a pair of operator | value
      // b) The operator
      // c) The value (or values) to use in the filter with the selected operator

      // In each row, we have to display the operator form and the value from
      // $row acts as a fake form to render each widget in a row.
      $row = [];
      $groups[$item_id] = $this->t('Grouping @id', ['@id' => $item_id]);
      $this->operatorForm($row, $form_state);
      // Force the operator form to be a select box. Some handlers uses
      // radios and they occupy a lot of space in a table row.
      $row['operator']['#type'] = 'select';
      $row['operator']['#title'] = '';
      $this->valueForm($row, $form_state);

      // Fix the dependencies to update value forms when operators changes. This
      // is needed because forms are inside a new form and their IDs changes.
      // Dependencies are used when operator changes from to 'Between',
      // 'Not Between', etc, and two or more widgets are displayed.
      FormHelper::rewriteStatesSelector($row['value'], ':input[name="options[operator]"]', ':input[name="options[group_info][group_items][' . $item_id . '][operator]"]');

      // Set default values.
      $children = Element::children($row['value']);
      if (!empty($children)) {
        foreach ($children as $child) {
          if (!empty($row['value'][$child]['#states']['visible'])) {
            foreach ($row['value'][$child]['#states']['visible'] as $state) {
              if (isset($state[':input[name="options[group_info][group_items][' . $item_id . '][operator]"]'])) {
                $row['value'][$child]['#title'] = '';

                // Exit this loop and process the next child element.
                break;
              }
            }
          }

          if (isset($this->options['group_info']['group_items'][$item_id]['value'][$child])) {
            $row['value'][$child]['#default_value'] = $this->options['group_info']['group_items'][$item_id]['value'][$child];
          }
        }
      }
      else {
        if (isset($this->options['group_info']['group_items'][$item_id]['value']) && $this->options['group_info']['group_items'][$item_id]['value'] != '') {
          $row['value']['#default_value'] = $this->options['group_info']['group_items'][$item_id]['value'];
        }
      }

      if (!empty($this->options['group_info']['group_items'][$item_id]['operator'])) {
        $row['operator']['#default_value'] = $this->options['group_info']['group_items'][$item_id]['operator'];
      }

      $default_title = '';
      if (!empty($this->options['group_info']['group_items'][$item_id]['title'])) {
        $default_title = $this->options['group_info']['group_items'][$item_id]['title'];
      }

      // Per item group, we have a title that identifies it.
      $form['group_info']['group_items'][$item_id] = [
        'title' => [
          '#title' => $this->t('Label'),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#size' => 20,
          '#default_value' => $default_title,
        ],
        'operator' => $row['operator'],
        'value' => $row['value'],
        // No title is given here, since this input is never displayed. It is
        // only triggered by JavaScript.
        'remove' => [
          '#type' => 'checkbox',
          '#id' => 'views-removed-' . $item_id,
          '#attributes' => ['class' => ['views-remove-checkbox']],
          '#default_value' => 0,
        ],
        'weight' => [
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#type' => 'weight',
          '#delta' => count($this->options['group_info']['group_items']),
          '#default_value' => $default_weight++,
          '#attributes' => ['class' => ['weight']],
        ],
      ];
    }
    // From all groups, let chose which is the default.
    $form['group_info']['default_group'] = [
      '#type' => 'radios',
      '#options' => $groups,
      '#default_value' => $this->options['group_info']['default_group'],
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['default-radios'],
      ],
    ];
    // From all groups, let chose which is the default.
    $form['group_info']['default_group_multiple'] = [
      '#type' => 'checkboxes',
      '#options' => $groups,
      '#default_value' => $this->options['group_info']['default_group_multiple'],
      '#attributes' => [
        'class' => ['default-checkboxes'],
      ],
    ];

    $form['group_info']['add_group'] = [
      '#prefix' => '<div class="views-build-group clear-block">',
      '#suffix' => '</div>',
      '#type' => 'submit',
      '#value' => $this->t('Add another item'),
      '#submit' => [[$this, 'addGroupForm']],
    ];

    $js = [];
    $js['tableDrag']['views-filter-groups']['weight'][0] = [
      'target' => 'weight',
      'source' => NULL,
      'relationship' => 'sibling',
      'action' => 'order',
      'hidden' => TRUE,
      'limit' => 0,
    ];
    $js_settings = $form_state->get('js_settings');
    if ($js_settings && is_array($js)) {
      $js_settings = array_merge($js_settings, $js);
    }
    else {
      $js_settings = $js;
    }
    $form_state->set('js_settings', $js_settings);
  }

  /**
   * Add a new group to the exposed filter groups.
   */
  public function addGroupForm($form, FormStateInterface $form_state) {
    $item = &$this->options;

    // Add a new row.
    $item['group_info']['group_items'][] = [];

    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');
    $view->getExecutable()->setHandler($display_id, $type, $id, $item);

    $view->cacheSet();
    $form_state->set('rerender', TRUE);
    $form_state->setRebuild();
    $form_state->get('force_build_group_options', TRUE);
  }

  /**
   * Make some translations to a form item to make it more suitable to exposing.
   */
  protected function exposedTranslate(&$form, $type) {
    if (!isset($form['#type'])) {
      return;
    }

    if ($form['#type'] == 'radios') {
      $form['#type'] = 'select';
    }
    // Checkboxes don't work so well in exposed forms due to GET conversions.
    if ($form['#type'] == 'checkboxes') {
      if (empty($form['#no_convert']) || empty($this->options['expose']['multiple'])) {
        $form['#type'] = 'select';
      }
      if (!empty($this->options['expose']['multiple'])) {
        $form['#multiple'] = TRUE;
      }
    }
    if (empty($this->options['expose']['multiple']) && isset($form['#multiple'])) {
      unset($form['#multiple']);
      $form['#size'] = NULL;
    }

    // Cleanup in case the translated element's (radios or checkboxes) display value contains html.
    if ($form['#type'] == 'select') {
      $this->prepareFilterSelectOptions($form['#options']);
    }

    if ($type == 'value' && empty($this->always_required) && empty($this->options['expose']['required']) && $form['#type'] == 'select' && empty($form['#multiple'])) {
      $form['#options'] = ['All' => $this->t('- Any -')] + $form['#options'];
      $form['#default_value'] = 'All';
    }

    if (!empty($this->options['expose']['required'])) {
      $form['#required'] = TRUE;
    }
  }

  /**
   * Sanitizes the HTML select element's options.
   *
   * The function is recursive to support optgroups.
   */
  protected function prepareFilterSelectOptions(&$options) {
    foreach ($options as $value => $label) {
      // Recurse for optgroups.
      if (is_array($label)) {
        $this->prepareFilterSelectOptions($options[$value]);
      }
      // FAPI has some special value to allow hierarchy.
      // @see _form_options_flatten
      elseif (is_object($label) && isset($label->option)) {
        $this->prepareFilterSelectOptions($options[$value]->option);
      }
      else {
        // Cast the label to a string since it can be an object.
        // @see \Drupal\Core\StringTranslation\TranslatableMarkup
        $options[$value] = strip_tags(Html::decodeEntities((string) $label));
      }
    }
  }

  /**
   * Tell the renderer about our exposed form.
   *
   * This only needs to be overridden for particularly complex forms. And maybe
   * not even then.
   *
   * @return array|null
   *   For standard exposed filters. An array with the following keys:
   *   - operator: The $form key of the operator. Set to NULL if no operator.
   *   - value: The $form key of the value. Set to NULL if no value.
   *   - label: The label to use for this piece.
   *   For grouped exposed filters. An array with the following keys:
   *   - value: The $form key of the value. Set to NULL if no value.
   *   - label: The label to use for this piece.
   */
  public function exposedInfo() {
    if (empty($this->options['exposed'])) {
      return;
    }

    if ($this->isAGroup()) {
      return [
        'value' => $this->options['group_info']['identifier'],
        'label' => $this->options['group_info']['label'],
        'description' => $this->options['group_info']['description'],
      ];
    }

    return [
      'operator' => $this->options['expose']['operator_id'],
      'value' => $this->options['expose']['identifier'],
      'label' => $this->options['expose']['label'],
      'description' => $this->options['expose']['description'],
    ];
  }

  /**
   * Transform the input from a grouped filter into a standard filter.
   *
   * When a filter is a group, find the set of operator and values
   * that the chosen item represents, and inform views that a normal
   * filter was submitted by telling the operator and the value selected.
   *
   * The param $selected_group_id is only passed when the filter uses the
   * checkboxes widget, and this function will be called for each item
   * chosen in the checkboxes.
   */
  public function convertExposedInput(&$input, $selected_group_id = NULL) {
    if ($this->isAGroup()) {
      // If it is already defined the selected group, use it. Only valid
      // when the filter uses checkboxes for widget.
      if (!empty($selected_group_id)) {
        $selected_group = $selected_group_id;
      }
      else {
        $selected_group = $input[$this->options['group_info']['identifier']];
      }
      if ($selected_group == 'All' && !empty($this->options['group_info']['optional'])) {
        return NULL;
      }
      if ($selected_group != 'All' && empty($this->options['group_info']['group_items'][$selected_group])) {
        return FALSE;
      }
      if (isset($selected_group) && isset($this->options['group_info']['group_items'][$selected_group])) {
        $selected_group_options = $this->options['group_info']['group_items'][$selected_group];

        $operator_id = $this->options['expose']['operator'];
        $input[$operator_id] = $selected_group_options['operator'];
        $this->options['expose']['operator_id'] = $operator_id;
        $this->options['expose']['use_operator'] = TRUE;

        // Value can be optional, For example for 'empty' and 'not empty' filters.
        if (isset($selected_group_options['value']) && $selected_group_options['value'] !== '') {
          $input[$this->options['group_info']['identifier']] = $selected_group_options['value'];
        }

        $this->group_info = $input[$this->options['group_info']['identifier']];
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Group multiple exposed input.
   *
   * Returns the options available for a grouped filter that users checkboxes
   * as widget, and therefore has to be applied several times, one per
   * item selected.
   */
  public function groupMultipleExposedInput(&$input) {
    if (!empty($input[$this->options['group_info']['identifier']])) {
      return array_filter($input[$this->options['group_info']['identifier']]);
    }
    return [];
  }

  /**
   * Multiple exposed input.
   *
   * Returns TRUE if users can select multiple groups items of a
   * grouped exposed filter.
   */
  public function multipleExposedInput() {
    return $this->isAGroup() && !empty($this->options['group_info']['multiple']);
  }

  /**
   * If set to remember exposed input in the session, store it there.
   *
   * This function is similar to storeExposedInput but modified to
   * work properly when the filter is a group.
   */
  public function storeGroupInput($input, $status) {
    if (!$this->isAGroup() || empty($this->options['group_info']['identifier'])) {
      return TRUE;
    }

    if (empty($this->options['group_info']['remember'])) {
      return;
    }

    // Figure out which display id is responsible for the filters, so we
    // know where to look for session stored values.
    $display_id = ($this->view->display_handler->isDefaulted('filters')) ? 'default' : $this->view->current_display;

    // False means that we got a setting that means to recurse ourselves,
    // so we should erase whatever happened to be there.
    $session = $this->view->getRequest()->getSession();
    $views_session = $session->get('views', []);
    if ($status === FALSE && isset($views_session[$this->view->storage->id()][$display_id])) {
      unset($views_session[$this->view->storage->id()][$display_id][$this->options['group_info']['identifier']]);
    }

    if ($status !== FALSE) {
      if (!isset($views_session[$this->view->storage->id()][$display_id])) {
        $views_session[$this->view->storage->id()][$display_id] = [];
      }
      $views_session[$this->view->storage->id()][$display_id][$this->options['group_info']['identifier']] = $input[$this->options['group_info']['identifier']];
    }
    if (!empty($views_session)) {
      $session->set('views', $views_session);
    }
  }

  /**
   * Determines if the input from a filter should change the generated query.
   *
   * @param array $input
   *   The exposed data for this view.
   *
   * @return bool
   *   TRUE if the input for this filter should be included in the view query.
   *   FALSE otherwise.
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    if (!empty($this->options['expose']['identifier'])) {
      if ($this->options['is_grouped']) {
        $value = $input[$this->options['group_info']['identifier']];
      }
      else {
        $value = $input[$this->options['expose']['identifier']];
      }

      // Various ways to check for the absence of non-required input.
      if (empty($this->options['expose']['required'])) {
        if (($this->operator == 'empty' || $this->operator == 'not empty') && $value === '') {
          $value = ' ';
        }

        if ($this->operator != 'empty' && $this->operator != 'not empty') {
          if ($value == 'All' || $value === 0 || $value === []) {
            return FALSE;
          }

          // If checkboxes are used to render this filter, do not include the
          // filter if no options are checked.
          if (is_array($value) && Checkboxes::detectEmptyCheckboxes($value)) {
            return FALSE;
          }
        }

        if (!empty($this->alwaysMultiple) && $value === '') {
          return FALSE;
        }
      }
      if (isset($value)) {
        $this->value = $value;
        if (empty($this->alwaysMultiple) && empty($this->options['expose']['multiple']) && !is_array($value)) {
          $this->value = [$value];
        }
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

  public function storeExposedInput($input, $status) {
    if (empty($this->options['exposed']) || empty($this->options['expose']['identifier'])) {
      return TRUE;
    }

    if (empty($this->options['expose']['remember'])) {
      return;
    }

    // Check if we store exposed value for current user.
    $user = \Drupal::currentUser();
    $allowed_rids = empty($this->options['expose']['remember_roles']) ? [] : array_filter($this->options['expose']['remember_roles']);
    $intersect_rids = array_intersect(array_keys($allowed_rids), $user->getRoles());
    if (empty($intersect_rids)) {
      return;
    }

    // Figure out which display id is responsible for the filters, so we
    // know where to look for session stored values.
    $display_id = ($this->view->display_handler->isDefaulted('filters')) ? 'default' : $this->view->current_display;

    // Shortcut test.
    $operator = !empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']);

    // False means that we got a setting that means to recurse ourselves,
    // so we should erase whatever happened to be there.
    $session = $this->view->getRequest()->getSession();
    $views_session = $session->get('views', []);
    if (!$status && isset($views_session[$this->view->storage->id()][$display_id])) {
      $session_ref = &$views_session[$this->view->storage->id()][$display_id];
      if ($operator && isset($session_ref[$this->options['expose']['operator_id']])) {
        unset($session_ref[$this->options['expose']['operator_id']]);
      }

      if (isset($session_ref[$this->options['expose']['identifier']])) {
        unset($session_ref[$this->options['expose']['identifier']]);
      }
    }

    if ($status) {
      if (!isset($views_session[$this->view->storage->id()][$display_id])) {
        $views_session[$this->view->storage->id()][$display_id] = [];
      }

      $session_ref = &$views_session[$this->view->storage->id()][$display_id];

      if ($operator && isset($input[$this->options['expose']['operator_id']])) {
        $session_ref[$this->options['expose']['operator_id']] = $input[$this->options['expose']['operator_id']];
      }

      $session_ref[$this->options['expose']['identifier']] = $input[$this->options['expose']['identifier']];
    }
    if (!empty($views_session)) {
      $session->set('views', $views_session);
    }
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $this->value, $this->operator);
  }

  /**
   * Can this filter be used in OR groups?
   *
   * Some filters have complicated where clauses that cannot be easily used
   * with OR groups. Some filters must also use HAVING which also makes
   * them not groupable. These filters will end up in a special group
   * if OR grouping is in use.
   *
   * @return bool
   */
  public function canGroup() {
    return TRUE;
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
    $cache_contexts = [];
    // An exposed filter allows the user to change a view's filters. They accept
    // input from GET parameters, which are part of the URL. Hence a view with
    // an exposed filter is cacheable per URL.
    if ($this->isExposed()) {
      $cache_contexts[] = 'url';
    }
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->options['exposed']) && $error = $this->validateIdentifier($this->options['expose']['identifier'])) {
      return [$error];
    }
  }

  /**
   * Filter by no empty values, though allow the use of (string) "0".
   *
   * @param string $var
   *   The variable to evaluate.
   *
   * @return bool
   *   TRUE if the value is equal to an empty string, FALSE otherwise.
   */
  protected static function arrayFilterZero($var) {
    if (is_int($var)) {
      return $var != 0;
    }
    return trim($var) != '';
  }

}

/**
 * @}
 */
