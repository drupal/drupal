<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a render element for a table.
 *
 * Note: Although this extends FormElement, it can be used outside the
 * context of a form.
 *
 * Properties:
 * - #header: An array of table header labels.
 * - #rows: An array of the rows to be displayed. Each row is either an array
 *   of cell contents or an array of properties as described in table.html.twig
 *   Alternatively specify the data for the table as child elements of the table
 *   element. Table elements would contain rows elements that would in turn
 *   contain column elements.
 * - #empty: Text to display when no rows are present.
 * - #responsive: Indicates whether to add the drupal.responsive_table library
 *   providing responsive tables.  Defaults to TRUE.
 * - #sticky: Indicates whether to add the drupal.tableheader library that makes
 *   table headers always visible at the top of the page. Defaults to FALSE.
 *
 * Usage example:
 * @code
 * $form['contacts'] = array(
 *   '#type' => 'table',
 *   '#caption' => $this->t('Sample Table'),
 *   '#header' => array($this->t('Name'), $this->t('Phone')),
 * );
 *
 * for ($i = 1; $i <= 4; $i++) {
 *   $form['contacts'][$i]['#attributes'] = array('class' => array('foo', 'baz'));
 *   $form['contacts'][$i]['name'] = array(
 *     '#type' => 'textfield',
 *     '#title' => $this->t('Name'),
 *     '#title_display' => 'invisible',
 *   );
 *
 *   $form['contacts'][$i]['phone'] = array(
 *     '#type' => 'tel',
 *     '#title' => $this->t('Phone'),
 *     '#title_display' => 'invisible',
 *   );
 * }
 *
 * $form['contacts'][]['colspan_example'] = array(
 *   '#plain_text' => 'Colspan Example',
 *   '#wrapper_attributes' => array('colspan' => 2, 'class' => array('foo', 'bar')),
 * );
 * @endcode
 * @see \Drupal\Core\Render\Element\Tableselect
 *
 * @FormElement("table")
 */
class Table extends FormElement {

