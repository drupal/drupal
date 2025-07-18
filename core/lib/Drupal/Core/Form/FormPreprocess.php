<?php

namespace Drupal\Core\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Template\Attribute;

/**
 * Initial preprocess callbacks for the form system.
 *
 * @internal
 */
class FormPreprocess {

  /**
   * Prepares variables for form templates.
   *
   * Default template: form.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #action, #method, #attributes, #children.
   */
  public function preprocessForm(array &$variables): void {
    $element = $variables['element'];
    if (isset($element['#action'])) {
      $element['#attributes']['action'] = UrlHelper::stripDangerousProtocols($element['#action']);
    }
    Element::setAttributes($element, ['method', 'id']);
    if (empty($element['#attributes']['accept-charset'])) {
      $element['#attributes']['accept-charset'] = "UTF-8";
    }
    $variables['attributes'] = $element['#attributes'];
    $variables['children'] = $element['#children'];
  }

  /**
   * Returns HTML for a form element.
   *
   * Prepares variables for form element templates.
   *
   * Default template: form-element.html.twig.
   *
   * In addition to the element itself, the DIV contains a label for the element
   * based on the optional #title_display property, and an optional
   * #description.
   *
   * The optional #title_display property can have these values:
   * - before: The label is output before the element. This is the default.
   *   The label includes the #title and the required marker, if #required.
   * - after: The label is output after the element. For example, this is used
   *   for radio and checkbox #type elements. If the #title is empty but the
   *   field is #required, the label will contain only the required marker.
   * - invisible: Labels are critical for screen readers to enable them to
   *   properly navigate through forms but can be visually distracting. This
   *   property hides the label for everyone except screen readers.
   * - attribute: Set the title attribute on the element to create a tooltip
   *   but output no label element. This is supported only for checkboxes
   *   and radios in
   *   \Drupal\Core\Render\Element\CompositeFormElementTrait::preRenderCompositeFormElement().
   *   It is used where a visual label is not needed, such as a table of
   *   checkboxes where the row and column provide the context. The tooltip will
   *   include the title and required marker.
   *
   * If the #title property is not set, then the label and any required marker
   * will not be output, regardless of the #title_display or #required values.
   * This can be useful in cases such as the password_confirm element, which
   * creates children elements that have their own labels and required markers,
   * but the parent element should have neither. Use this carefully because a
   * field without an associated label can cause accessibility challenges.
   *
   * To associate the label with a different field, set the #label_for property
   * to the ID of the desired field.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #title, #title_display, #description, #id, #required,
   *     #children, #type, #name, #label_for.
   */
  public function preprocessFormElement(array &$variables): void {
    $element = &$variables['element'];

    // This function is invoked as theme wrapper, but the rendered form element
    // may not necessarily have been processed by
    // \Drupal::formBuilder()->doBuildForm().
    $element += [
      '#title_display' => 'before',
      '#wrapper_attributes' => [],
      '#label_attributes' => [],
      '#label_for' => NULL,
    ];
    $variables['attributes'] = $element['#wrapper_attributes'];

    // Add element #id for #type 'item'.
    if (isset($element['#markup']) && !empty($element['#id'])) {
      $variables['attributes']['id'] = $element['#id'];
    }

    // Pass elements #type and #name to template.
    if (!empty($element['#type'])) {
      $variables['type'] = $element['#type'];
    }
    if (!empty($element['#name'])) {
      $variables['name'] = $element['#name'];
    }

    // Pass elements disabled status to template.
    $variables['disabled'] = !empty($element['#attributes']['disabled']) ? $element['#attributes']['disabled'] : NULL;

    // Suppress error messages.
    $variables['errors'] = NULL;

    // If #title is not set, we don't display any label.
    if (!isset($element['#title'])) {
      $element['#title_display'] = 'none';
    }

    $variables['title_display'] = $element['#title_display'];

    $variables['prefix'] = $element['#field_prefix'] ?? NULL;
    $variables['suffix'] = $element['#field_suffix'] ?? NULL;

    $variables['description'] = NULL;
    if (!empty($element['#description'])) {
      $variables['description_display'] = $element['#description_display'];
      $description_attributes = [];
      if (!empty($element['#id'])) {
        $description_attributes['id'] = $element['#id'] . '--description';
      }
      $variables['description']['attributes'] = new Attribute($description_attributes);
      $variables['description']['content'] = $element['#description'];
    }

    // Add label_display and label variables to template.
    $variables['label_display'] = $element['#title_display'];
    $variables['label'] = ['#theme' => 'form_element_label'];
    $variables['label'] += array_intersect_key($element, array_flip(['#id', '#required', '#title', '#title_display']));
    $variables['label']['#attributes'] = $element['#label_attributes'];
    if (!empty($element['#label_for'])) {
      $variables['label']['#for'] = $element['#label_for'];
      if (!empty($element['#id'])) {
        $variables['label']['#id'] = $element['#id'] . '--label';
      }
    }

    $variables['children'] = $element['#children'];
  }

