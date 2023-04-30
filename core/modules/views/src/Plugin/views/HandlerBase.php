<?php

namespace Drupal\views\Plugin\views;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views\ViewsData;

/**
 * Base class for Views handler plugins.
 *
 * @ingroup views_plugins
 */
abstract class HandlerBase extends PluginBase implements ViewsHandlerInterface {

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\QueryPluginBase
   */
  public $query = NULL;

  /**
   * The table this handler is attached to.
   *
   * @var string
   */
  public $table;

  /**
   * The alias of the table of this handler which is used in the query.
   *
   * @var string
   */
  public $tableAlias;

  /**
   * The real field.
   *
   * The actual field in the database table, maybe different
   * on other kind of query plugins/special handlers.
   *
   * @var string
   */
  public $realField;

  /**
   * With field you can override the realField if the real field is not set.
   *
   * @var string
   */
  public $field;

  /**
   * The relationship used for this field.
   *
   * @var string
   */
  public $relationship = NULL;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * Tracks whether the plugin is a handler.
   */
  public bool $is_handler;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->is_handler = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Check to see if this handler type is defaulted. Note that
    // we have to do a lookup because the type is singular but the
    // option is stored as the plural.

    $this->unpackOptions($this->options, $options);

    // This exist on most handlers, but not all. So they are still optional.
    if (isset($options['table'])) {
      $this->table = $options['table'];
    }

    // Allow aliases on both fields and tables.
    if (isset($this->definition['real table'])) {
      $this->table = $this->definition['real table'];
    }

    if (isset($this->definition['real field'])) {
      $this->realField = $this->definition['real field'];
    }

    if (isset($this->definition['field'])) {
      $this->realField = $this->definition['field'];
    }

    if (isset($options['field'])) {
      $this->field = $options['field'];
      if (!isset($this->realField)) {
        $this->realField = $options['field'];
      }
    }