  /**
   * Counter for tables having draggable rows.
   *
   * @var int
   */
  protected static $tableDragId = 0;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#header' => [],
      '#rows' => [],
      '#empty' => '',
      // Properties for tableselect support.
      '#input' => TRUE,
      '#tree' => TRUE,
      '#tableselect' => FALSE,
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#multiple' => TRUE,
      '#js_select' => TRUE,
      '#process' => [
        [$class, 'processTable'],
      ],
      '#element_validate' => [
        [$class, 'validateTable'],
      ],
      // Properties for tabledrag support.
      // The value is a list of arrays that are passed to
      // Table::attachTabledrag(). Table::preRenderTable() prepends the HTML ID
      // of the table to each set of options.
      // @see Table::attachTabledrag()
      '#tabledrag' => [],
      // Render properties.
      '#pre_render' => [
        [$class, 'preRenderTable'],
      ],
      '#theme' => 'table',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // If #multiple is FALSE, the regular default value of radio buttons is used.
    if (!empty($element['#tableselect']) && !empty($element['#multiple'])) {
      // Contrary to #type 'checkboxes', the default value of checkboxes in a
      // table is built from the array keys (instead of array values) of the
      // #default_value property.
      // @todo D8: Remove this inconsistency.
      if ($input === FALSE) {
        $element += ['#default_value' => []];
        $value = array_keys(array_filter($element['#default_value']));
        return array_combine($value, $value);
      }
      else {
        return is_array($input) ? array_combine($input, $input) : [];
      }
    }
  }

  /**
   * #process callback for #type 'table' to add tableselect support.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   table element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTable(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#tableselect']) {
      if ($element['#multiple']) {
        $value = is_array($element['#value']) ? $element['#value'] : [];
      }
      // Advanced selection behavior makes no sense for radios.
      else {
        $element['#js_select'] = FALSE;
      }
      // Add a "Select all" checkbox column to the header.
      // @todo D8: Rename into #select_all?
      if ($element['#js_select']) {
        $element['#attached']['library'][] = 'core/drupal.tableselect';
        array_unshift($element['#header'], ['class' => ['select-all']]);
      }
      // Add an empty header column for radio buttons or when a "Select all"
      // checkbox is not desired.
      else {
        array_unshift($element['#header'], '');
      }

      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = [];
      }
      // Create a checkbox or radio for each row in a way that the value of the
      // tableselect element behaves as if it had been of #type checkboxes or
      // radios.
      foreach (Element::children($element) as $key) {
        $row = &$element[$key];
        // Prepare the element #parents for the tableselect form element.
        // Their values have to be located in child keys (#tree is ignored),
        // since Table::validateTable() has to be able to validate whether input
        // (for the parent #type 'table' element) has been submitted.
        $element_parents = array_merge($element['#parents'], [$key]);

        // Since the #parents of the tableselect form element will equal the
        // #parents of the row element, prevent FormBuilder from auto-generating
        // an #id for the row element, since
        // \Drupal\Component\Utility\Html::getUniqueId() would automatically
        // append a suffix to the tableselect form element's #id otherwise.
        $row['#id'] = HtmlUtility::getUniqueId('edit-' . implode('-', $element_parents) . '-row');

        // Do not overwrite manually created children.
        if (!isset($row['select'])) {
          // Determine option label; either an assumed 'title' column, or the
          // first available column containing a #title or #markup.
          // @todo Consider to add an optional $element[$key]['#title_key']
          //   defaulting to 'title'?
          unset($label_element);
          $title = NULL;
          if (isset($row['title']['#type']) && $row['title']['#type'] == 'label') {
            $label_element = &$row['title'];
          }
          else {
            if (!empty($row['title']['#title'])) {
              $title = $row['title']['#title'];
            }
            else {
              foreach (Element::children($row) as $column) {
                if (isset($row[$column]['#title'])) {
                  $title = $row[$column]['#title'];
                  break;
                }
                if (isset($row[$column]['#markup'])) {
                  $title = $row[$column]['#markup'];
                  break;
                }
              }
            }
            if (isset($title) && $title !== '') {
              $title = t('Update @title', ['@title' => $title]);
            }
          }

          // Prepend the select column to existing columns.
          $row = ['select' => []] + $row;
          $row['select'] += [
            '#type' => $element['#multiple'] ? 'checkbox' : 'radio',
            '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $element_parents)),
            // @todo If rows happen to use numeric indexes instead of string keys,
            //   this results in a first row with $key === 0, which is always FALSE.
            '#return_value' => $key,
            '#attributes' => $element['#attributes'],
            '#wrapper_attributes' => [
              'class' => ['table-select'],
            ],
          ];
          if ($element['#multiple']) {
            $row['select']['#default_value'] = isset($value[$key]) ? $key : NULL;
            $row['select']['#parents'] = $element_parents;
          }
          else {
            $row['select']['#default_value'] = ($element['#default_value'] == $key ? $key : NULL);
            $row['select']['#parents'] = $element['#parents'];
          }
          if (isset($label_element)) {
            $label_element['#id'] = $row['select']['#id'] . '--label';
            $label_element['#for'] = $row['select']['#id'];
            $row['select']['#attributes']['aria-labelledby'] = $label_element['#id'];
            $row['select']['#title_display'] = 'none';
          }
          else {
            $row['select']['#title'] = $title;
            $row['select']['#title_display'] = 'invisible';
          }
        }
      }
    }

    return $element;
  }

  /**
   * #element_validate callback for #type 'table'.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   table element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateTable(&$element, FormStateInterface $form_state, &$complete_form) {
    // Skip this validation if the button to submit the form does not require
    // selected table row data.
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#tableselect'])) {
      return;
    }
    if ($element['#multiple']) {
      if (!is_array($element['#value']) || !count(array_filter($element['#value']))) {
        $form_state->setError($element, t('No items selected.'));
      }
    }
    elseif (!isset($element['#value']) || $element['#value'] === '') {
      $form_state->setError($element, t('No item selected.'));
    }
  }

  /**
   * #pre_render callback to transform children of an element of #type 'table'.
   *
   * This function converts sub-elements of an element of #type 'table' to be
   * suitable for table.html.twig:
   * - The first level of sub-elements are table rows. Only the #attributes
   *   property is taken into account.
   * - The second level of sub-elements is converted into columns for the
   *   corresponding first-level table row.
   *
   * Simple example usage:
   * @code
   * $form['table'] = array(
   *   '#type' => 'table',
   *   '#header' => array($this->t('Title'), array('data' => $this->t('Operations'), 'colspan' => '1')),
   *   // Optionally, to add tableDrag support:
   *   '#tabledrag' => array(
   *     array(
   *       'action' => 'order',
   *       'relationship' => 'sibling',
   *       'group' => 'thing-weight',
   *     ),
   *   ),
   * );
   * foreach ($things as $row => $thing) {
   *   $form['table'][$row]['#weight'] = $thing['weight'];
   *
   *   $form['table'][$row]['title'] = array(
   *     '#type' => 'textfield',
   *     '#default_value' => $thing['title'],
   *   );
   *
   *   // Optionally, to add tableDrag support:
   *   $form['table'][$row]['#attributes']['class'][] = 'draggable';
   *   $form['table'][$row]['weight'] = array(
   *     '#type' => 'textfield',
   *     '#title' => $this->t('Weight for @title', array('@title' => $thing['title'])),
   *     '#title_display' => 'invisible',
   *     '#size' => 4,
   *     '#default_value' => $thing['weight'],
   *     '#attributes' => array('class' => array('thing-weight')),
   *   );
   *
   *   // The amount of link columns should be identical to the 'colspan'
   *   // attribute in #header above.
   *   $form['table'][$row]['edit'] = array(
   *     '#type' => 'link',
   *     '#title' => $this->t('Edit'),
   *     '#url' => Url::fromRoute('entity.test_entity.edit_form', ['test_entity' => $row]),
   *   );
   * }
   * @endcode
   *
   * @param array $element
   *   A structured array containing two sub-levels of elements. Properties used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     Table::attachTabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *
   * @see template_preprocess_table()
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   * @see \Drupal\Core\Render\Element\Table::attachTabledrag()
   */
  public static function preRenderTable($element) {
    foreach (Element::children($element) as $first) {
      $row = ['data' => []];
      // Apply attributes of first-level elements as table row attributes.
      if (isset($element[$first]['#attributes'])) {
        $row += $element[$first]['#attributes'];
      }
      // Turn second-level elements into table row columns.
      // @todo Do not render a cell for children of #type 'value'.
      // @see https://www.drupal.org/node/1248940
      foreach (Element::children($element[$first]) as $second) {
        // Assign the element by reference, so any potential changes to the
        // original element are taken over.
        $column = ['data' => &$element[$first][$second]];

        // Apply wrapper attributes of second-level elements as table cell
        // attributes.
        if (isset($element[$first][$second]['#wrapper_attributes'])) {
          $column += $element[$first][$second]['#wrapper_attributes'];
        }

        $row['data'][] = $column;
      }
      $element['#rows'][] = $row;
    }

    // Take over $element['#id'] as HTML ID attribute, if not already set.
    Element::setAttributes($element, ['id']);

    // Add sticky headers, if applicable.
    if (count($element['#header']) && $element['#sticky']) {
      $element['#attached']['library'][] = 'core/drupal.tableheader';
      // Add 'sticky-enabled' class to the table to identify it for JS.
      // This is needed to target tables constructed by this function.
      $element['#attributes']['class'][] = 'sticky-enabled';
    }
    // If the table has headers and it should react responsively to columns hidden
    // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
    // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
    if (count($element['#header']) && $element['#responsive']) {
      $element['#attached']['library'][] = 'core/drupal.tableresponsive';
      // Add 'responsive-enabled' class to the table to identify it for JS.
      // This is needed to target tables constructed by this function.
      $element['#attributes']['class'][] = 'responsive-enabled';
    }

    // If the custom #tabledrag is set and there is an HTML ID, add the table's
    // HTML ID to the options and attach the behavior.
    if (!empty($element['#tabledrag']) && isset($element['#attributes']['id'])) {
      $element['#attributes']['class'][] = 'tabledrag-enabled';
      $element['#attributes']['data-drupal-tabledrag-id'] = $element['#attributes']['id'];
      foreach ($element['#tabledrag'] as $options) {
        $options['table_id'] = $element['#attributes']['id'];
        static::attachTabledrag($element, $options);
      }
    }

    return $element;
  }

  /**
   * Assists in attaching the tableDrag JavaScript behavior to a themed table.
   *
   * Draggable tables should be used wherever an outline or list of sortable
   * items needs to be arranged by an end-user. Draggable tables are very
   * flexible and can manipulate the value of form elements placed within
   * individual columns.
   *
   * To set up a table to use drag and drop in place of weight select-lists or
   * in place of a form that contains parent relationships, the form must be
   * themed into a table. The table must have an ID attribute set and it
   * maybe set as follows:
   * @code
   * $table = [
   *   '#type' => 'table',
   *   '#header' => $header,
   *   '#rows' => $rows,
   *   '#attributes' => [
   *     'id' => 'my-module-table',
   *   ],
   * ];
   * return \Drupal::service('renderer')->render($table);
   * @endcode
   *
   * In the theme function for the form, a special class must be added to each
   * form element within the same column, "grouping" them together.
   *
   * In a situation where a single weight column is being sorted in the table,
   * the classes could be added like this (in the theme function):
   * @code
   * $form['my_elements'][$delta]['weight']['#attributes']['class'] = ['my-elements-weight'];
   * @endcode
   *
   * Each row of the table must also have a class of "draggable" in order to
   * enable the drag handles:
   * @code
   * $row = [...];
   * $rows[] = [
   *   'data' => $row,
   *   'class' => ['draggable'],
   * ];
   * @endcode
   *
   * When tree relationships are present, the two additional classes
   * 'tabledrag-leaf' and 'tabledrag-root' can be used to refine the behavior:
   * - Rows with the 'tabledrag-leaf' class cannot have child rows.
   * - Rows with the 'tabledrag-root' class cannot be nested under a parent row.
   *
   * Calling Table::attachTabledrag() would then be written as such:
   * @code
   * Table::attachTabledrag('my-module-table', [
   *   'action' => 'order',
   *   'relationship' => 'sibling',
   *   'group' => 'my-elements-weight',
   * ];
   * @endcode
   *
   * In a more complex case where there are several groups in one column (such
   * as the block regions on the admin/structure/block page), a separate
   * subgroup class must also be added to differentiate the groups.
   * @code
   * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = ['my-elements-weight', 'my-elements-weight-' . $region];
   * @endcode
   *
   * The 'group' option is still 'my-element-weight', and the additional
   * 'subgroup' option will be passed in as 'my-elements-weight-' . $region.
   * This also means that you'll need to call Table::attachTabledrag() once for
   * every region added.
   *
   * @code
   * foreach ($regions as $region) {
   *   Table::attachTabledrag('my-module-table', [
   *     'action' => 'order',
   *     'relationship' => 'sibling',
   *     'group' => 'my-elements-weight',
   *     'subgroup' => 'my-elements-weight-' . $region,
   *   ]);
   * }
   * @endcode
   *
   * In a situation where tree relationships are present, adding multiple
   * subgroups is not necessary, because the table will contain indentations
   * that provide enough information about the sibling and parent relationships.
   * See MenuForm::BuildOverviewForm for an example creating a table containing
   * parent relationships.
   *
   * @param array $element
   *   A form element to attach the tableDrag behavior to.
   * @param array $options
   *   These options are used to generate JavaScript settings necessary to
   *   configure the tableDrag behavior appropriately for this particular table.
   *   An associative array containing the following keys:
   *   - 'table_id': String containing the target table's id attribute. If the
   *     table does not have an id, one will need to be set, such as
   *     <table id="my-module-table">.
   *   - 'action': String describing the action to be done on the form item.
   *      Either 'match' 'depth', or 'order':
   *     - 'match' is typically used for parent relationships.
   *     - 'order' is typically used to set weights on other form elements with
   *       the same group.
   *     - 'depth' updates the target element with the current indentation.
   *   - 'relationship': String describing where the "action" option should be
   *     performed. Either 'parent', 'sibling', 'group', or 'self':
   *     - 'parent' will only look for fields up the tree.
   *     - 'sibling' will look for fields in the same group in rows above and
   *       below it.
   *     - 'self' affects the dragged row itself.
   *     - 'group' affects the dragged row, plus any children below it (the
   *       entire dragged group).
   *   - 'group': A class name applied on all related form elements for this
   *     action.
   *   - 'subgroup': (optional) If the group has several subgroups within it,
   *     this string should contain the class name identifying fields in the
   *     same subgroup.
   *   - 'source': (optional) If the $action is 'match', this string should
   *     contain the classname identifying what field will be used as the source
   *     value when matching the value in $subgroup.
   *   - 'hidden': (optional) The column containing the field elements may be
   *     entirely hidden from view dynamically when the JavaScript is loaded.
   *     Set to FALSE if the column should not be hidden.
   *   - 'limit': (optional) Limit the maximum amount of parenting in this
   *     table.
   *
   * @see \Drupal\menu_ui\MenuForm::buildOverviewForm()
   */
  public static function attachTabledrag(array &$element, array $options): void {
    // Add default values to elements.
    $options += [
      'subgroup' => NULL,
      'source' => NULL,
      'hidden' => TRUE,
      'limit' => 0,
    ];

    $group = $options['group'];

    $tabledrag_id = self::$tableDragId++;

    // If a subgroup or source isn't set, assume it is the same as the group.
    $target = $options['subgroup'] ?? $group;
    $source = $options['source'] ?? $target;
    $element['#attached']['drupalSettings']['tableDrag'][$options['table_id']][$group][$tabledrag_id] = [
      'target' => $target,
      'source' => $source,
      'relationship' => $options['relationship'],
      'action' => $options['action'],
      'hidden' => $options['hidden'],
      'limit' => $options['limit'],
    ];

    $element['#attached']['library'][] = 'core/drupal.tabledrag';
  }

}
