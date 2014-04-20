<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\HandlerBase.
 */

namespace Drupal\views\Plugin\views;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Database\Database;
use Drupal\views\Views;
use Drupal\views\ViewsData;

abstract class HandlerBase extends PluginBase {

  /**
   * Where the $query object will reside:
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
   * When a table has been moved this property is set.
   *
   * @var string
   */
  public $actualTable;

  /**
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
   * When a field has been moved this property is set.
   *
   * @var string
   */
  public $actualField;

  /**
   * The relationship used for this field.
   *
   * @var string
   */
  public $relationship = NULL;

  /**
   * Whether or not this handler is optional.
   *
   * @var bool
   */
  protected $optional = FALSE;

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
   * Constructs a Handler object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->is_handler = TRUE;
    $this->optional = !empty($configuration['optional']);
  }

  /**
   * Overrides \Drupal\views\Plugin\views\PluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Check to see if this handler type is defaulted. Note that
    // we have to do a lookup because the type is singular but the
    // option is stored as the plural.

    // If the 'moved to' keyword moved our handler, let's fix that now.
    if (isset($this->actualTable)) {
      $options['table'] = $this->actualTable;
    }

    if (isset($this->actualField)) {
      $options['field'] = $this->actualField;
    }

    $this->unpackOptions($this->options, $options);

    // This exist on most handlers, but not all. So they are still optional.
    if (isset($options['table'])) {
      $this->table = $options['table'];
    }

    // Allow alliases on both fields and tables.
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

    $options['id'] = array('default' => '');
    $options['table'] = array('default' => '');
    $options['field'] = array('default' => '');
    $options['relationship'] = array('default' => 'none');
    $options['group_type'] = array('default' => 'group');
    $options['admin_label'] = array('default' => '', 'translatable' => TRUE);
    $options['dependencies'] = array('default' => array());

    return $options;
  }

  /**
   * Returns whether this handler is optional.
   *
   * @return bool
   */
  public function isOptional() {
    return $this->optional;
  }

  /**
   * Return a string representing this handler's name in the UI.
   */
  public function adminLabel($short = FALSE) {
    if (!empty($this->options['admin_label'])) {
      $title = String::checkPlain($this->options['admin_label']);
      return $title;
    }
    $title = ($short && isset($this->definition['title short'])) ? $this->definition['title short'] : $this->definition['title'];
    return $this->t('!group: !title', array('!group' => $this->definition['group'], '!title' => $title));
  }

  /**
   * Shortcut to get a handler's raw field value.
   *
   * This should be overridden for handlers with formulae or other
   * non-standard fields. Because this takes an argument, fields
   * overriding this can just call return parent::getField($formula)
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
   * Sanitize the value for output.
   *
   * @param $value
   *   The value being rendered.
   * @param $type
   *   The type of sanitization needed. If not provided, String::checkPlain() is used.
   *
   * @return string
   *   Returns the safe value.
   */
  public function sanitizeValue($value, $type = NULL) {
    switch ($type) {
      case 'xss':
        $value = Xss::filter($value);
        break;
      case 'xss_admin':
        $value = Xss::filterAdmin($value);
        break;
      case 'url':
        $value = String::checkPlain(UrlHelper::stripDangerousProtocols($value));
        break;
      default:
        $value = String::checkPlain($value);
        break;
    }
    return $value;
  }

  /**
   * Transform a string by a certain method.
   *
   * @param $string
   *    The input you want to transform.
   * @param $option
   *    How do you want to transform it, possible values:
   *      - upper: Uppercase the string.
   *      - lower: lowercase the string.
   *      - ucfirst: Make the first char uppercase.
   *      - ucwords: Make each word in the string uppercase.
   *
   * @return string
   *    The transformed string.
   */
  protected function caseTransform($string, $option) {
    switch ($option) {
      default:
        return $string;
      case 'upper':
        return Unicode::strtoupper($string);
      case 'lower':
        return Unicode::strtolower($string);
      case 'ucfirst':
        return Unicode::ucfirst($string);
      case 'ucwords':
        return Unicode::ucwords($string);
    }
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the form_state['values'] hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = array(get_class($this), 'preRenderAddFieldsetMarkup');

    parent::buildOptionsForm($form, $form_state);

    $form['fieldsets'] = array(
      '#type' => 'value',
      '#value' => array('more', 'admin_label'),
    );

    $form['admin_label'] = array(
      '#type' => 'details',
      '#title' => t('Administrative title'),
      '#weight' => 150,
    );
    $form['admin_label']['admin_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Administrative title'),
      '#description' => $this->t('This title will be displayed on the views edit page instead of the default one. This might be useful if you have the same item twice.'),
      '#default_value' => $this->options['admin_label'],
      '#parents' => array('options', 'admin_label'),
    );

    // This form is long and messy enough that the "Administrative title" option
    // belongs in "Administrative title" fieldset at the bottom of the form.
    $form['more'] = array(
      '#type' => 'details',
      '#title' => t('More'),
      '#weight' => 200,
    );
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
  public function buildGroupByForm(&$form, &$form_state) {
    $display_id = $form_state['display_id'];
    $type = $form_state['type'];
    $id = $form_state['id'];

    $form['#section'] = $display_id . '-' . $type . '-' . $id;

    $this->view->initQuery();
    $info = $this->view->query->getAggregationInfo();
    foreach ($info as $id => $aggregate) {
      $group_types[$id] = $aggregate['title'];
    }

    $form['group_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Aggregation type'),
      '#default_value' => $this->options['group_type'],
      '#description' => $this->t('Select the aggregation function to use on this field.'),
      '#options' => $group_types,
    );
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitGroupByForm(&$form, &$form_state) {
    $form_state['handler']->options['group_type'] = $form_state['values']['options']['group_type'];
  }

  /**
   * If a handler has 'extra options' it will get a little settings widget and
   * another form called extra_options.
   */
  public function hasExtraOptions() { return FALSE; }

  /**
   * Provide defaults for the handler.
   */
  public function defineExtraOptions(&$option) { }

  /**
   * Provide a form for setting options.
   */
  public function buildExtraOptionsForm(&$form, &$form_state) { }

  /**
   * Validate the options form.
   */
  public function validateExtraOptionsForm($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitExtraOptionsForm($form, &$form_state) { }

  /**
   * Determine if a handler can be exposed.
   */
  public function canExpose() { return FALSE; }

  /**
   * Set new exposed option defaults when exposed setting is flipped
   * on.
   */
  public function defaultExposeOptions() { }

  /**
   * Get information about the exposed form for the form renderer.
   */
  public function exposedInfo() { }

  /**
   * Render our chunk of the exposed handler form when selecting
   */
  public function buildExposedForm(&$form, &$form_state) { }

  /**
   * Validate the exposed handler form
   */
  public function validateExposed(&$form, &$form_state) { }

  /**
   * Submit the exposed handler form
   */
  public function submitExposed(&$form, &$form_state) { }

  /**
   * Form for exposed handler options.
   */
  public function buildExposeForm(&$form, &$form_state) { }

  /**
   * Validate the options form.
   */
  public function validateExposeForm($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form exposes prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitExposeForm($form, &$form_state) { }

  /**
   * Shortcut to display the expose/hide button.
   */
  public function showExposeButton(&$form, &$form_state) { }

  /**
   * Shortcut to display the exposed options form.
   */
  public function showExposeForm(&$form, &$form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $this->buildExposeForm($form, $form_state);

    // When we click the expose button, we add new gadgets to the form but they
    // have no data in POST so their defaults get wiped out. This prevents
    // these defaults from getting wiped out. This setting will only be TRUE
    // during a 2nd pass rerender.
    if (!empty($form_state['force_expose_options'])) {
      foreach (Element::children($form['expose']) as $id) {
        if (isset($form['expose'][$id]['#default_value']) && !isset($form['expose'][$id]['#value'])) {
          $form['expose'][$id]['#value'] = $form['expose'][$id]['#default_value'];
        }
      }
    }
  }

  /**
   * Check whether current user has access to this handler.
   *
   * @param AccountInterface $account
   * @return boolean
   */
  public function access(AccountInterface $account) {
    if (isset($this->definition['access callback']) && function_exists($this->definition['access callback'])) {
      if (isset($this->definition['access arguments']) && is_array($this->definition['access arguments'])) {
        return call_user_func_array($this->definition['access callback'], array($account) + $this->definition['access arguments']);
      }
      return $this->definition['access callback']($account);
    }

    return TRUE;
  }

  /**
   * Run before the view is built.
   *
   * This gives all the handlers some time to set up before any handler has
   * been fully run.
   */
  public function preQuery() {
  }

  /**
   * Don't run a query by default.
   */
  public function query() {
  }

  /**
   * Run after the view is executed, before the result is cached.
   *
   * This gives all the handlers some time to modify values. This is primarily
   * used so that handlers that pull up secondary data can put it in the
   * $values so that the raw data can be utilized externally.
   */
  public function postExecute(&$values) { }

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
   * Called just prior to query(), this lets a handler set up any relationship
   * it needs.
   */
  public function setRelationship() {
    // Ensure this gets set to something.
    $this->relationship = NULL;

    // Don't process non-existant relationships.
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
   * Ensure the main table for this handler is in the query. This is used
   * a lot.
   */
  public function ensureMyTable() {
    if (!isset($this->tableAlias)) {
      $this->tableAlias = $this->query->ensureTable($this->table, $this->relationship);
    }
    return $this->tableAlias;
  }

  /**
   * Provide text for the administrative summary.
   */
  public function adminSummary() { }

  /**
   * Determine if this item is 'exposed', meaning it provides form elements
   * to let users modify the view.
   *
   * @return TRUE/FALSE
   */
  public function isExposed() {
    return !empty($this->options['exposed']);
  }

  /**
   * Returns TRUE if the exposed filter works like a grouped filter.
   */
  public function isAGroup() { return FALSE; }

  /**
   * Define if the exposed input has to be submitted multiple times.
   * This is TRUE when exposed filters grouped are using checkboxes as
   * widgets.
   */
  public function multipleExposedInput() { return FALSE; }

  /**
   * Take input from exposed handlers and assign to this handler, if necessary.
   */
  public function acceptExposedInput($input) { return TRUE; }

  /**
   * If set to remember exposed input in the session, store it there.
   */
  public function storeExposedInput($input, $status) { return TRUE; }

  /**
   * Get the join object that should be used for this handler.
   *
   * This method isn't used a great deal, but it's very handy for easily
   * getting the join if it is necessary to make some changes to it, such
   * as adding an 'extra'.
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
   * Validates the handler against the complete View.
   *
   * This is called when the complete View is being validated. For validating
   * the handler options form use validateOptionsForm().
   *
   * @see views_handler::validateOptionsForm()
   *
   * @return
   *   Empty array if the handler is valid; an array of error strings if it is not.
   */
  public function validate() { return array(); }

  /**
   * Determines if the handler is considered 'broken', meaning it's a
   * a placeholder used when a handler can't be found.
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
   * Sets the views data service.
   *
   * @param \Drupal\views\ViewsData $views_data
   *   The views data.
   */
  public function setViewsData(ViewsData $views_data) {
    $this->viewsData = $views_data;
  }

  /**
   * Fetches a handler to join one table to a primary table from the data cache.
   *
   * @param string $table
   *   The table to join from.
   * @param string $base_table
   *   The table to join to.
   *
   * @return \Drupal\views\Plugin\views\join\JoinPluginBase
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
   * Determines the entity type used by this handler.
   *
   * If this handler uses a relationship, the base class of the relationship is
   * taken into account.
   *
   * @return string
   *   The machine name of the entity type.
   */
  public function getEntityType() {
    // If the user has configured a relationship on the handler take that into
    // account.
    if (!empty($this->options['relationship']) && $this->options['relationship'] != 'none') {
      $views_data = $this->getViewsData()->get($this->view->relationship->table);
    }
    else {
      $views_data = $this->getViewsData()->get($this->view->storage->get('base_table'));
    }

    if (isset($views_data['table']['entity type'])) {
      return $views_data['table']['entity type'];
    }
    else {
      throw new \Exception(String::format('No entity type for field @field on view @view', array('@field' => $this->options['id'], '@view' => $this->view->storage->id())));
    }
  }

  /**
   * Breaks x,y,z and x+y+z into an array. Numeric only.
   *
   * @param string $str
   *   The string to parse.
   * @param \Drupal\views\Plugin\views\HandlerBase|null $handler
   *   The handler object to use as a base. If not specified one will
   *   be created.
   *
   * @return \Drupal\views\Plugin\views\HandlerBase|stdClass $handler
   *   The new handler object.
   */
  public static function breakPhrase($str, &$handler = NULL) {
    if (!$handler) {
      $handler = new \stdClass();
    }

    // Set up defaults:

    if (!isset($handler->value)) {
      $handler->value = array();
    }

    if (!isset($handler->operator)) {
      $handler->operator = 'or';
    }

    if (empty($str)) {
      return $handler;
    }

    if (preg_match('/^([0-9]+[+ ])+[0-9]+$/', $str)) {
      // The '+' character in a query string may be parsed as ' '.
      $handler->operator = 'or';
      $handler->value = preg_split('/[+ ]/', $str);
    }
    elseif (preg_match('/^([0-9]+,)*[0-9]+$/', $str)) {
      $handler->operator = 'and';
      $handler->value = explode(',', $str);
    }

    // Keep an 'error' value if invalid strings were given.
    if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
      $handler->value = array(-1);
      return $handler;
    }

    // Doubly ensure that all values are numeric only.
    foreach ($handler->value as $id => $value) {
      $handler->value[$id] = intval($value);
    }

    return $handler;
  }

  /**
   * Breaks x,y,z and x+y+z into an array. Works for strings.
   *
   * @param string $str
   *   The string to parse.
   * @param \Drupal\views\Plugin\views\HandlerBase|null $handler
   *   The object to use as a base. If not specified one will
   *   be created.
   *
   * @return \Drupal\views\Plugin\views\HandlerBase|stdClass $handler
   *   The new handler object.
   */
  public static function breakPhraseString($str, &$handler = NULL) {
    if (!$handler) {
      $handler = new \stdClass();
    }

    // Set up defaults:
    if (!isset($handler->value)) {
      $handler->value = array();
    }

    if (!isset($handler->operator)) {
      $handler->operator = 'or';
    }

    if ($str == '') {
      return $handler;
    }

    // Determine if the string has 'or' operators (plus signs) or 'and' operators
    // (commas) and split the string accordingly. If we have an 'and' operator,
    // spaces are treated as part of the word being split, but otherwise they are
    // treated the same as a plus sign.
    $or_wildcard = '[^\s+,]';
    $and_wildcard = '[^+,]';
    if (preg_match("/^({$or_wildcard}+[+ ])+{$or_wildcard}+$/", $str)) {
      $handler->operator = 'or';
      $handler->value = preg_split('/[+ ]/', $str);
    }
    elseif (preg_match("/^({$and_wildcard}+,)*{$and_wildcard}+$/", $str)) {
      $handler->operator = 'and';
      $handler->value = explode(',', $str);
    }

    // Keep an 'error' value if invalid strings were given.
    if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
      $handler->value = array(-1);
      return $handler;
    }

    // Doubly ensure that all values are strings only.
    foreach ($handler->value as $id => $value) {
      $handler->value[$id] = (string) $value;
    }

    return $handler;
  }

  /**
   * Displays the Expose form.
   */
  public function displayExposedForm($form, &$form_state) {
    $item = &$this->options;
    // flip
    $item['exposed'] = empty($item['exposed']);

    // If necessary, set new defaults:
    if ($item['exposed']) {
      $this->defaultExposeOptions();
    }

    $form_state['view']->getExecutable()->setHandler($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

    $form_state['view']->addFormToStack($form_state['form_key'], $form_state['display_id'], $form_state['type'], $form_state['id'], TRUE, TRUE);

    $form_state['view']->cacheSet();
    $form_state['rerender'] = TRUE;
    $form_state['rebuild'] = TRUE;
    $form_state['force_expose_options'] = TRUE;
  }

  /**
   * A submit handler that is used for storing temporary items when using
   * multi-step changes, such as ajax requests.
   */
  public function submitTemporaryForm($form, &$form_state) {
    // Run it through the handler's submit function.
    $this->submitOptionsForm($form['options'], $form_state);
    $item = $this->options;
    $types = ViewExecutable::getHandlerTypes();

    // For footer/header $handler_type is area but $type is footer/header.
    // For all other handle types it's the same.
    $handler_type = $type = $form_state['type'];
    if (!empty($types[$type]['type'])) {
      $handler_type = $types[$type]['type'];
    }

    $override = NULL;
    $executable = $form_state['view']->getExecutable();
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
    $options = $form_state['values']['options'] + $this->options;

    // This unpacks only options that are in the definition, ensuring random
    // extra stuff on the form is not sent through.
    $handler->unpackOptions($handler->options, $options, NULL, FALSE);

    // Store the item back on the view.
    $executable = $form_state['view']->getExecutable();
    $executable->temporary_options[$type][$form_state['id']] = $handler->options;

    // @todo Decide if \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm() is
    //   perhaps the better place to fix the issue.
    // \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm() drops the current
    // form from the stack, even if it's an #ajax. So add the item back to the top
    // of the stack.
    $form_state['view']->addFormToStack($form_state['form_key'], $form_state['display_id'], $type, $item['id'], TRUE);

    $form_state['rerender'] = TRUE;
    $form_state['rebuild'] = TRUE;
    // Write to cache
    $form_state['view']->cacheSet();
  }

}