  /**
   * Prepares variables for form label templates.
   *
   * Form element labels include the #title and a #required marker. The label is
   * associated with the element itself by the element #id. Labels may appear
   * before or after elements, depending on form-element.html.twig and
   * #title_display.
   *
   * This function will not be called for elements with no labels, depending on
   * #title_display. For elements that have an empty #title and are not
   * required, this function will output no label (''). For required elements
   * that have an empty #title, this will output the required marker alone
   * within the label.
   * The label will use the #id to associate the marker with the field that is
   * required. That is especially important for screen reader users to know
   * which field is required.
   *
   * To associate the label with a different field, set the #for property to the
   * ID of the desired field.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #required, #title, #id, #value, #description, #for.
   */
  public function preprocessFormElementLabel(array &$variables): void {
    $element = $variables['element'];
    // If title and required marker are both empty, output no label.
    if (isset($element['#title']) && $element['#title'] !== '') {
      $variables['title'] = ['#markup' => $element['#title']];
    }

    // Pass elements title_display to template.
    $variables['title_display'] = $element['#title_display'];

    // A #for property of a dedicated #type 'label' element as precedence.
    if (!empty($element['#for'])) {
      $variables['attributes']['for'] = $element['#for'];
      // A custom #id allows the referenced form input element to refer back to
      // the label element; e.g., in the 'aria-labelledby' attribute.
      if (!empty($element['#id'])) {
        $variables['attributes']['id'] = $element['#id'];
      }
    }
    // Otherwise, point to the #id of the form input element.
    elseif (!empty($element['#id'])) {
      $variables['attributes']['for'] = $element['#id'];
    }

    // Pass elements required to template.
    $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;
  }

  /**
   * Prepares variables for input templates.
   *
   * Default template: input.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #attributes.
   */
  public function preprocessInput(array &$variables): void {
    $element = $variables['element'];
    // Remove name attribute if empty, for W3C compliance.
    if (isset($variables['attributes']['name']) && empty((string) $variables['attributes']['name'])) {
      unset($variables['attributes']['name']);
    }
    $variables['children'] = $element['#children'];
  }

  /**
   * Prepares variables for textarea templates.
   *
   * Default template: textarea.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #title, #value, #description, #rows, #cols,
   *     #maxlength, #placeholder, #required, #attributes, #resizable.
   */
  public function preprocessTextarea(array &$variables): void {
    $element = $variables['element'];
    $attributes = ['id', 'name', 'rows', 'cols', 'maxlength', 'placeholder'];
    Element::setAttributes($element, $attributes);
    RenderElementBase::setAttributes($element, ['form-textarea']);
    $variables['wrapper_attributes'] = new Attribute();
    $variables['attributes'] = new Attribute($element['#attributes']);
    $variables['value'] = $element['#value'];
    $resizable = !empty($element['#resizable']) ? $element['#resizable'] : NULL;
    $variables['resizable'] = $resizable;
    if ($resizable) {
      $variables['attributes']->addClass('resize-' . $resizable);
    }
    $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;
  }

  /**
   * Prepares variables for select element templates.
   *
   * Default template: select.html.twig.
   *
   * It is possible to group options together; to do this, change the format of
   * the #options property to an associative array in which the keys are group
   * labels, and the values are associative arrays in the normal #options
   * format.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #title, #value, #options, #description, #extra,
   *     #multiple, #required, #name, #attributes, #size, #sort_options,
   *     #sort_start.
   */
  public function preprocessSelect(array &$variables): void {
    $element = $variables['element'];
    Element::setAttributes($element, ['id', 'name', 'size']);
    RenderElementBase::setAttributes($element, ['form-select']);

    $variables['attributes'] = $element['#attributes'];
    $variables['options'] = form_select_options($element);
  }

