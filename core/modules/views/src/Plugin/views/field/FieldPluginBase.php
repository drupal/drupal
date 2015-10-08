<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\FieldPluginBase.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @defgroup views_field_handlers Views field handler plugins
 * @{
 * Handler plugins for Views fields.
 *
 * Field handlers handle both querying and display of fields in views.
 *
 * Field handler plugins extend
 * \Drupal\views\Plugin\views\field\FieldPluginBase. They must be
 * annotated with \Drupal\views\Annotation\ViewsField annotation, and they
 * must be in namespace directory Plugin\views\field.
 *
 * The following items can go into a hook_views_data() implementation in a
 * field section to affect how the field handler will behave:
 * - additional fields: An array of fields that should be added to the query.
 *   The array is in one of these forms:
 *   @code
 *   // Simple form, for fields within the same table.
 *   array('identifier' => fieldname)
 *   // Form for fields in a different table.
 *   array('identifier' => array('table' => tablename, 'field' => fieldname))
 *   @endcode
 *   As many fields as are necessary may be in this array.
 * - click sortable: If TRUE (default), this field may be click sorted.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base class for views fields.
 *
 * @ingroup views_field_handlers
 */
abstract class FieldPluginBase extends HandlerBase implements FieldHandlerInterface {

  /**
   * Indicator of the renderText() method for rendering a single item.
   * (If no render_item() is present).
   */
  const RENDER_TEXT_PHASE_SINGLE_ITEM = 0;

  /**
   * Indicator of the renderText() method for rendering the whole element.
   * (if no render_item() method is available).
   */
  const RENDER_TEXT_PHASE_COMPLETELY = 1;

  /**
   * Indicator of the renderText() method for rendering the empty text.
   */
  const RENDER_TEXT_PHASE_EMPTY = 2;

  var $field_alias = 'unknown';
  var $aliases = array();

  /**
   * The field value prior to any rewriting.
   *
   * @var mixed
   */
  public $original_value = NULL;

  /**
   * @var array
   * Stores additional fields which get's added to the query.
   * The generated aliases are stored in $aliases.
   */
  var $additional_fields = array();

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Stores the render API renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields = array();
    if (!empty($this->definition['additional fields'])) {
      $this->additional_fields = $this->definition['additional fields'];
    }

    if (!isset($this->options['exclude'])) {
      $this->options['exclude'] = '';
    }
  }

  /**
   * Determine if this field can allow advanced rendering.
   *
   * Fields can set this to FALSE if they do not wish to allow
   * token based rewriting or link-making.
   */
  protected function allowAdvancedRender() {
    return TRUE;
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $this->field_alias = $this->query->addField($this->tableAlias, $this->realField, NULL, $params);

    $this->addAdditionalFields();
  }

  /**
   * Add 'additional' fields to the query.
   *
   * @param $fields
   * An array of fields. The key is an identifier used to later find the
   * field alias used. The value is either a string in which case it's
   * assumed to be a field on this handler's table; or it's an array in the
   * form of
   * @code array('table' => $tablename, 'field' => $fieldname) @endcode
   */
  protected function addAdditionalFields($fields = NULL) {
    if (!isset($fields)) {
      // notice check
      if (empty($this->additional_fields)) {
        return;
      }
      $fields = $this->additional_fields;
    }

    $group_params = array();
    if ($this->options['group_type'] != 'group') {
      $group_params = array(
        'function' => $this->options['group_type'],
      );
    }

    if (!empty($fields) && is_array($fields)) {
      foreach ($fields as $identifier => $info) {
        if (is_array($info)) {
          if (isset($info['table'])) {
            $table_alias = $this->query->ensureTable($info['table'], $this->relationship);
          }
          else {
            $table_alias = $this->tableAlias;
          }

          if (empty($table_alias)) {
            debug(t('Handler @handler tried to add additional_field @identifier but @table could not be added!', array('@handler' => $this->definition['id'], '@identifier' => $identifier, '@table' => $info['table'])));
            $this->aliases[$identifier] = 'broken';
            continue;
          }

          $params = array();
          if (!empty($info['params'])) {
            $params = $info['params'];
          }

          $params += $group_params;
          $this->aliases[$identifier] = $this->query->addField($table_alias, $info['field'], NULL, $params);
        }
        else {
          $this->aliases[$info] = $this->query->addField($this->tableAlias, $info, NULL, $group_params);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
      $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return isset($this->definition['click sortable']) ? $this->definition['click sortable'] : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!isset($this->options['label'])) {
      return '';
    }
    return $this->options['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function elementType($none_supported = FALSE, $default_empty = FALSE, $inline = FALSE) {
    if ($none_supported) {
      if ($this->options['element_type'] === '0') {
        return '';
      }
    }
    if ($this->options['element_type']) {
      return $this->options['element_type'];
    }

    if ($default_empty) {
      return '';
    }

    if ($inline) {
      return 'span';
    }

    if (isset($this->definition['element type'])) {
      return $this->definition['element type'];
    }

    return 'span';
  }

  /**
   * {@inheritdoc}
   */
  public function elementLabelType($none_supported = FALSE, $default_empty = FALSE) {
    if ($none_supported) {
      if ($this->options['element_label_type'] === '0') {
        return '';
      }
    }
    if ($this->options['element_label_type']) {
      return $this->options['element_label_type'];
    }

    if ($default_empty) {
      return '';
    }

    return 'span';
  }

  /**
   * {@inheritdoc}
   */
  public function elementWrapperType($none_supported = FALSE, $default_empty = FALSE) {
    if ($none_supported) {
      if ($this->options['element_wrapper_type'] === '0') {
        return 0;
      }
    }
    if ($this->options['element_wrapper_type']) {
      return $this->options['element_wrapper_type'];
    }

    if ($default_empty) {
      return '';
    }

    return 'div';
  }

  /**
   * {@inheritdoc}
   */
  public function getElements() {
    static $elements = NULL;
    if (!isset($elements)) {
      // @todo Add possible html5 elements.
      $elements = array(
        '' => $this->t(' - Use default -'),
        '0' => $this->t('- None -')
      );
      $elements += \Drupal::config('views.settings')->get('field_rewrite_elements');
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function elementClasses($row_index = NULL) {
    $classes = explode(' ', $this->options['element_class']);
    foreach ($classes as &$class) {
      $class = $this->tokenizeValue($class, $row_index);
      $class = Html::cleanCssIdentifier($class);
    }
    return implode(' ', $classes);
  }

  /**
   * {@inheritdoc}
   */
  public function tokenizeValue($value, $row_index = NULL) {
    if (strpos($value, '{{') !== FALSE) {
      $fake_item = array(
        'alter_text' => TRUE,
        'text' => $value,
      );

      // Use isset() because empty() will trigger on 0 and 0 is
      // the first row.
      if (isset($row_index) && isset($this->view->style_plugin->render_tokens[$row_index])) {
        $tokens = $this->view->style_plugin->render_tokens[$row_index];
      }
      else {
        // Get tokens from the last field.
        $last_field = end($this->view->field);
        if (isset($last_field->last_tokens)) {
          $tokens = $last_field->last_tokens;
        }
        else {
          $tokens = $last_field->getRenderTokens($fake_item);
        }
      }

      $value = strip_tags($this->renderAltered($fake_item, $tokens));
      if (!empty($this->options['alter']['trim_whitespace'])) {
        $value = trim($value);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function elementLabelClasses($row_index = NULL) {
    $classes = explode(' ', $this->options['element_label_class']);
    foreach ($classes as &$class) {
      $class = $this->tokenizeValue($class, $row_index);
      $class = Html::cleanCssIdentifier($class);
    }
    return implode(' ', $classes);
  }

  /**
   * {@inheritdoc}
   */
  public function elementWrapperClasses($row_index = NULL) {
    $classes = explode(' ', $this->options['element_wrapper_class']);
    foreach ($classes as &$class) {
      $class = $this->tokenizeValue($class, $row_index);
      $class = Html::cleanCssIdentifier($class);
    }
    return implode(' ', $classes);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $values) {
    $relationship_id = $this->options['relationship'];
    if ($relationship_id == 'none') {
      return $values->_entity;
    }
    elseif (isset($values->_relationship_entities[$relationship_id])) {
      return $values->_relationship_entities[$relationship_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $alias = isset($field) ? $this->aliases[$field] : $this->field_alias;
    if (isset($values->{$alias})) {
      return $values->{$alias};
    }
  }

  /**
   * {@inheritdoc}
   */
  public function useStringGroupBy() {
    return TRUE;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label'] = array('default' => '');
    // Some styles (for example table) should have labels enabled by default.
    $style = $this->view->getStyle();
    if (isset($style) && $style->defaultFieldLabels()) {
      $options['label']['default'] = $this->definition['title'];
    }

    $options['exclude'] = array('default' => FALSE);
    $options['alter'] = array(
      'contains' => array(
        'alter_text' => array('default' => FALSE),
        'text' => array('default' => ''),
        'make_link' => array('default' => FALSE),
        'path' => array('default' => ''),
        'absolute' => array('default' => FALSE),
        'external' => array('default' => FALSE),
        'replace_spaces' => array('default' => FALSE),
        'path_case' => array('default' => 'none'),
        'trim_whitespace' => array('default' => FALSE),
        'alt' => array('default' => ''),
        'rel' => array('default' => ''),
        'link_class' => array('default' => ''),
        'prefix' => array('default' => ''),
        'suffix' => array('default' => ''),
        'target' => array('default' => ''),
        'nl2br' => array('default' => FALSE),
        'max_length' => array('default' => 0),
        'word_boundary' => array('default' => TRUE),
        'ellipsis' => array('default' => TRUE),
        'more_link' => array('default' => FALSE),
        'more_link_text' => array('default' => ''),
        'more_link_path' => array('default' => ''),
        'strip_tags' => array('default' => FALSE),
        'trim' => array('default' => FALSE),
        'preserve_tags' => array('default' => ''),
        'html' => array('default' => FALSE),
      ),
    );
    $options['element_type'] = array('default' => '');
    $options['element_class'] = array('default' => '');

    $options['element_label_type'] = array('default' => '');
    $options['element_label_class'] = array('default' => '');
    $options['element_label_colon'] = array('default' => TRUE);

    $options['element_wrapper_type'] = array('default' => '');
    $options['element_wrapper_class'] = array('default' => '');

    $options['element_default_classes'] = array('default' => TRUE);

    $options['empty'] = array('default' => '');
    $options['hide_empty'] = array('default' => FALSE);
    $options['empty_zero'] = array('default' => FALSE);
    $options['hide_alter_empty'] = array('default' => TRUE);

    return $options;
  }

  /**
   * Performs some cleanup tasks on the options array before saving it.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $options = &$form_state->getValue('options');
    $types = array('element_type', 'element_label_type', 'element_wrapper_type');
    $classes = array_combine(array('element_class', 'element_label_class', 'element_wrapper_class'), $types);

    foreach ($types as $type) {
      if (!$options[$type . '_enable']) {
        $options[$type] = '';
      }
    }

    foreach ($classes as $class => $type) {
      if (!$options[$class . '_enable'] || !$options[$type . '_enable']) {
        $options[$class] = '';
      }
    }

    if (empty($options['custom_label'])) {
      $options['label'] = '';
      $options['element_label_colon'] = FALSE;
    }
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $label = $this->label();
    $form['custom_label'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create a label'),
      '#default_value' => $label !== '',
      '#weight' => -103,
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $label,
      '#states' => array(
        'visible' => array(
          ':input[name="options[custom_label]"]' => array('checked' => TRUE),
        ),
      ),
      '#weight' => -102,
    );
    $form['element_label_colon'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Place a colon after the label'),
      '#default_value' => $this->options['element_label_colon'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[custom_label]"]' => array('checked' => TRUE),
        ),
      ),
      '#weight' => -101,
    );

    $form['exclude'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude from display'),
      '#default_value' => $this->options['exclude'],
      '#description' => $this->t('Enable to load this field as hidden. Often used to group fields, or to use as token in another field.'),
      '#weight' => -100,
    );

    $form['style_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Style settings'),
      '#weight' => 99,
    );

    $form['element_type_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Customize field HTML'),
      '#default_value' => !empty($this->options['element_type']) || (string) $this->options['element_type'] == '0' || !empty($this->options['element_class']) || (string) $this->options['element_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_type'] = array(
      '#title' => $this->t('HTML element'),
      '#options' => $this->getElements(),
      '#type' => 'select',
      '#default_value' => $this->options['element_type'],
      '#description' => $this->t('Choose the HTML element to wrap around this field, e.g. H1, H2, etc.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );

    $form['element_class_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create a CSS class'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#default_value' => !empty($this->options['element_class']) || (string) $this->options['element_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_class'] = array(
      '#title' => $this->t('CSS class'),
      '#description' => $this->t('You may use token substitutions from the rewriting section in this class.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_class'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_type_enable]"]' => array('checked' => TRUE),
          ':input[name="options[element_class_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );

    $form['element_label_type_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Customize label HTML'),
      '#default_value' => !empty($this->options['element_label_type']) || (string) $this->options['element_label_type'] == '0' || !empty($this->options['element_label_class']) || (string) $this->options['element_label_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_label_type'] = array(
      '#title' => $this->t('Label HTML element'),
      '#options' => $this->getElements(FALSE),
      '#type' => 'select',
      '#default_value' => $this->options['element_label_type'],
      '#description' => $this->t('Choose the HTML element to wrap around this label, e.g. H1, H2, etc.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_label_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );
    $form['element_label_class_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create a CSS class'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_label_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#default_value' => !empty($this->options['element_label_class']) || (string) $this->options['element_label_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_label_class'] = array(
      '#title' => $this->t('CSS class'),
      '#description' => $this->t('You may use token substitutions from the rewriting section in this class.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_label_class'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_label_type_enable]"]' => array('checked' => TRUE),
          ':input[name="options[element_label_class_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );

    $form['element_wrapper_type_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Customize field and label wrapper HTML'),
      '#default_value' => !empty($this->options['element_wrapper_type']) || (string) $this->options['element_wrapper_type'] == '0' || !empty($this->options['element_wrapper_class']) || (string) $this->options['element_wrapper_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_wrapper_type'] = array(
      '#title' => $this->t('Wrapper HTML element'),
      '#options' => $this->getElements(FALSE),
      '#type' => 'select',
      '#default_value' => $this->options['element_wrapper_type'],
      '#description' => $this->t('Choose the HTML element to wrap around this field and label, e.g. H1, H2, etc. This may not be used if the field and label are not rendered together, such as with a table.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_wrapper_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );

    $form['element_wrapper_class_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create a CSS class'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_wrapper_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#default_value' => !empty($this->options['element_wrapper_class']) || (string) $this->options['element_wrapper_class'] == '0',
      '#fieldset' => 'style_settings',
    );
    $form['element_wrapper_class'] = array(
      '#title' => $this->t('CSS class'),
      '#description' => $this->t('You may use token substitutions from the rewriting section in this class.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_wrapper_class'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[element_wrapper_class_enable]"]' => array('checked' => TRUE),
          ':input[name="options[element_wrapper_type_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'style_settings',
    );

    $form['element_default_classes'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add default classes'),
      '#default_value' => $this->options['element_default_classes'],
      '#description' => $this->t('Use default Views classes to identify the field, field label and field content.'),
      '#fieldset' => 'style_settings',
    );

    $form['alter'] = array(
      '#title' => $this->t('Rewrite results'),
      '#type' => 'details',
      '#weight' => 100,
    );

    if ($this->allowAdvancedRender()) {
      $form['alter']['#tree'] = TRUE;
      $form['alter']['alter_text'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Override the output of this field with custom text'),
        '#default_value' => $this->options['alter']['alter_text'],
      );

      $form['alter']['text'] = array(
        '#title' => $this->t('Text'),
        '#type' => 'textarea',
        '#default_value' => $this->options['alter']['text'],
        '#description' => $this->t('The text to display for this field. You may include HTML or <a href=":url">Twig</a>. You may enter data from this view as per the "Replacement patterns" below.', array(':url' => CoreUrl::fromUri('http://twig.sensiolabs.org/documentation')->toString())),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][alter_text]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['make_link'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Output this field as a custom link'),
        '#default_value' => $this->options['alter']['make_link'],
      );
      $form['alter']['path'] = array(
        '#title' => $this->t('Link path'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['path'],
        '#description' => $this->t('The Drupal path or absolute URL for this link. You may enter data from this view as per the "Replacement patterns" below.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
        '#maxlength' => 255,
      );
      $form['alter']['absolute'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Use absolute path'),
        '#default_value' => $this->options['alter']['absolute'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['replace_spaces'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Replace spaces with dashes'),
        '#default_value' => $this->options['alter']['replace_spaces'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['external'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('External server URL'),
        '#default_value' => $this->options['alter']['external'],
        '#description' => $this->t("Links to an external server using a full URL: e.g. 'http://www.example.com' or 'www.example.com'."),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['path_case'] = array(
        '#type' => 'select',
        '#title' => $this->t('Transform the case'),
        '#description' => $this->t('When printing url paths, how to transform the case of the filter value.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
       '#options' => array(
          'none' => $this->t('No transform'),
          'upper' => $this->t('Upper case'),
          'lower' => $this->t('Lower case'),
          'ucfirst' => $this->t('Capitalize first letter'),
          'ucwords' => $this->t('Capitalize each word'),
        ),
        '#default_value' => $this->options['alter']['path_case'],
      );
      $form['alter']['link_class'] = array(
        '#title' => $this->t('Link class'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['link_class'],
        '#description' => $this->t('The CSS class to apply to the link.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['alt'] = array(
        '#title' => $this->t('Title text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['alt'],
        '#description' => $this->t('Text to place as "title" text which most browsers display as a tooltip when hovering over the link.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['rel'] = array(
        '#title' => $this->t('Rel Text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['rel'],
        '#description' => $this->t('Include Rel attribute for use in lightbox2 or other javascript utility.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['prefix'] = array(
        '#title' => $this->t('Prefix text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['prefix'],
        '#description' => $this->t('Any text to display before this link. You may include HTML.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['suffix'] = array(
        '#title' => $this->t('Suffix text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['suffix'],
        '#description' => $this->t('Any text to display after this link. You may include HTML.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['target'] = array(
        '#title' => $this->t('Target'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['target'],
        '#description' => $this->t("Target of the link, such as _blank, _parent or an iframe's name. This field is rarely used."),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
          ),
        ),
      );


      // Get a list of the available fields and arguments for token replacement.

      // Setup the tokens for fields.
      $previous = $this->getPreviousFieldLabels();
      $optgroup_arguments = (string) t('Arguments');
      $optgroup_fields = (string) t('Fields');
      foreach ($previous as $id => $label) {
        $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($label, ":"), 2 );
      }
      // Add the field to the list of options.
      $options[$optgroup_fields]["{{ {$this->options['id']} }}"] = substr(strrchr($this->adminLabel(), ":"), 2 );

      foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
        $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', array('@argument' => $handler->adminLabel()));
        $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', array('@argument' => $handler->adminLabel()));
      }

      $this->documentSelfTokens($options[$optgroup_fields]);

      // Default text.

      $output = [];
      $output[] = [
        '#markup' => '<p>' . $this->t('You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.') . '</p>',
      ];
      // We have some options, so make a list.
      if (!empty($options)) {
        $output[] = [
          '#markup' => '<p>' . $this->t("The following replacement tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
        ];
        foreach (array_keys($options) as $type) {
          if (!empty($options[$type])) {
            $items = array();
            foreach ($options[$type] as $key => $value) {
              $items[] = $key . ' == ' . $value;
            }
            $item_list = array(
              '#theme' => 'item_list',
              '#items' => $items,
            );
            $output[] = $item_list;
          }
        }
      }
      // This construct uses 'hidden' and not markup because process doesn't
      // run. It also has an extra div because the dependency wants to hide
      // the parent in situations like this, so we need a second div to
      // make this work.
      $form['alter']['help'] = array(
        '#type' => 'details',
        '#title' => $this->t('Replacement patterns'),
        '#value' => $output,
        '#states' => array(
          'visible' => array(
            array(
              ':input[name="options[alter][make_link]"]' => array('checked' => TRUE),
            ),
            array(
              ':input[name="options[alter][alter_text]"]' => array('checked' => TRUE),
            ),
            array(
              ':input[name="options[alter][more_link]"]' => array('checked' => TRUE),
            ),
          ),
        ),
      );

      $form['alter']['trim'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Trim this field to a maximum number of characters'),
        '#default_value' => $this->options['alter']['trim'],
      );

      $form['alter']['max_length'] = array(
        '#title' => $this->t('Maximum number of characters'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['max_length'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['word_boundary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Trim only on a word boundary'),
        '#description' => $this->t('If checked, this field be trimmed only on a word boundary. This is guaranteed to be the maximum characters stated or less. If there are no word boundaries this could trim a field to nothing.'),
        '#default_value' => $this->options['alter']['word_boundary'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['ellipsis'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Add "…" at the end of trimmed text'),
        '#default_value' => $this->options['alter']['ellipsis'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['more_link'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Add a read-more link if output is trimmed'),
        '#default_value' => $this->options['alter']['more_link'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['more_link_text'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('More link label'),
        '#default_value' => $this->options['alter']['more_link_text'],
        '#description' => $this->t('You may use the "Replacement patterns" above.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
            ':input[name="options[alter][more_link]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['alter']['more_link_path'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('More link path'),
        '#default_value' => $this->options['alter']['more_link_path'],
        '#description' => $this->t('This can be an internal Drupal path such as node/add or an external URL such as "https://www.drupal.org". You may use the "Replacement patterns" above.'),
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
            ':input[name="options[alter][more_link]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['html'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Field can contain HTML'),
        '#description' => $this->t('An HTML corrector will be run to ensure HTML tags are properly closed after trimming.'),
        '#default_value' => $this->options['alter']['html'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][trim]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['strip_tags'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Strip HTML tags'),
        '#default_value' => $this->options['alter']['strip_tags'],
      );

      $form['alter']['preserve_tags'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Preserve certain tags'),
        '#description' => $this->t('List the tags that need to be preserved during the stripping process. example &quot;&lt;p&gt; &lt;br&gt;&quot; which will preserve all p and br elements'),
        '#default_value' => $this->options['alter']['preserve_tags'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[alter][strip_tags]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['alter']['trim_whitespace'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Remove whitespace'),
        '#default_value' => $this->options['alter']['trim_whitespace'],
      );

      $form['alter']['nl2br'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Convert newlines to HTML &lt;br&gt; tags'),
        '#default_value' => $this->options['alter']['nl2br'],
      );
    }

    $form['empty_field_behavior'] = array(
      '#type' => 'details',
      '#title' => $this->t('No results behavior'),
      '#weight' => 100,
    );

    $form['empty'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('No results text'),
      '#default_value' => $this->options['empty'],
      '#description' => $this->t('Provide text to display if this field contains an empty result. You may include HTML. You may enter data from this view as per the "Replacement patterns" in the "Rewrite Results" section below.'),
      '#fieldset' => 'empty_field_behavior',
    );

    $form['empty_zero'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Count the number 0 as empty'),
      '#default_value' => $this->options['empty_zero'],
      '#description' => $this->t('Enable to display the "no results text" if the field contains the number 0.'),
      '#fieldset' => 'empty_field_behavior',
    );

    $form['hide_empty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide if empty'),
      '#default_value' => $this->options['hide_empty'],
      '#description' => $this->t('Enable to hide this field if it is empty. Note that the field label or rewritten output may still be displayed. To hide labels, check the style or row style settings for empty fields. To hide rewritten content, check the "Hide rewriting if empty" checkbox.'),
      '#fieldset' => 'empty_field_behavior',
    );

    $form['hide_alter_empty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide rewriting if empty'),
      '#default_value' => $this->options['hide_alter_empty'],
      '#description' => $this->t('Do not display rewritten content if this field is empty.'),
      '#fieldset' => 'empty_field_behavior',
    );
  }

  /**
   * Returns all field labels of fields before this field.
   *
   * @return array
   *   An array of field labels keyed by their field IDs.
   */
  protected function getPreviousFieldLabels() {
    $all_fields = $this->view->display_handler->getFieldLabels();
    $field_options = array_slice($all_fields, 0, array_search($this->options['id'], array_keys($all_fields)));
    return $field_options;
  }

  /**
   * Provide extra data to the administration form
   */
  public function adminSummary() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) { }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value);
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(ResultRow $row, $output) {
    // Make sure the last rendered value is available also when this is
    // retrieved from cache.
    $this->last_render = $output;
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function advancedRender(ResultRow $values) {
    if ($this->allowAdvancedRender() && $this instanceof MultiItemsFieldHandlerInterface) {
      $raw_items = $this->getItems($values);
      // If there are no items, set the original value to NULL.
      if (empty($raw_items)) {
        $this->original_value = NULL;
      }
    }
    else {
      $value = $this->render($values);
      if (is_array($value)) {
        $value = $this->getRenderer()->render($value);
      }
      $this->last_render = $value;
      $this->original_value = $value;
    }

    if ($this->allowAdvancedRender()) {
      $tokens = NULL;
      if ($this instanceof MultiItemsFieldHandlerInterface) {
        $items = array();
        foreach ($raw_items as $count => $item) {
          $value = $this->render_item($count, $item);
          if (is_array($value)) {
            $value = (string) $this->getRenderer()->render($value);
          }
          $this->last_render = $value;
          $this->original_value = $this->last_render;

          $alter = $item + $this->options['alter'];
          $alter['phase'] = static::RENDER_TEXT_PHASE_SINGLE_ITEM;
          $items[] = $this->renderText($alter);
        }

        $value = $this->renderItems($items);
      }
      else {
        $alter = array('phase' => static::RENDER_TEXT_PHASE_COMPLETELY) + $this->options['alter'];
        $value = $this->renderText($alter);
      }

      if (is_array($value)) {
        $value = $this->getRenderer()->render($value);
      }
      // This happens here so that renderAsLink can get the unaltered value of
      // this field as a token rather than the altered value.
      $this->last_render = $value;
    }

    // String cast is necessary to test emptiness of MarkupInterface
    // objects.
    if (empty((string) $this->last_render)) {
      if ($this->isValueEmpty($this->last_render, $this->options['empty_zero'], FALSE)) {
        $alter = $this->options['alter'];
        $alter['alter_text'] = 1;
        $alter['text'] = $this->options['empty'];
        $alter['phase'] = static::RENDER_TEXT_PHASE_EMPTY;
        $this->last_render = $this->renderText($alter);
      }
    }

    return $this->last_render;
  }

  /**
   * {@inheritdoc}
   */
  public function isValueEmpty($value, $empty_zero, $no_skip_empty = TRUE) {
    // Convert MarkupInterface to a string for checking.
    if ($value instanceof MarkupInterface) {
      $value = (string) $value;
    }
    if (!isset($value)) {
      $empty = TRUE;
    }
    else {
      $empty = ($empty_zero || ($value !== 0 && $value !== '0'));
    }

    if ($no_skip_empty) {
      $empty = empty($value) && $empty;
    }
    return $empty;
  }

  /**
   * {@inheritdoc}
   */
  public function renderText($alter) {
    // We need to preserve the safeness of the value regardless of the
    // alterations made by this method. Any alterations or replacements made
    // within this method need to ensure that at the minimum the result is
    // XSS admin filtered. See self::renderAltered() as an example that does.
    $value_is_safe = SafeMarkup::isSafe($this->last_render);
    // Cast to a string so that empty checks and string functions work as
    // expected.
    $value = (string) $this->last_render;

    if (!empty($alter['alter_text']) && $alter['text'] !== '') {
      $tokens = $this->getRenderTokens($alter);
      $value = $this->renderAltered($alter, $tokens);
    }

    if (!empty($this->options['alter']['trim_whitespace'])) {
      $value = trim($value);
    }

    // Check if there should be no further rewrite for empty values.
    $no_rewrite_for_empty = $this->options['hide_alter_empty'] && $this->isValueEmpty($this->original_value, $this->options['empty_zero']);

    // Check whether the value is empty and return nothing, so the field isn't rendered.
    // First check whether the field should be hidden if the value(hide_alter_empty = TRUE) /the rewrite is empty (hide_alter_empty = FALSE).
    // For numeric values you can specify whether "0"/0 should be empty.
    if ((($this->options['hide_empty'] && empty($value))
        || ($alter['phase'] != static::RENDER_TEXT_PHASE_EMPTY && $no_rewrite_for_empty))
      && $this->isValueEmpty($value, $this->options['empty_zero'], FALSE)) {
      return '';
    }
    // Only in empty phase.
    if ($alter['phase'] == static::RENDER_TEXT_PHASE_EMPTY && $no_rewrite_for_empty) {
      // If we got here then $alter contains the value of "No results text"
      // and so there is nothing left to do.
      if ($value_is_safe) {
        $value = ViewsRenderPipelineMarkup::create($value);
      }
      return $value;
    }

    if (!empty($alter['strip_tags'])) {
      $value = strip_tags($value, $alter['preserve_tags']);
    }

    $more_link = '';
    if (!empty($alter['trim']) && !empty($alter['max_length'])) {
      $length = strlen($value);
      $value = $this->renderTrimText($alter, $value);
      if ($this->options['alter']['more_link'] && strlen($value) < $length) {
        $tokens = $this->getRenderTokens($alter);
        $more_link_text = $this->options['alter']['more_link_text'] ? $this->options['alter']['more_link_text'] : $this->t('more');
        $more_link_text = strtr(Xss::filterAdmin($more_link_text), $tokens);
        $more_link_path = $this->options['alter']['more_link_path'];
        $more_link_path = strip_tags(Html::decodeEntities($this->viewsTokenReplace($more_link_path, $tokens)));

        // Make sure that paths which were run through URL generation work as
        // well.
        $base_path = base_path();
        // Checks whether the path starts with the base_path.
        if (strpos($more_link_path, $base_path) === 0) {
          $more_link_path = Unicode::substr($more_link_path, Unicode::strlen($base_path));
        }

        // @todo Views should expect and store a leading /. See
        //   https://www.drupal.org/node/2423913.
        $more_link = ' ' . $this->linkGenerator()->generate($more_link_text, CoreUrl::fromUserInput('/' . $more_link_path, array('attributes' => array('class' => array('views-more-link')))));
      }
    }

    if (!empty($alter['nl2br'])) {
      $value = nl2br($value);
    }

    if ($value_is_safe) {
      $value = ViewsRenderPipelineMarkup::create($value);
    }
    $this->last_render_text = $value;

    if (!empty($alter['make_link']) && (!empty($alter['path']) || !empty($alter['url']))) {
      if (!isset($tokens)) {
        $tokens = $this->getRenderTokens($alter);
      }
      $value = $this->renderAsLink($alter, $value, $tokens);
    }

    // Preserve whether or not the string is safe. Since $more_link comes from
    // \Drupal::l(), it is safe to append. Use SafeMarkup::isSafe() here because
    // renderAsLink() can return both safe and unsafe values.
    if (SafeMarkup::isSafe($value)) {
      return ViewsRenderPipelineMarkup::create($value . $more_link);
    }
    else {
      // If the string is not already marked safe, it is still OK to return it
      // because it will be sanitized by Twig.
      return $value . $more_link;
    }
  }

  /**
   * Render this field as user-defined altered text.
   */
  protected function renderAltered($alter, $tokens) {
    return $this->viewsTokenReplace($alter['text'], $tokens);
  }

  /**
   * Trims the field down to the specified length.
   *
   * @param array $alter
   *   The alter array of options to use.
   *     - max_length: Maximum length of the string, the rest gets truncated.
   *     - word_boundary: Trim only on a word boundary.
   *     - ellipsis: Show an ellipsis (…) at the end of the trimmed string.
   *     - html: Make sure that the html is correct.
   *
   * @param string $value
   *   The string which should be trimmed.
   *
   * @return string
   *   The rendered trimmed string.
   */
  protected function renderTrimText($alter, $value) {
    if (!empty($alter['strip_tags'])) {
      // NOTE: It's possible that some external fields might override the
      // element type.
      $this->definition['element type'] = 'span';
    }
    return static::trimText($alter, $value);
  }

  /**
   * Render this field as a link, with the info from a fieldset set by
   * the user.
   */
  protected function renderAsLink($alter, $text, $tokens) {
    $options = array(
      'absolute' => !empty($alter['absolute']) ? TRUE : FALSE,
      'alias' => FALSE,
      'entity' => NULL,
      'entity_type' => NULL,
      'fragment' => NULL,
      'language' => NULL,
      'query' => [],
    );

    $alter += [
      'path' => NULL
    ];

    $path = $alter['path'];
    if (empty($alter['url'])) {
      if (!parse_url($path, PHP_URL_SCHEME)) {
        // @todo Views should expect and store a leading /. See
        //   https://www.drupal.org/node/2423913.
        $alter['url'] = CoreUrl::fromUserInput('/' . ltrim($path, '/'));
      }
      else {
        $alter['url'] = CoreUrl::fromUri($path);
      }
    }

    $options = $alter['url']->getOptions() + $options;

    $path = $alter['url']->setOptions($options)->toUriString();

    // strip_tags() removes <front>, so check whether its different to front.
    if ($path != 'route:<front>') {
      // Unescape Twig delimiters that may have been escaped by the
      // Url::toUriString() call above, because we support twig tokens in
      // rewrite settings of views fields.
      // In that case the original path looks like
      // internal:/admin/content/files/usage/{{ fid }}, which will be escaped by
      // the toUriString() call above.
      $path = preg_replace(['/(\%7B){2}(\%20)*/', '/(\%20)*(\%7D){2}/'], ['{{','}}'], $path);

      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that are escaped
      // by \Drupal\Component\Utility\Html::escape().
      $path = strip_tags(Html::decodeEntities($this->viewsTokenReplace($path, $tokens)));

      if (!empty($alter['path_case']) && $alter['path_case'] != 'none' && !$alter['url']->isRouted()) {
        $path = str_replace($alter['path'], $this->caseTransform($alter['path'], $this->options['alter']['path_case']), $path);
      }

      if (!empty($alter['replace_spaces'])) {
        $path = str_replace(' ', '-', $path);
      }
    }

    // Parse the URL and move any query and fragment parameters out of the path.
    $url = UrlHelper::parse($path);

    // Seriously malformed URLs may return FALSE or empty arrays.
    if (empty($url)) {
      return $text;
    }

    // If the path is empty do not build a link around the given text and return
    // it as is.
    // http://www.example.com URLs will not have a $url['path'], so check host as well.
    if (empty($url['path']) && empty($url['host']) && empty($url['fragment']) && empty($url['url'])) {
      return $text;
    }

    // If we get to here we have a path from the url parsing. So assign that to
    // $path now so we don't get query strings or fragments in the path.
    $path = $url['path'];

    // If no scheme is provided in the $path, assign the default 'http://'.
    // This allows a url of 'www.example.com' to be converted to 'http://www.example.com'.
    // Only do this on for external URLs.
    if ($alter['external']) {
      if (!isset($url['scheme'])) {
        // There is no scheme, add the default 'http://' to the $path.
        // Use the original $alter['path'] instead of the parsed version.
        $path = "http://" . $alter['path'];
        // Reset the $url array to include the new scheme.
        $url = UrlHelper::parse($path);
      }
    }

    if (isset($url['query'])) {
      // Remove query parameters that were assigned a query string replacement
      // token for which there is no value available.
      foreach ($url['query'] as $param => $val) {
        if ($val == '%' . $param) {
          unset($url['query'][$param]);
        }
        // Replace any empty query params from URL parsing with NULL. So the
        // query will get built correctly with only the param key.
        // @see \Drupal\Component\Utility\UrlHelper::buildQuery().
        if ($val === '') {
          $url['query'][$param] = NULL;
        }
      }

      $options['query'] = $url['query'];
    }

    if (isset($url['fragment'])) {
      $path = strtr($path, array('#' . $url['fragment'] => ''));
      // If the path is empty we want to have a fragment for the current site.
      if ($path == '') {
        $options['external'] = TRUE;
      }
      $options['fragment'] = $url['fragment'];
    }

    $alt = $this->viewsTokenReplace($alter['alt'], $tokens);
    // Set the title attribute of the link only if it improves accessibility
    if ($alt && $alt != $text) {
      $options['attributes']['title'] = Html::decodeEntities($alt);
    }

    $class = $this->viewsTokenReplace($alter['link_class'], $tokens);
    if ($class) {
      $options['attributes']['class'] = array($class);
    }

    if (!empty($alter['rel']) && $rel = $this->viewsTokenReplace($alter['rel'], $tokens)) {
      $options['attributes']['rel'] = $rel;
    }

    $target = trim($this->viewsTokenReplace($alter['target'], $tokens));
    if (!empty($target)) {
      $options['attributes']['target'] = $target;
    }

    // Allow the addition of arbitrary attributes to links. Additional attributes
    // currently can only be altered in preprocessors and not within the UI.
    if (isset($alter['link_attributes']) && is_array($alter['link_attributes'])) {
      foreach ($alter['link_attributes'] as $key => $attribute) {
        if (!isset($options['attributes'][$key])) {
          $options['attributes'][$key] = $this->viewsTokenReplace($attribute, $tokens);
        }
      }
    }

    // If the query and fragment were programmatically assigned overwrite any
    // parsed values.
    if (isset($alter['query'])) {
      // Convert the query to a string, perform token replacement, and then
      // convert back to an array form for
      // \Drupal\Core\Utility\LinkGeneratorInterface::generate().
      $options['query'] = UrlHelper::buildQuery($alter['query']);
      $options['query'] = $this->viewsTokenReplace($options['query'], $tokens);
      $query = array();
      parse_str($options['query'], $query);
      $options['query'] = $query;
    }
    if (isset($alter['alias'])) {
      // Alias is a boolean field, so no token.
      $options['alias'] = $alter['alias'];
    }
    if (isset($alter['fragment'])) {
      $options['fragment'] = $this->viewsTokenReplace($alter['fragment'], $tokens);
    }
    if (isset($alter['language'])) {
      $options['language'] = $alter['language'];
    }

    // If the url came from entity_uri(), pass along the required options.
    if (isset($alter['entity'])) {
      $options['entity'] = $alter['entity'];
    }
    if (isset($alter['entity_type'])) {
      $options['entity_type'] = $alter['entity_type'];
    }

    // The path has been heavily processed above, so it should be used as-is.
    $final_url = CoreUrl::fromUri($path, $options);

    // Build the link based on our altered Url object, adding on the optional
    // prefix and suffix
    $render = [
      '#type' => 'link',
      '#title' => $text,
      '#url' => $final_url,
    ];

    if (!empty($alter['prefix'])) {
      $render['#prefix'] = $this->viewsTokenReplace($alter['prefix'], $tokens);
    }
    if (!empty($alter['suffix'])) {
      $render['#suffix'] = $this->viewsTokenReplace($alter['suffix'], $tokens);
    }
    return $this->getRenderer()->render($render);

  }

  /**
   * {@inheritdoc}
   */
  public function getRenderTokens($item) {
    $tokens = array();
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }
    $count = 0;
    foreach ($this->displayHandler->getHandlers('argument') as $arg => $handler) {
      $token = "{{ arguments.$arg }}";
      if (!isset($tokens[$token])) {
        $tokens[$token] = '';
      }

      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that
      // were removed by SafeMarkup::checkPlain().
      $tokens["{{ raw_arguments.$arg }}"] = isset($this->view->args[$count]) ? strip_tags(Html::decodeEntities($this->view->args[$count])) : '';
      $count++;
    }

    // Get flattened set of tokens for any array depth in query parameters.
    if ($request = $this->view->getRequest()) {
      $tokens += $this->getTokenValuesRecursive($request->query->all());
    }

    // Now add replacements for our fields.
    foreach ($this->displayHandler->getHandlers('field') as $field => $handler) {
      /** @var static $handler */
      $placeholder = $handler->getFieldTokenPlaceholder();

      if (isset($handler->last_render)) {
        $tokens[$placeholder] = $handler->last_render;
      }
      else {
        $tokens[$placeholder] = '';
      }

      // We only use fields up to (and including) this one.
      if ($field == $this->options['id']) {
        break;
      }
    }

    // Store the tokens for the row so we can reference them later if necessary.
    $this->view->style_plugin->render_tokens[$this->view->row_index] = $tokens;
    $this->last_tokens = $tokens;
    if (!empty($item)) {
      $this->addSelfTokens($tokens, $item);
    }

    return $tokens;
  }

  /**
   * Returns a token placeholder for the current field.
   *
   * @return string
   *   A token placeholder.
   */
  protected function getFieldTokenPlaceholder() {
    return '{{ ' . $this->options['id'] . ' }}';
  }

  /**
   * Recursive function to add replacements for nested query string parameters.
   *
   * E.g. if you pass in the following array:
   *   array(
   *     'foo' => array(
   *       'a' => 'value',
   *       'b' => 'value',
   *     ),
   *     'bar' => array(
   *       'a' => 'value',
   *       'b' => array(
   *         'c' => value,
   *       ),
   *     ),
   *   );
   *
   * Would yield the following array of tokens:
   *   array(
   *     '%foo_a' => 'value'
   *     '%foo_b' => 'value'
   *     '%bar_a' => 'value'
   *     '%bar_b_c' => 'value'
   *   );
   *
   * @param $array
   *   An array of values.
   *
   * @param $parent_keys
   *   An array of parent keys. This will represent the array depth.
   *
   * @return
   *   An array of available tokens, with nested keys representative of the array structure.
   */
  protected function getTokenValuesRecursive(array $array, array $parent_keys = array()) {
    $tokens = array();

    foreach ($array as $param => $val) {
      if (is_array($val)) {
        // Copy parent_keys array, so we don't affect other elements of this
        // iteration.
        $child_parent_keys = $parent_keys;
        $child_parent_keys[] = $param;
        // Get the child tokens.
        $child_tokens = $this->getTokenValuesRecursive($val, $child_parent_keys);
        // Add them to the current tokens array.
        $tokens += $child_tokens;
      }
      else {
        // Create a token key based on array element structure.
        $token_string = !empty($parent_keys) ? implode('.', $parent_keys) . '.' . $param : $param;
        $tokens['{{ arguments.' . $token_string . ' }}'] = strip_tags(Html::decodeEntities($val));
      }
    }

    return $tokens;
  }

  /**
   * Add any special tokens this field might use for itself.
   *
   * This method is intended to be overridden by items that generate
   * fields as a list. For example, the field that displays all terms
   * on a node might have tokens for the tid and the term.
   *
   * By convention, tokens should follow the format of {{ token
   * subtoken }}
   * where token is the field ID and subtoken is the field. If the
   * field ID is terms, then the tokens might be {{ terms__tid }} and
   * {{ terms__name }}.
   */
  protected function addSelfTokens(&$tokens, $item) { }

  /**
   * Document any special tokens this field might use for itself.
   *
   * @see addSelfTokens()
   */
  protected function documentSelfTokens(&$tokens) { }

  /**
   * {@inheritdoc}
   */
  function theme(ResultRow $values) {
    $renderer = $this->getRenderer();
    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#field' => $this,
      '#row' => $values,
    );
    $output = $renderer->render($build);

    // Set the bubbleable rendering metadata on $view->element. This ensures the
    // bubbleable rendering metadata of individual rendered fields is not lost.
    // @see \Drupal\Core\Render\Renderer::updateStack()
    $this->view->element = $renderer->mergeBubbleableMetadata($this->view->element, $build);

    return $output;
  }

  public function themeFunctions() {
    $themes = array();
    $hook = 'views_view_field';

    $display = $this->view->display_handler->display;

    if (!empty($display)) {
      $themes[] = $hook . '__' . $this->view->storage->id()  . '__' . $display['id'] . '__' . $this->options['id'];
      $themes[] = $hook . '__' . $this->view->storage->id()  . '__' . $display['id'];
      $themes[] = $hook . '__' . $display['id'] . '__' . $this->options['id'];
      $themes[] = $hook . '__' . $display['id'];
      if ($display['id'] != $display['display_plugin']) {
        $themes[] = $hook . '__' . $this->view->storage->id()  . '__' . $display['display_plugin'] . '__' . $this->options['id'];
        $themes[] = $hook . '__' . $this->view->storage->id()  . '__' . $display['display_plugin'];
        $themes[] = $hook . '__' . $display['display_plugin'] . '__' . $this->options['id'];
        $themes[] = $hook . '__' . $display['display_plugin'];
      }
    }
    $themes[] = $hook . '__' . $this->view->storage->id() . '__' . $this->options['id'];
    $themes[] = $hook . '__' . $this->view->storage->id();
    $themes[] = $hook . '__' . $this->options['id'];
    $themes[] = $hook;

    return $themes;
  }

  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  /**
   * Trims the field down to the specified length.
   *
   * @param array $alter
   *   The alter array of options to use.
   *     - max_length: Maximum length of the string, the rest gets truncated.
   *     - word_boundary: Trim only on a word boundary.
   *     - ellipsis: Show an ellipsis (…) at the end of the trimmed string.
   *     - html: Make sure that the html is correct.
   *
   * @param string $value
   *   The string which should be trimmed.
   *
   * @return string
   *   The trimmed string.
   */
  public static function trimText($alter, $value) {
    if (Unicode::strlen($value) > $alter['max_length']) {
      $value = Unicode::substr($value, 0, $alter['max_length']);
      if (!empty($alter['word_boundary'])) {
        $regex = "(.*)\b.+";
        if (function_exists('mb_ereg')) {
          mb_regex_encoding('UTF-8');
          $found = mb_ereg($regex, $value, $matches);
        }
        else {
          $found = preg_match("/$regex/us", $value, $matches);
        }
        if ($found) {
          $value = $matches[1];
        }
      }
      // Remove scraps of HTML entities from the end of a strings
      $value = rtrim(preg_replace('/(?:<(?!.+>)|&(?!.+;)).*$/us', '', $value));

      if (!empty($alter['ellipsis'])) {
        $value .= t('…');
      }
    }
    if (!empty($alter['html'])) {
      $value = Html::normalize($value);
    }

    return $value;
  }

  /**
   * Gets the link generator.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected function linkGenerator() {
    if (!isset($this->linkGenerator)) {
      $this->linkGenerator = \Drupal::linkGenerator();
    }
    return $this->linkGenerator;
  }

  /**
   * Returns the render API renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   */
  protected function getRenderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }

    return $this->renderer;
  }

}

/**
 * @} End of "defgroup views_field_handlers".
 */
