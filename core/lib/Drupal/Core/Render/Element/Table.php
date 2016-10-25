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
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#header' => array(),
      '#rows' => array(),
      '#empty' => '',
      // Properties for tableselect support.
      '#input' => TRUE,
      '#tree' => TRUE,
      '#tableselect' => FALSE,
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#multiple' => TRUE,
      '#js_select' => TRUE,
      '#process' => array(
        array($class, 'processTable'),
      ),
      '#element_validate' => array(
        array($class, 'validateTable'),
      ),
      // Properties for tabledrag support.
      // The value is a list of arrays that are passed to
      // drupal_attach_tabledrag(). Table::preRenderTable() prepends the HTML ID
      // of the table to each set of options.
      // @see drupal_attach_tabledrag()
      '#tabledrag' => array(),
      // Render properties.
      '#pre_render' => array(
        array($class, 'preRenderTable'),
      ),
      '#theme' => 'table',
    );
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
        $element += array('#default_value' => array());
        $value = array_keys(array_filter($element['#default_value']));
        return array_combine($value, $value);
      }
      else {
        return is_array($input) ? array_combine($input, $input) : array();
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
        $value = is_array($element['#value']) ? $element['#value'] : array();
      }
      // Advanced selection behavior makes no sense for radios.
      else {
        $element['#js_select'] = FALSE;
      }
      // Add a "Select all" checkbox column to the header.
      // @todo D8: Rename into #select_all?
      if ($element['#js_select']) {
        $element['#attached']['library'][] = 'core/drupal.tableselect';
        array_unshift($element['#header'], array('class' => array('select-all')));
      }
      // Add an empty header column for radio buttons or when a "Select all"
      // checkbox is not desired.
      else {
        array_unshift($element['#header'], '');
      }

      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = array();
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
        $element_parents = array_merge($element['#parents'], array($key));

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
              $title = t('Update @title', array('@title' => $title));
            }
          }

          // Prepend the select column to existing columns.
          $row = array('select' => array()) + $row;
          $row['select'] += array(
            '#type' => $element['#multiple'] ? 'checkbox' : 'radio',
            '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $element_parents)),
            // @todo If rows happen to use numeric indexes instead of string keys,
            //   this results in a first row with $key === 0, which is always FALSE.
            '#return_value' => $key,
            '#attributes' => $element['#attributes'],
            '#wrapper_attributes' => array(
              'class' => array('table-select'),
            ),
          );
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
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *
   * @see template_preprocess_table()
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   * @see drupal_attach_tabledrag()
   */
  public static function preRenderTable($element) {
    foreach (Element::children($element) as $first) {
      $row = array('data' => array());
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
        $column = array('data' => &$element[$first][$second]);

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
    Element::setAttributes($element, array('id'));

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

    // If the custom #tabledrag is set and there is a HTML ID, add the table's
    // HTML ID to the options and attach the behavior.
    if (!empty($element['#tabledrag']) && isset($element['#attributes']['id'])) {
      foreach ($element['#tabledrag'] as $options) {
        $options['table_id'] = $element['#attributes']['id'];
        drupal_attach_tabledrag($element, $options);
      }
    }

    return $element;
  }

}