  /**
   * Prepares variables for fieldset element templates.
   *
   * Default template: fieldset.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #attributes, #children, #description, #id, #title,
   *     #value.
   */
  public function preprocessFieldset(array &$variables): void {
    $element = $variables['element'];
    Element::setAttributes($element, ['id']);
    RenderElementBase::setAttributes($element);
    $variables['attributes'] = $element['#attributes'] ?? [];
    $variables['prefix'] = $element['#field_prefix'] ?? NULL;
    $variables['suffix'] = $element['#field_suffix'] ?? NULL;
    $variables['title_display'] = $element['#title_display'] ?? NULL;
    $variables['children'] = $element['#children'];
    $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;

    if (isset($element['#title']) && $element['#title'] !== '') {
      $variables['legend']['title'] = ['#markup' => $element['#title']];
    }

    $variables['legend']['attributes'] = new Attribute();
    // Add 'visually-hidden' class to legend span.
    if ($variables['title_display'] == 'invisible') {
      $variables['legend_span']['attributes'] = new Attribute(['class' => ['visually-hidden']]);
    }
    else {
      $variables['legend_span']['attributes'] = new Attribute();
    }

    if (!empty($element['#description'])) {
      $description_id = $element['#attributes']['id'] . '--description';
      $description_attributes['id'] = $description_id;
      $variables['description_display'] = $element['#description_display'];
      if ($element['#description_display'] === 'invisible') {
        $description_attributes['class'][] = 'visually-hidden';
      }
      $description_attributes['data-drupal-field-elements'] = 'description';
      $variables['description']['attributes'] = new Attribute($description_attributes);
      $variables['description']['content'] = $element['#description'];

      // Add the description's id to the fieldset aria attributes.
      $variables['attributes']['aria-describedby'] = $description_id;
    }

    // Suppress error messages.
    $variables['errors'] = NULL;
  }

  /**
   * Prepares variables for details element templates.
   *
   * Default template: details.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #attributes, #children, #description, #required,
   *     #summary_attributes, #title, #value.
   */
  public function preprocessDetails(array &$variables): void {
    $element = $variables['element'];
    $variables['attributes'] = $element['#attributes'];
    $variables['summary_attributes'] = new Attribute($element['#summary_attributes']);
    if (!empty($element['#title'])) {
      $variables['summary_attributes']['role'] = 'button';
      if (!empty($element['#attributes']['id'])) {
        $variables['summary_attributes']['aria-controls'] = $element['#attributes']['id'];
      }
      $variables['summary_attributes']['aria-expanded'] = !empty($element['#attributes']['open']) ? 'true' : 'false';
    }
    $variables['title'] = (!empty($element['#title'])) ? $element['#title'] : '';
    // If the element title is a string, wrap it a render array so that markup
    // will not be escaped (but XSS-filtered).
    if (is_string($variables['title']) && $variables['title'] !== '') {
      $variables['title'] = ['#markup' => $variables['title']];
    }
    $variables['description'] = (!empty($element['#description'])) ? $element['#description'] : '';
    $variables['children'] = (isset($element['#children'])) ? $element['#children'] : '';
    $variables['value'] = (isset($element['#value'])) ? $element['#value'] : '';
    $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;

    // Suppress error messages.
    $variables['errors'] = NULL;
  }

  /**
   * Prepares variables for radios templates.
   *
   * Default template: radios.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #title, #value, #options, #description, #required,
   *     #attributes, #children.
   */
  public function preprocessRadios(array &$variables): void {
    $element = $variables['element'];
    $variables['attributes'] = [];
    if (isset($element['#id'])) {
      $variables['attributes']['id'] = $element['#id'];
    }
    if (isset($element['#attributes']['title'])) {
      $variables['attributes']['title'] = $element['#attributes']['title'];
    }
    $variables['children'] = $element['#children'];
  }

  /**
   * Prepares variables for checkboxes templates.
   *
   * Default template: checkboxes.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #children, #attributes.
   */
  public function preprocessCheckboxes(array &$variables): void {
    $element = $variables['element'];
    $variables['attributes'] = [];
    if (isset($element['#id'])) {
      $variables['attributes']['id'] = $element['#id'];
    }
    if (isset($element['#attributes']['title'])) {
      $variables['attributes']['title'] = $element['#attributes']['title'];
    }
    $variables['children'] = $element['#children'];
  }

  /**
   * Prepares variables for vertical tabs templates.
   *
   * Default template: vertical-tabs.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties and children of
   *     the details element. Properties used: #children.
   */
  public function preprocessVerticalTabs(array &$variables): void {
    $element = $variables['element'];
    $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
  }

}