    $this->query = &$view->query;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['id'] = ['default' => ''];
    $options['table'] = ['default' => ''];
    $options['field'] = ['default' => ''];
    $options['relationship'] = ['default' => 'none'];
    $options['group_type'] = ['default' => 'group'];
    $options['admin_label'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    if (!empty($this->options['admin_label'])) {
      return $this->options['admin_label'];
    }
    $title = ($short && isset($this->definition['title short'])) ? $this->definition['title short'] : $this->definition['title'];
    return $this->t('@group: @title', ['@group' => $this->definition['group'], '@title' => $title]);
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field = NULL) {
    if (!isset($field)) {
      if (!empty($this->formula)) {
        $field = $this->getFormula();
      }
      else {
        $field = $this->tableAlias . '.' . $this->realField;
      }
    }

    // If grouping, check to see if the aggregation method needs to modify the field.
    if ($this->view->display_handler->useGroupBy()) {
      $this->view->initQuery();
      if ($this->query) {
        $info = $this->query->getAggregationInfo();
        if (!empty($info[$this->options['group_type']]['method'])) {
          $method = $info[$this->options['group_type']]['method'];
          if (method_exists($this->query, $method)) {
            return $this->query->$method($this->options['group_type'], $field);
          }
        }
      }
    }

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function sanitizeValue($value, $type = NULL) {
    if ($value === NULL) {
      return '';
    }
    switch ($type) {
      case 'xss':
        $value = Xss::filter($value);
        break;

      case 'xss_admin':
        $value = Xss::filterAdmin($value);
        break;

      case 'url':
        $value = Html::escape(UrlHelper::stripDangerousProtocols($value));
        break;

      default:
        $value = Html::escape($value);
        break;
    }
    return ViewsRenderPipelineMarkup::create($value);
  }

  /**
   * Transform a string by a certain method.
   *
   * @param $string
   *   The input you want to transform.
   * @param $option
   *   How do you want to transform it, possible values:
   *   - upper: Uppercase the string.
   *   - lower: lowercase the string.
   *   - ucfirst: Make the first char uppercase.
   *   - ucwords: Make each word in the string uppercase.
   *
   * @return string
   *   The transformed string.
   */
  protected function caseTransform($string, $option) {
    switch ($option) {
      default:
        return $string;
      case 'upper':
        return mb_strtoupper($string);

      case 'lower':
        return mb_strtolower($string);

      case 'ucfirst':
        return Unicode::ucfirst($string);

      case 'ucwords':
        return Unicode::ucwords($string);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the $form_state->getValues() hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = [static::class, 'preRenderAddFieldsetMarkup'];

    parent::buildOptionsForm($form, $form_state);

    $form['fieldsets'] = [
      '#type' => 'value',
      '#value' => ['more', 'admin_label'],
    ];

    $form['admin_label'] = [
      '#type' => 'details',
      '#title' => $this->t('Administrative title'),
      '#weight' => 150,
    ];
    $form['admin_label']['admin_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative title'),
      '#description' => $this->t('This title will be displayed on the views edit page instead of the default one. This might be useful if you have the same item twice.'),
      '#default_value' => $this->options['admin_label'],
      '#parents' => ['options', 'admin_label'],
    ];

    // This form is long and messy enough that the "Administrative title" option
    // belongs in "Administrative title" fieldset at the bottom of the form.
    $form['more'] = [
      '#type' => 'details',
      '#title' => $this->t('More'),
      '#weight' => 200,
      '#optional' => TRUE,
    ];

    // Allow to alter the default values brought into the form.
    // @todo Do we really want to keep this hook.
    $this->getModuleHandler()->alter('views_handler_options', $this->options, $this->view);
  }

  /**
   * Gets the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function getModuleHandler() {
    if (!$this->moduleHandler) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }

    return $this->moduleHandler;
  }

  /**
   * Sets the module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Provides the handler some groupby.
   */
  public function usesGroupBy() {
    return TRUE;
  }

  /**
   * Provide a form for aggregation settings.
   */
  public function buildGroupByForm(&$form, FormStateInterface $form_state) {
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');

    $form['#section'] = $display_id . '-' . $type . '-' . $id;

    $this->view->initQuery();
    $info = $this->view->query->getAggregationInfo();
    foreach ($info as $id => $aggregate) {
      $group_types[$id] = $aggregate['title'];
    }

    $form['group_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Aggregation type'),
      '#default_value' => $this->options['group_type'],
      '#description' => $this->t('Select the aggregation function to use on this field.'),
      '#options' => $group_types,
    ];
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  public function submitGroupByForm(&$form, FormStateInterface $form_state) {
    $form_state->get('handler')->options['group_type'] = $form_state->getValue(['options', 'group_type']);
  }

  /**
   * Determines if the handler has extra options.
   *
   * If a handler has 'extra options' it will get a little settings widget and
   * another form called extra_options.
   */
  public function hasExtraOptions() {
    return FALSE;
  }

  /**
   * Provide defaults for the handler.
   */
  public function defineExtraOptions(&$option) {}

  /**
   * Provide a form for setting options.
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * Validate the options form.
   */
  public function validateExtraOptionsForm($form, FormStateInterface $form_state) {}

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  public function submitExtraOptionsForm($form, FormStateInterface $form_state) {}

  /**
   * Determine if a handler can be exposed.
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Set new exposed option defaults when exposed setting is flipped on.
   */
  public function defaultExposeOptions() {}

  /**
   * Get information about the exposed form for the form renderer.
   */
  public function exposedInfo() {}

  /**
   * Render our chunk of the exposed handler form when selecting.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {}

  /**
   * Validate the exposed handler form.
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {}

  /**
   * Submit the exposed handler form.
   */
  public function submitExposed(&$form, FormStateInterface $form_state) {}

  /**
   * Form for exposed handler options.
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {}

  /**
   * Validate the options form.
   */
  public function validateExposeForm($form, FormStateInterface $form_state) {}

  /**
   * Perform any necessary changes to the form exposes prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  public function submitExposeForm($form, FormStateInterface $form_state) {}

  /**
   * Shortcut to display the expose/hide button.
   */
  public function showExposeButton(&$form, FormStateInterface $form_state) {}

  /**
   * Shortcut to display the exposed options form.
   */
  public function showExposeForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $this->buildExposeForm($form, $form_state);

    // When we click the expose button, we add new gadgets to the form but they
    // have no data in POST so their defaults get wiped out. This prevents
    // these defaults from getting wiped out. This setting will only be TRUE
    // during a 2nd pass rerender.
    if ($form_state->get('force_expose_options')) {
      foreach (Element::children($form['expose']) as $id) {
        if (isset($form['expose'][$id]['#default_value']) && !isset($form['expose'][$id]['#value'])) {
          $form['expose'][$id]['#value'] = $form['expose'][$id]['#default_value'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if (isset($this->definition['access callback']) && function_exists($this->definition['access callback'])) {
      if (isset($this->definition['access arguments']) && is_array($this->definition['access arguments'])) {
        return call_user_func_array($this->definition['access callback'], [$account] + $this->definition['access arguments']);
      }
      return $this->definition['access callback']($account);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery() {
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function postExecute(&$values) {}

  /**
   * Provides a unique placeholders for handlers.
   *
   * @return string
   *   A placeholder which contains the table and the fieldname.
   */
  protected function placeholder() {
    return $this->query->placeholder($this->table . '_' . $this->field);
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationship() {
    // Ensure this gets set to something.
    $this->relationship = NULL;

    // Don't process non-existent relationships.
    if (empty($this->options['relationship']) || $this->options['relationship'] == 'none') {
      return;
    }

    $relationship = $this->options['relationship'];

    // Ignore missing/broken relationships.
    if (empty($this->view->relationship[$relationship])) {
      return;
    }

    // Check to see if the relationship has already processed. If not, then we
    // cannot process it.
    if (empty($this->view->relationship[$relationship]->alias)) {
      return;
    }

    // Finally!
    $this->relationship = $this->view->relationship[$relationship]->alias;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureMyTable() {
    if (!isset($this->tableAlias)) {
      $this->tableAlias = $this->query->ensureTable($this->table, $this->relationship);
    }
    return $this->tableAlias;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * Determine if this item is 'exposed'.
   *
   * Exposed means it provides form elements to let users modify the view.
   *
   * @return bool
   */
  public function isExposed() {
    return !empty($this->options['exposed']);
  }

  /**
   * Returns TRUE if the exposed filter works like a grouped filter.
   */
  public function isAGroup() {
    return FALSE;
  }

  /**
   * Define if the exposed input has to be submitted multiple times.
   *
   * This is TRUE when exposed filters grouped are using checkboxes as
   * widgets.
   */
  public function multipleExposedInput() {
    return FALSE;
  }

  /**
   * Take input from exposed handlers and assign to this handler, if necessary.
   */
  public function acceptExposedInput($input) {
    return TRUE;
  }

  /**
   * If set to remember exposed input in the session, store it there.
   */
  public function storeExposedInput($input, $status) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getJoin() {
    // get the join from this table that links back to the base table.
    // Determine the primary table to seek
    if (empty($this->query->relationships[$this->relationship])) {
      $base_table = $this->view->storage->get('base_table');
    }
    else {
      $base_table = $this->query->relationships[$this->relationship]['base'];
    }

    $join = $this->getTableJoin($this->table, $base_table);
    if ($join) {
      return clone $join;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function broken() {
    return FALSE;
  }

  /**
   * Creates cross-database SQL date formatting.
   *
   * @param string $format
   *   A format string for the result, like 'Y-m-d H:i:s'.
   *
   * @return string
   *   An appropriate SQL string for the DB type and field type.
   */
  public function getDateFormat($format) {
    return $this->query->getDateFormat($this->getDateField(), $format);
  }

  /**
   * Creates cross-database SQL dates.
   *
   * @return string
   *   An appropriate SQL string for the db type and field type.
   */
  public function getDateField() {
    return $this->query->getDateField("$this->tableAlias.$this->realField");
  }

  /**
   * Gets views data service.
   *
   * @return \Drupal\views\ViewsData
   */
  protected function getViewsData() {
    if (!$this->viewsData) {
      $this->viewsData = Views::viewsData();
    }

    return $this->viewsData;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewsData(ViewsData $views_data) {
    $this->viewsData = $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getTableJoin($table, $base_table) {
    $data = Views::viewsData()->get($table);
    if (isset($data['table']['join'][$base_table])) {
      $join_info = $data['table']['join'][$base_table];
      if (!empty($join_info['join_id'])) {
        $id = $join_info['join_id'];
      }
      else {
        $id = 'standard';
      }

      $configuration = $join_info;
      // Fill in some easy defaults.
      if (empty($configuration['table'])) {
        $configuration['table'] = $table;
      }
      // If this is empty, it's a direct link.
      if (empty($configuration['left_table'])) {
        $configuration['left_table'] = $base_table;
      }

      if (isset($join_info['arguments'])) {
        foreach ($join_info['arguments'] as $key => $argument) {
          $configuration[$key] = $argument;
        }
      }

      $join = Views::pluginManager('join')->createInstance($id, $configuration);

      return $join;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    // If the user has configured a relationship on the handler take that into
    // account.
    if (!empty($this->options['relationship']) && $this->options['relationship'] != 'none') {
      $relationship = $this->displayHandler->getOption('relationships')[$this->options['relationship']];
      $table_data = $this->getViewsData()->get($relationship['table']);
      $views_data = $this->getViewsData()->get($table_data[$relationship['field']]['relationship']['base']);
    }
    else {
      $views_data = $this->getViewsData()->get($this->view->storage->get('base_table'));
    }

    if (isset($views_data['table']['entity type'])) {
      return $views_data['table']['entity type'];
    }
    else {
      throw new \Exception("No entity type for field {$this->options['id']} on view {$this->view->storage->id()}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function breakString($str, $force_int = FALSE) {
    $operator = NULL;
    $value = [];

    // Determine if the string has 'or' operators (plus signs) or 'and'
    // operators (commas) and split the string accordingly.
    if (preg_match('/^([\w0-9-_\.]+[+ ]+)+[\w0-9-_\.]+$/u', $str)) {
      // The '+' character in a query string may be parsed as ' '.
      $operator = 'or';
      $value = preg_split('/[+ ]/', $str);
    }
    elseif (preg_match('/^([\w0-9-_\.]+[, ]+)*[\w0-9-_\.]+$/u', $str)) {
      $operator = 'and';
      $value = explode(',', $str);
    }

    // Filter any empty matches (Like from '++' in a string) and reset the
    // array keys. 'strlen' is used as the filter callback so we do not lose
    // 0 values (would otherwise evaluate == FALSE).
    $value = array_values(array_filter($value, 'strlen'));

    if ($force_int) {
      $value = array_map('intval', $value);
    }

    return (object) ['value' => $value, 'operator' => $operator];
  }

  /**
   * Displays the Expose form.
   */
  public function displayExposedForm($form, FormStateInterface $form_state) {
    $item = &$this->options;
    // flip
    $item['exposed'] = empty($item['exposed']);

    // If necessary, set new defaults:
    if ($item['exposed']) {
      $this->defaultExposeOptions();
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
    $form_state->set('force_expose_options', TRUE);
  }

  /**
   * Submits a temporary form.
   *
   * A submit handler that is used for storing temporary items when using
   * multi-step changes, such as ajax requests.
   */
  public function submitTemporaryForm($form, FormStateInterface $form_state) {
    // Run it through the handler's submit function.
    $this->submitOptionsForm($form['options'], $form_state);
    $item = $this->options;
    $types = ViewExecutable::getHandlerTypes();

    // For footer/header $handler_type is area but $type is footer/header.
    // For all other handle types it's the same.
    $handler_type = $type = $form_state->get('type');
    if (!empty($types[$type]['type'])) {
      $handler_type = $types[$type]['type'];
    }

    $override = NULL;
    $view = $form_state->get('view');
    $executable = $view->getExecutable();
    if ($executable->display_handler->useGroupBy() && !empty($item['group_type'])) {
      if (empty($executable->query)) {
        $executable->initQuery();
      }
      $aggregate = $executable->query->getAggregationInfo();
      if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
        $override = $aggregate[$item['group_type']]['handler'][$type];
      }
    }

    // Create a new handler and unpack the options from the form onto it. We
    // can use that for storage.
    $handler = Views::handlerManager($handler_type)->getHandler($item, $override);
    $handler->init($executable, $executable->display_handler, $item);

    // Add the incoming options to existing options because items using
    // the extra form may not have everything in the form here.
    $options = $form_state->getValue('options') + $this->options;

    // This unpacks only options that are in the definition, ensuring random
    // extra stuff on the form is not sent through.
    $handler->unpackOptions($handler->options, $options, NULL, FALSE);

    // Store the item back on the view.
    $executable = $view->getExecutable();
    $executable->temporary_options[$type][$form_state->get('id')] = $handler->options;

    // @todo Decide if \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm() is
    //   perhaps the better place to fix the issue.
    // \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm() drops the current
    // form from the stack, even if it's an #ajax. So add the item back to the top
    // of the stack.
    $view->addFormToStack($form_state->get('form_key'), $form_state->get('display_id'), $type, $item['id'], TRUE);

    $form_state->get('rerender', TRUE);
    $form_state->setRebuild();
    // Write to cache
    $view->cacheSet();
  }

  /**
   * Calculates options stored on the handler.
   *
   * @param array $options
   *   The options stored in the handler
   * @param array $form_state_options
   *   The newly submitted form state options.
   *
   * @return array
   *   The new options
   */
  public function submitFormCalculateOptions(array $options, array $form_state_options) {
    return $form_state_options + $options;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($this->table) {
      // Ensure that the view depends on the module that provides the table.
      $data = $this->getViewsData()->get($this->table);
      if (isset($data['table']['provider'])) {
        $dependencies['module'][] = $data['table']['provider'];
      }
    }
    return $dependencies;
  }

}
