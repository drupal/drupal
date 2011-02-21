<?php
// $Id: views_handler_field.inc,v 1.33.4.44 2010/12/23 22:38:54 dereine Exp $
/**
 * @defgroup views_field_handlers Views' field handlers
 * @{
 * Handlers to tell Views how to build and display fields.
 *
 */

/**
 * Base field handler that has no options and renders an unformatted field.
 *
 * Definition terms:
 * - additional fields: An array of fields that should be added to the query
 *                      for some purpose. The array is in the form of:
 *                      array('identifier' => array('table' => tablename,
 *                      'field' => fieldname); as many fields as are necessary
 *                      may be in this array.
 * - click sortable: If TRUE, this field may be click sorted.
 */
class views_handler_field extends views_handler {
  var $field_alias = 'unknown';
  var $aliases = array();

  /**
   * Construct a new field handler.
   */
  function construct() {
    parent::construct();

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
  function allow_advanced_render() {
    return TRUE;
  }

  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->options += array(
      'exclude' => FALSE,
    );
  }

  /**
   * Called to add the field to a query.
   */
  function query() {
    $this->ensure_my_table();
    // Add the field.
    $this->field_alias = $this->query->add_field($this->table_alias, $this->real_field);

    $this->add_additional_fields();
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
  function add_additional_fields($fields = NULL) {
    if (!isset($fields)) {
      // notice check
      if (empty($this->additional_fields)) {
        return;
      }
      $fields = $this->additional_fields;
    }
    if (!empty($fields) && is_array($fields)) {
      foreach ($fields as $identifier => $info) {
        if (is_array($info)) {
          if (isset($info['table'])) {
            $table_alias = $this->query->ensure_table($info['table'], $this->relationship);
          }
          else {
            $table_alias = $this->table_alias;
          }

          if (empty($table_alias)) {
            debug(t('Handler @handler tried to add additional_field @identifier but @table could not be added!', array('@handler' => $this->definition['handler'], '@identifier' => $identifier, '@table' => $info['table'])));
            $this->aliases[$identifier] = 'broken';
            continue;
          }

          $params = array();
          if (!empty($info['params'])) {
            $params = $info['params'];
          }

          $this->aliases[$identifier] = $this->query->add_field($table_alias, $info['field'], NULL, $params);
        }
        else {
          $this->aliases[$info] = $this->query->add_field($this->table_alias, $info);
        }
      }
    }
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  function click_sort($order) {
    if (isset($this->field_alias)) {
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $this->query->add_orderby(NULL, NULL, $order, $this->field_alias);
    }
  }

  /**
   * Determine if this field is click sortable.
   */
  function click_sortable() {
    return !empty($this->definition['click sortable']);
  }

  /**
   * Get this field's label.
   */
  function label() {
    if (!isset($this->options['label'])) {
      return '';
    }
    return $this->options['label'];
  }

  /**
   * Return an HTML element based upon the field's element type.
   */
  function element_type($none_supported = FALSE, $default_empty = FALSE) {
    if ($none_supported) {
      if ($this->options['element_type'] === '0') {
        return '';
      }
    }
    if ($this->options['element_type']) {
      return check_plain($this->options['element_type']);
    }

    if ($default_empty) {
      return '';
    }

    if (isset($this->definition['element type'])) {
      return $this->definition['element type'];
    }

    return 'span';
  }

  /**
   * Return an HTML element for the label based upon the field's element type.
   */
  function element_label_type($none_supported = FALSE, $default_empty = FALSE) {
    if ($none_supported) {
      if ($this->options['element_label_type'] === '0') {
        return '';
      }
    }
    if ($this->options['element_label_type']) {
      return check_plain($this->options['element_label_type']);
    }

    if ($default_empty) {
      return '';
    }

    return 'span';
  }

  /**
   * Return an HTML element for the wrapper based upon the field's element type.
   */
  function element_wrapper_type($none_supported = FALSE, $default_empty = FALSE) {
    if ($none_supported) {
      if ($this->options['element_wrapper_type'] === '0') {
        return 0;
      }
    }
    if ($this->options['element_wrapper_type']) {
      return check_plain($this->options['element_wrapper_type']);
    }

    if ($default_empty) {
      return '';
    }

    return 'div';
  }

  /**
   * Provide a list of elements valid for field HTML.
   *
   * This function can be overridden by fields that want more or fewer
   * elements available, though this seems like it would be an incredibly
   * rare occurence.
   */
  function get_elements() {
    return array(
      '' => t('- Use default -'),
      '0' => t('- None -'),
      'div' => t('DIV'),
      'span' => t('SPAN'),
      'h1' => t('H1'),
      'h2' => t('H2'),
      'h3' => t('H3'),
      'h4' => t('H4'),
      'h5' => t('H5'),
      'h6' => t('H6'),
      'p' => t('P'),
      'strong' => t('STRONG'),
      'em' => t('EM'),
    );
  }

  /**
   * Return the class of the field.
   */
  function element_classes() {
    $classes = $this->tokenize_value($this->options['element_class']);
    return drupal_clean_css_identifier($classes);
  }

  /**
   * Replace a value with tokens from the last field.
   *
   * This function actually figures out which field was last and uses its
   * tokens so they will all be available.
   */
  function tokenize_value($value) {
    if (strpos($value, '[') !== FALSE || strpos($value, '!') !== FALSE || strpos($value, '%') !== FALSE) {
      $fake_item = array(
        'alter_text' => TRUE,
        'text' => $value,
      );

      // Get tokens from the last field.
      $last_field = end($this->view->field);
      if (isset($last_field->last_tokens)) {
        $tokens = $last_field->last_tokens;
      }
      else {
        $tokens = $last_field->get_render_tokens($fake_item);
      }

      $value = strip_tags($this->render_altered($fake_item, $tokens));
    }

    return $value;
  }

  /**
   * Return the class of the field's label.
   */
  function element_label_classes() {
    return drupal_clean_css_identifier($this->options['element_label_class']);
  }

  /**
   * Return the class of the field's wrapper.
   */
  function element_wrapper_classes() {
    return drupal_clean_css_identifier($this->options['element_wrapper_class']);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['label'] = array('default' => $this->definition['title'], 'translatable' => TRUE);
    $options['exclude'] = array('default' => FALSE, 'bool' => TRUE);
    $options['alter'] = array(
      'contains' => array(
        'alter_text' => array('default' => FALSE),
        'text' => array('default' => '', 'translatable' => TRUE),
        'make_link' => array('default' => FALSE),
        'path' => array('default' => '', 'translatable' => TRUE),
        'absolute' => array('default' => '', 'translatable' => FALSE),
        'alt' => array('default' => '', 'translatable' => TRUE),
        'rel' => array('default' => ''),
        'link_class' => array('default' => ''),
        'prefix' => array('default' => '', 'translatable' => TRUE),
        'suffix' => array('default' => '', 'translatable' => TRUE),
        'target' => array('default' => '', 'translatable' => TRUE),
        'trim' => array('default' => FALSE),
        'max_length' => array('default' => ''),
        'word_boundary' => array('default' => TRUE),
        'ellipsis' => array('default' => TRUE),
        'strip_tags' => array('default' => FALSE),
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

    $options['empty'] = array('default' => '', 'translatable' => TRUE);
    $options['hide_empty'] = array('default' => FALSE);
    $options['empty_zero'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Use prefix and suffix to fake a fieldset because we use #tree.
    $form['style_prefix'] = array(
      '#value' => '<fieldset id="views-validator-options"><legend>' . t('Style settings') . '</legend>',
    );

    $form['exclude'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude from display'),
      '#default_value' => $this->options['exclude'],
      '#description' => t('Check this box to not display this field, but still load it in the view. Use this option to not show a grouping field in each record, or when doing advanced theming, or when you want to use this field as a token in other fields.'),
    );

    $form['element_type'] = array(
      '#title' => t('HTML element'),
      '#options' => $this->get_elements(),
      '#type' => 'select',
      '#default_value' => $this->options['element_type'],
      '#description' => t('Most styles provide wrappers for fields. If the chosen style supports wrappers, wrap the field in this HTML element. The default will usually be either DIV or SPAN.'),
    );

    $form['element_class'] = array(
      '#title' => t('Element class'),
      '#description' => t('The class to provide on the wrapper element. You may enter data from this view as per the "Replacement patterns" used in "Rewrite the output of this field".'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_class'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : '',
      '#description' => t('The label for this field that will be displayed to end users if the style requires it.'),
    );

    $form['element_label_type'] = array(
      '#title' => t('Label HTML element'),
      '#options' => $this->get_elements(FALSE),
      '#type' => 'select',
      '#default_value' => $this->options['element_label_type'],
      '#description' => t('What HTML Element type to use to wrap the label.'),
    );

    $form['element_label_class'] = array(
      '#title' => t('Label class'),
      '#description' => t('The class to provide on the label wrapper element.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_label_class'],
    );

    $form['element_label_colon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Place a colon after the label'),
      '#default_value' => $this->options['element_label_colon'],
      '#description' => t('If the label is to be inline with the value, place a colon between them. Not valid for styles such as table where the label is not placed with the value.'),
    );

    $form['element_wrapper_type'] = array(
      '#title' => t('Wrapper HTML element'),
      '#options' => $this->get_elements(FALSE),
      '#type' => 'select',
      '#default_value' => $this->options['element_wrapper_type'],
      '#description' => t('What HTML Element type to use to wrap the field (and the label). This is not supported by some styles such as tables.'),
    );

    $form['element_wrapper_class'] = array(
      '#title' => t('Wrapper class'),
      '#description' => t('The class to provide on the wrapper element.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['element_wrapper_class'],
    );

    $form['element_default_classes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add default classes'),
      '#default_value' => $this->options['element_default_classes'],
      '#description' => t('Use default Views classes to identify the field, field label and field content.'),
    );

    $form['style_suffix'] = array(
      '#value' => '</fieldset>',
    );

    $form['alter'] = array(
      '#title' => t('Rewriting'),
      '#type' => 'fieldset',
    );

    if ($this->allow_advanced_render()) {
      $form['alter']['#tree'] = TRUE;
      $form['alter']['alter_text'] = array(
        '#type' => 'checkbox',
        '#title' => t('Rewrite the output of this field'),
        '#description' => t('If checked, you can alter the output of this field by specifying a string of text with replacement tokens that can use any existing field output.'),
        '#default_value' => $this->options['alter']['alter_text'],
      );

      $form['alter']['text'] = array(
        '#title' => t('Text'),
        '#type' => 'textarea',
        '#default_value' => $this->options['alter']['text'],
        '#description' => t('The text to display for this field. You may include HTML. You may enter data from this view as per the "Replacement patterns" below.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-alter-text' => array(1)
        ),
      );

      $form['alter']['make_link'] = array(
        '#type' => 'checkbox',
        '#title' => t('Output this field as a link'),
        '#description' => t('If checked, this field will be made into a link. The destination must be given below.'),
        '#default_value' => $this->options['alter']['make_link'],
      );
      $form['alter']['path'] = array(
        '#title' => t('Link path'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['path'],
        '#description' => t('The Drupal path or absolute URL for this link. You may enter data from this view as per the "Replacement patterns" below.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
        '#maxlength' => 255,
      );
       $form['alter']['absolute'] = array(
         '#type' => 'checkbox',
         '#title' => t('Use absolute path'),
         '#default_value' => $this->options['alter']['absolute'],
         '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
         '#dependency' => array(
           'edit-options-alter-make-link' => array(1)
         ),
       );

      $form['alter']['link_class'] = array(
        '#title' => t('Link class'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['link_class'],
        '#description' => t('The CSS class to apply to the link.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );
      $form['alter']['alt'] = array(
        '#title' => t('Alt text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['alt'],
        '#description' => t('Text to place as "alt" text which most browsers display as a tooltip when hovering over the link.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );
      $form['alter']['rel'] = array(
        '#title' => t('Rel Text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['rel'],
        '#description' => t('Include Rel attribute for use in lightbox2 or other javascript utility.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );
      $form['alter']['prefix'] = array(
        '#title' => t('Prefix text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['prefix'],
        '#description' => t('Any text to display before this link. You may include HTML.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );
      $form['alter']['suffix'] = array(
        '#title' => t('Suffix text'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['suffix'],
        '#description' => t('Any text to display after this link. You may include HTML.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );
      $form['alter']['target'] = array(
        '#title' => t('Target'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['target'],
        '#description' => t("Target of the link, such as _blank, _parent or an iframe's name. This field is rarely used."),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1)
        ),
      );


      // Get a list of the available fields and arguments for token replacement.
      $options = array();
      foreach ($this->view->display_handler->get_handlers('field') as $field => $handler) {
        $options[t('Fields')]["[$field]"] = $handler->ui_name();
        // We only use fields up to (and including) this one.
        if ($field == $this->options['id']) {
          break;
        }
      }
      $count = 0; // This lets us prepare the key as we want it printed.
      foreach ($this->view->display_handler->get_handlers('argument') as $arg => $handler) {
        $options[t('Arguments')]['%' . ++$count] = t('@argument title', array('@argument' => $handler->ui_name()));
        $options[t('Arguments')]['!' . $count] = t('@argument input', array('@argument' => $handler->ui_name()));
      }

      $this->document_self_tokens($options[t('Fields')]);

      // Default text.
      $output = t('<p>You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.</p>');
      // We have some options, so make a list.
      if (!empty($options)) {
        $output = t('<p>The following tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.
If you would like to have the characters %5B and %5D please use the html entity codes \'%5B\' or  \'%5D\' or they will get replaced with empty space.</p>');
        foreach (array_keys($options) as $type) {
          if (!empty($options[$type])) {
            $items = array();
            foreach ($options[$type] as $key => $value) {
              $items[] = $key . ' == ' . $value;
            }
            $output .= theme('item_list',
              array(
                'items' => $items,
                'type' => $type
              ));
          }
        }
      }
      // This construct uses 'hidden' and not markup because process doesn't
      // run. It also has an extra div because the dependency wants to hide
      // the parent in situations like this, so we need a second div to
      // make this work.
      $form['alter']['help'] = array(
        '#type' => 'hidden',
        '#id' => 'views-tokens-help',
        '#prefix' => '<div><fieldset id="views-tokens-help"><legend>' . t('Replacement patterns') . '</legend>' . $output . '</fieldset></div>',
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-make-link' => array(1),
          'edit-options-alter-alter-text' => array(1),
        ),
      );

      $form['alter']['trim'] = array(
        '#type' => 'checkbox',
        '#title' => t('Trim this field to a maximum length'),
        '#description' => t('If checked, this field be trimmed to a maximum length in characters.'),
        '#default_value' => $this->options['alter']['trim'],
      );

      $form['alter']['max_length'] = array(
        '#title' => t('Maximum length'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alter']['max_length'],
        '#description' => t('The maximum number of characters this field can be.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-trim' => array(1)
        ),
      );

      $form['alter']['word_boundary'] = array(
        '#type' => 'checkbox',
        '#title' => t('Trim only on a word boundary'),
        '#description' => t('If checked, this field be trimmed only on a word boundary. This is guaranteed to be the maximum characters stated or less. If there are no word boundaries this could trim a field to nothing.'),
        '#default_value' => $this->options['alter']['word_boundary'],
        '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-trim' => array(1)
        ),
      );

      $form['alter']['ellipsis'] = array(
        '#type' => 'checkbox',
        '#title' => t('Add an ellipsis'),
        '#description' => t('If checked, a "..." will be added if a field was trimmed.'),
        '#default_value' => $this->options['alter']['ellipsis'],
        '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-trim' => array(1)
        ),
      );

      $form['alter']['html'] = array(
        '#type' => 'checkbox',
        '#title' => t('Field can contain HTML'),
        '#description' => t('If checked, HTML corrector will be run to ensure tags are properly closed after trimming.'),
        '#default_value' => $this->options['alter']['html'],
        '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-alter-trim' => array(1)
        ),
      );

      $form['alter']['strip_tags'] = array(
        '#type' => 'checkbox',
        '#title' => t('Strip HTML tags'),
        '#description' => t('If checked, all HTML tags will be stripped.'),
        '#default_value' => $this->options['alter']['strip_tags'],
        '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
      );
      $form['alter']['preserve_tags'] = array(
        '#type' => 'textfield',
        '#title' => t('Preserve certain tags'),
        '#description' => t('List the tags that need to be preserved during the stripping process. example &quot;&lt;p&gt; &lt;br&gt;&quot; which will preserve all p and br elements'),
        '#default_value' => $this->options['alter']['preserve_tags'],
        '#dependency' => array(
          'edit-options-alter-strip-tags' => array(1),
        ),
        '#process' => array('ctools_dependent_process'),
      );
    }

    // Use prefix and suffix to fake a fieldset because we use #tree.
    $form['empty_prefix'] = array(
      '#value' => '<fieldset id="views-validator-options"><legend>' . t('Empty field behavior') . '</legend>',
    );

    $form['empty'] = array(
      '#type' => 'textfield',
      '#title' => t('Empty text'),
      '#default_value' => $this->options['empty'],
      '#description' => t('If the field is empty, display this text instead.'),
    );

    $form['empty_zero'] = array(
      '#type' => 'checkbox',
      '#title' => t('Count the number 0 as empty'),
      '#default_value' => $this->options['empty_zero'],
      '#description' => t('If the field contains the number zero, display the empty text instead'),
    );

    $form['hide_empty'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide if empty'),
      '#default_value' => $this->options['hide_empty'],
      '#description' => t('Do not display anything for this field if it is empty. Note that the field label may still be displayed. Check style or row style settings to hide labels for empty fields.'),
    );

    $form['empty_suffix'] = array(
      '#value' => '</fieldset>',
    );

  }

  /**
   * Provide extra data to the administration form
   */
  function admin_summary() {
    return $this->label();
  }

  /**
   * Run before any fields are rendered.
   *
   * This gives the handlers some time to set up before any handler has
   * been rendered.
   *
   * @param $values
   *   An array of all objects returned from the query.
   */
  function pre_render(&$values) { }

  /**
   * Render the field.
   *
   * @param $values
   *   The values retrieved from the database.
   */
  function render($values) {
    $value = $values->{$this->field_alias};
    return check_plain($value);
  }

  /**
   * Render a field using advanced settings.
   *
   * This renders a field normally, then decides if render-as-link and
   * text-replacement rendering is necessary.
   */
  function advanced_render($values) {
    if ($this->allow_advanced_render() && method_exists($this, 'render_item')) {
      $raw_items = $this->get_items($values);
    }
    else {
      $this->last_render = $value = $this->render($values);
      $this->original_value = $value;
    }

    if ($this->allow_advanced_render()) {
      $tokens = NULL;
      if (method_exists($this, 'render_item')) {
        $items = array();
        foreach ($raw_items as $count => $item) {
          $this->last_render = $this->render_item($count, $item);
          $this->original_value = $this->last_render;

          $alter = $item + $this->options['alter'];
          $items[] = $this->render_text($alter);
        }

        $value = $this->render_items($items);
      }
      else {
        $value = $this->render_text($this->options['alter']);
      }

      // This happens here so that render_as_link can get the unaltered value of
      // this field as a token rather than the altered value.
      $this->last_render = $value;
    }

    if (empty($this->last_render)) {
      if (($this->last_render !== 0 && $this->last_render !== '0') || !empty($this->options['empty_zero'])) {
        $alter = $this->options['alter'];
        $alter['alter_text'] = 1;
        $alter['text'] = $this->options['empty'];
        $this->last_render = $this->render_text($alter);
      }
    }

    return $this->last_render;
  }

  /**
   * Perform an advanced text render for the item.
   *
   * This is separated out as some fields may render lists, and this allows
   * each item to be handled individually.
   */
  function render_text($alter) {
    $value = trim($this->last_render);

    if (!empty($alter['alter_text']) && $alter['text'] !== '') {
      $tokens = $this->get_render_tokens($alter);
      $value = $this->render_altered($alter, $tokens);
    }

    if ($this->options['hide_empty'] && empty($value) && ($value !== 0 || $this->options['empty_zero'])) {
      return '';
    }

    if (!empty($alter['strip_tags'])) {
      $value = strip_tags($value, $alter['preserve_tags']);
    }

    if (!empty($alter['trim']) && !empty($alter['max_length'])) {
      $value = $this->render_trim_text($alter, $value);
    }

    if (!empty($alter['make_link']) && !empty($alter['path'])) {
      if (!isset($tokens)) {
       $tokens = $this->get_render_tokens($alter);
      }
      $value = $this->render_as_link($alter, $value, $tokens);
    }

    return $value;
  }

  /**
   * Render this field as altered text, from a fieldset set by the user.
   */
  function render_altered($alter, $tokens) {
    // Filter this right away as our substitutions are already sanitized.
    $value = filter_xss_admin($alter['text']);
    $value = strtr($value, $tokens);

    return $value;
  }

  /**
   * Trim the field down to the specified length.
   */
  function render_trim_text($alter, $value) {
    if (!empty($alter['strip_tags'])) {
      // NOTE: It's possible that some external fields might override the
      // element type so if someone from, say, CCK runs into a bug here,
      // this may be why =)
      $this->definition['element type'] = 'span';
    }
    return views_trim_text($alter, $value);
  }

  /**
   * Render this field as a link, with the info from a fieldset set by
   * the user.
   */
  function render_as_link($alter, $text, $tokens) {
    $value = '';

    if (!empty($alter['prefix'])) {
      $value .= filter_xss_admin(strtr($alter['prefix'], $tokens));
    }

    $options = array(
      'html' => TRUE,
      'absolute' => !empty($alter['absolute']) ? TRUE : FALSE,
    );

    // $path will be run through check_url() by l() so we do not need to
    // sanitize it ourselves.
    $path = $alter['path'];

    // html_entity_decode removes <front>, so check whether its different to front.
    if ($path != '<front>') {
      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that
      // were removed by check_plain().
      $path = strip_tags(html_entity_decode(strtr($path, $tokens)));
    }

    // If the path is empty do not build a link around the given text and return
    // it as is.
    if (empty($path)) {
      return $text;
    }

    // Parse the URL and move any query and fragment parameters out of the path.
    $url = parse_url($path);
    if (isset($url['query'])) {
      $path = strtr($path, array('?' . $url['query'] => ''));
      $options['query'] = drupal_get_query_array($url['query']);
    }
    if (isset($url['fragment'])) {
      $path = strtr($path, array('#' . $url['fragment'] => ''));
      // If the path is empty we want to have a fragment for the current site.
      if ($path == '') {
        $options['external'] = TRUE;
      }
      $options['fragment'] = $url['fragment'];
    }

    $alt = strtr($alter['alt'], $tokens);
    // Set the title attribute of the link only if it improves accessibility
    if ($alt && $alt != $text) {
      $options['attributes']['title'] = html_entity_decode($alt, ENT_QUOTES);
    }

    $class = strtr($alter['link_class'], $tokens);
    if ($class) {
      $options['attributes']['class'] = array($class);
    }

    if (!empty($alter['rel']) && $rel = strtr($alter['rel'], $tokens)) {
      $options['attributes']['rel'] = $rel;
    }

    $target = check_plain(trim(strtr($alter['target'],$tokens)));
    if (!empty($target)) {
      $options['attributes']['target'] = $target;
    }

    // If the query and fragment were programatically assigned overwrite any
    // parsed values.
    if (isset($alter['query'])) {
      // Convert the query to a string, perform token replacement, and then
      // convert back to an array form for l().
      $options['query'] = drupal_http_build_query($alter['query']);
      $options['query'] = strtr($options['query'], $tokens);
      $options['query'] = drupal_get_query_array($options['query']);
    }
    if (isset($alter['alias'])) {
      // Alias is a boolean field, so no token.
      $options['alias'] = $alter['alias'];
    }
    if (isset($alter['fragment'])) {
      $options['fragment'] = strtr($alter['fragment'], $tokens);
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

    $value .= l($text, $path, $options);

    if (!empty($alter['suffix'])) {
      $value .= filter_xss_admin(strtr($alter['suffix'], $tokens));
    }

    return $value;
  }

  /**
   * Get the 'render' tokens to use for advanced rendering.
   *
   * This runs through all of the fields and arguments that
   * are available and gets their values. This will then be
   * used in one giant str_replace().
   */
  function get_render_tokens($item) {
    $tokens = array();
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }
    $count = 0;
    foreach ($this->view->display_handler->get_handlers('argument') as $arg => $handler) {
      $token = '%' . ++$count;
      if (!isset($tokens[$token])) {
        $tokens[$token] = '';
      }

      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that
      // were removed by check_plain().
      $tokens['!' . $count] = isset($this->view->args[$count - 1]) ? strip_tags(html_entity_decode($this->view->args[$count - 1])) : '';
    }

    // Now add replacements for our fields.
    foreach ($this->view->display_handler->get_handlers('field') as $field => $handler) {
      if (isset($handler->last_render)) {
        $tokens["[$field]"] = $handler->last_render;
      }
      else {
        $tokens["[$field]"] = '';
      }
      $this->add_self_tokens($tokens, $item);

      // We only use fields up to (and including) this one.
      if ($field == $this->options['id']) {
        break;
      }
    }

    $this->last_tokens = $tokens;
    return $tokens;
  }

  /**
   * Add any special tokens this field might use for itself.
   *
   * This method is intended to be overridden by items that generate
   * fields as a list. For example, the field that displays all terms
   * on a node might have tokens for the tid and the term.
   *
   * By convention, tokens should follow the format of [token-subtoken]
   * where token is the field ID and subtoken is the field. If the
   * field ID is terms, then the tokens might be [terms-tid] and [terms-name].
   */
  function add_self_tokens(&$tokens, $item) { }

  /**
   * Document any special tokens this field might use for itself.
   *
   * @see add_self_tokens() for details.
   */
  function document_self_tokens(&$tokens) { }

  /**
   * Call out to the theme() function, which probably just calls render() but
   * allows sites to override output fairly easily.
   */
  function theme($values) {
    return theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'field' => $this,
        'row' => $values
      ));
  }

  function theme_functions() {
    $themes = array();
    $hook = 'views_view_field';

    $display = $this->view->display[$this->view->current_display];

    if (!empty($display)) {
      $themes[] = $hook . '__' . $this->view->name  . '__' . $display->id . '__' . $this->options['id'];
      $themes[] = $hook . '__' . $this->view->name  . '__' . $display->id;
      $themes[] = $hook . '__' . $display->id . '__' . $this->options['id'];
      $themes[] = $hook . '__' . $display->id;
      if ($display->id != $display->display_plugin) {
        $themes[] = $hook . '__' . $this->view->name  . '__' . $display->display_plugin . '__' . $this->options['id'];
        $themes[] = $hook . '__' . $this->view->name  . '__' . $display->display_plugin;
        $themes[] = $hook . '__' . $display->display_plugin . '__' . $this->options['id'];
        $themes[] = $hook . '__' . $display->display_plugin;
      }
    }
    $themes[] = $hook . '__' . $this->view->name . '__' . $this->options['id'];
    $themes[] = $hook . '__' . $this->view->name;
    $themes[] = $hook . '__' . $this->options['id'];
    $themes[] = $hook;

    return $themes;
  }
}

/**
 * A special handler to take the place of missing or broken handlers.
 */
class views_handler_field_broken extends views_handler_field {
  function ui_name($short = FALSE) {
    return t('Broken/missing handler');
  }

  function ensure_my_table() { /* No table to ensure! */ }
  function query() { /* No query to run */ }
  function options_form(&$form, &$form_state) {
    $form['markup'] = array(
      '#markup' => '<div class="form-item description">' . t('The handler for this item is broken or missing and cannot be used. If a module provided the handler and was disabled, re-enabling the module may restore it. Otherwise, you should probably delete this item.') . '</div>',
    );
  }

  /**
   * Determine if the handler is considered 'broken'
   */
  function broken() { return TRUE; }
}

/**
 * Render a numeric value as a size.
 */
class views_handler_field_file_size extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['file_size_display'] = array('default' => 'formatted');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['file_size_display'] = array(
      '#title' => t('File size display'),
      '#type' => 'select',
      '#options' => array(
        'formatted' => t('Formatted (in KB or MB)'),
        'bytes' => t('Raw bytes'),
      ),
    );
  }

  function render($values) {
    if ($values->{$this->field_alias}) {
      switch ($this->options['file_size_display']) {
        case 'bytes':
          return $values->{$this->field_alias};
        case 'formatted':
        default:
          return format_size($values->{$this->field_alias});
      }
    }
    else {
      return '';
    }
  }
}

/**
 * A handler to run a field through simple XSS filtering
 */
class views_handler_field_xss extends views_handler_field {
  function render($values) {
    $value = $values->{$this->field_alias};
    return filter_xss($value);
  }
}

/**
 * @}
 */
