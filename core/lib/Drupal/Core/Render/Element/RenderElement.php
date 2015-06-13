<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\RenderElement.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides a base class for render element plugins.
 *
 * @see \Drupal\Core\Render\Annotation\RenderElement
 * @see \Drupal\Core\Render\ElementInterface
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see plugin_api
 *
 * @ingroup theme_render
 */
abstract class RenderElement extends PluginBase implements ElementInterface {

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = array()) {
    if (!empty($class)) {
      if (!isset($element['#attributes']['class'])) {
        $element['#attributes']['class'] = array();
      }
      $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $class);
    }
    // This function is invoked from form element theme functions, but the
    // rendered form element may not necessarily have been processed by
    // \Drupal::formBuilder()->doBuildForm().
    if (!empty($element['#required'])) {
      $element['#attributes']['class'][] = 'required';
      $element['#attributes']['required'] = 'required';
      $element['#attributes']['aria-required'] = 'true';
    }
    if (isset($element['#parents']) && isset($element['#errors']) && !empty($element['#validated'])) {
      $element['#attributes']['class'][] = 'error';
      $element['#attributes']['aria-invalid'] = 'true';
    }
  }

  /**
   * Adds members of this group as actual elements for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with all group members.
   */
  public static function preRenderGroup($element) {
    // The element may be rendered outside of a Form API context.
    if (!isset($element['#parents']) || !isset($element['#groups'])) {
      return $element;
    }

    // Inject group member elements belonging to this group.
    $parents = implode('][', $element['#parents']);
    $children = Element::children($element['#groups'][$parents]);
    if (!empty($children)) {
      foreach ($children as $key) {
        // Break references and indicate that the element should be rendered as
        // group member.
        $child = (array) $element['#groups'][$parents][$key];
        $child['#group_details'] = TRUE;
        // Inject the element as new child element.
        $element[] = $child;

        $sort = TRUE;
      }
      // Re-sort the element's children if we injected group member elements.
      if (isset($sort)) {
        $element['#sorted'] = FALSE;
      }
    }

    if (isset($element['#group'])) {
      // Contains form element summary functionalities.
      $element['#attached']['library'][] = 'core/drupal.form';

      $group = $element['#group'];
      // If this element belongs to a group, but the group-holding element does
      // not exist, we need to render it (at its original location).
      if (!isset($element['#groups'][$group]['#group_exists'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // If we injected this element into the group, then we want to render it.
      elseif (!empty($element['#group_details'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // Otherwise, this element belongs to a group and the group exists, so we do
      // not render it.
      elseif (Element::children($element['#groups'][$group])) {
        $element['#printed'] = TRUE;
      }
    }

    return $element;
  }

  /**
   * Form element processing handler for the #ajax form property.
   *
   * This method is useful for non-input elements that can be used in and
   * outside the context of a form.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see self::preRenderAjaxForm()
   */
  public static function processAjaxForm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = static::preRenderAjaxForm($element);

    // If the element was processed as an #ajax element, and a custom URL was
    // provided, set the form to be cached.
    if (!empty($element['#ajax_processed']) && !empty($element['#ajax']['url'])) {
      $form_state->setCached();
    }
    return $element;
  }

  /**
   * Adds Ajax information about an element to communicate with JavaScript.
   *
   * If #ajax is set on an element, this additional JavaScript is added to the
   * page header to attach the Ajax behaviors. See ajax.js for more information.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used:
   *   - #ajax['event']
   *   - #ajax['prevent']
   *   - #ajax['url']
   *   - #ajax['callback']
   *   - #ajax['options']
   *   - #ajax['wrapper']
   *   - #ajax['parameters']
   *   - #ajax['effect']
   *   - #ajax['accepts']
   *
   * @return array
   *   The processed element with the necessary JavaScript attached to it.
   */
  public static function preRenderAjaxForm($element) {
    // Skip already processed elements.
    if (isset($element['#ajax_processed'])) {
      return $element;
    }
    // Initialize #ajax_processed, so we do not process this element again.
    $element['#ajax_processed'] = FALSE;

    // Nothing to do if there are no Ajax settings.
    if (empty($element['#ajax'])) {
      return $element;
    }

    // Add a reasonable default event handler if none was specified.
    if (isset($element['#ajax']) && !isset($element['#ajax']['event'])) {
      switch ($element['#type']) {
        case 'submit':
        case 'button':
        case 'image_button':
          // Pressing the ENTER key within a textfield triggers the click event of
          // the form's first submit button. Triggering Ajax in this situation
          // leads to problems, like breaking autocomplete textfields, so we bind
          // to mousedown instead of click.
          // @see https://www.drupal.org/node/216059
          $element['#ajax']['event'] = 'mousedown';
          // Retain keyboard accessibility by setting 'keypress'. This causes
          // ajax.js to trigger 'event' when SPACE or ENTER are pressed while the
          // button has focus.
          $element['#ajax']['keypress'] = TRUE;
          // Binding to mousedown rather than click means that it is possible to
          // trigger a click by pressing the mouse, holding the mouse button down
          // until the Ajax request is complete and the button is re-enabled, and
          // then releasing the mouse button. Set 'prevent' so that ajax.js binds
          // an additional handler to prevent such a click from triggering a
          // non-Ajax form submission. This also prevents a textfield's ENTER
          // press triggering this button's non-Ajax form submission behavior.
          if (!isset($element['#ajax']['prevent'])) {
            $element['#ajax']['prevent'] = 'click';
          }
          break;

        case 'password':
        case 'textfield':
        case 'number':
        case 'tel':
        case 'textarea':
          $element['#ajax']['event'] = 'blur';
          break;

        case 'radio':
        case 'checkbox':
        case 'select':
          $element['#ajax']['event'] = 'change';
          break;

        case 'link':
          $element['#ajax']['event'] = 'click';
          break;

        default:
          return $element;
      }
    }

    // Attach JavaScript settings to the element.
    if (isset($element['#ajax']['event'])) {
      $element['#attached']['library'][] = 'core/jquery.form';
      $element['#attached']['library'][] = 'core/drupal.ajax';

      $settings = $element['#ajax'];

      // Assign default settings. When 'url' is set to NULL, ajax.js submits the
      // Ajax request to the same URL as the form or link destination is for
      // someone with JavaScript disabled. This is generally preferred as a way to
      // ensure consistent server processing for js and no-js users, and Drupal's
      // content negotiation takes care of formatting the response appropriately.
      // However, 'url' and 'options' may be set when wanting server processing
      // to be substantially different for a JavaScript triggered submission.
      // One such substantial difference is form elements that use
      // #ajax['callback'] for determining which part of the form needs
      // re-rendering. For that, we have a special 'system.ajax' route which
      // must be manually set.
      $settings += [
        'url' => NULL,
        'options' => ['query' => []],
        'dialogType' => 'ajax',
      ];
      if (array_key_exists('callback', $settings) && !isset($settings['url'])) {
        $settings['url'] = Url::fromRoute('<current>');
        // Add all the current query parameters in order to ensure that we build
        // the same form on the AJAX POST requests. For example,
        // \Drupal\user\AccountForm takes query parameters into account in order
        // to hide the password field dynamically.
        $settings['options']['query'] += \Drupal::request()->query->all();
        $settings['options']['query'][FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
      }

      // @todo Legacy support. Remove in Drupal 8.
      if (isset($settings['method']) && $settings['method'] == 'replace') {
        $settings['method'] = 'replaceWith';
      }

      // Convert \Drupal\Core\Url object to string.
      if (isset($settings['url']) && $settings['url'] instanceof Url) {
        $settings['url'] = $settings['url']->setOptions($settings['options'])->toString();
      }
      else {
        $settings['url'] = NULL;
      }
      unset($settings['options']);

      // Add special data to $settings['submit'] so that when this element
      // triggers an Ajax submission, Drupal's form processing can determine which
      // element triggered it.
      // @see _form_element_triggered_scripted_submission()
      if (isset($settings['trigger_as'])) {
        // An element can add a 'trigger_as' key within #ajax to make the element
        // submit as though another one (for example, a non-button can use this
        // to submit the form as though a button were clicked). When using this,
        // the 'name' key is always required to identify the element to trigger
        // as. The 'value' key is optional, and only needed when multiple elements
        // share the same name, which is commonly the case for buttons.
        $settings['submit']['_triggering_element_name'] = $settings['trigger_as']['name'];
        if (isset($settings['trigger_as']['value'])) {
          $settings['submit']['_triggering_element_value'] = $settings['trigger_as']['value'];
        }
        unset($settings['trigger_as']);
      }
      elseif (isset($element['#name'])) {
        // Most of the time, elements can submit as themselves, in which case the
        // 'trigger_as' key isn't needed, and the element's name is used.
        $settings['submit']['_triggering_element_name'] = $element['#name'];
        // If the element is a (non-image) button, its name may not identify it
        // uniquely, in which case a match on value is also needed.
        // @see _form_button_was_clicked()
        if (!empty($element['#is_button']) && empty($element['#has_garbage_value'])) {
          $settings['submit']['_triggering_element_value'] = $element['#value'];
        }
      }

      // Convert a simple #ajax['progress'] string into an array.
      if (isset($settings['progress']) && is_string($settings['progress'])) {
        $settings['progress'] = array('type' => $settings['progress']);
      }
      // Change progress path to a full URL.
      if (isset($settings['progress']['url']) && $settings['progress']['url'] instanceof Url) {
        $settings['progress']['url'] = $settings['progress']['url']->toString();
      }

      $element['#attached']['drupalSettings']['ajax'][$element['#id']] = $settings;

      // Indicate that Ajax processing was successful.
      $element['#ajax_processed'] = TRUE;
    }
    return $element;
  }

  /**
   * Arranges elements into groups.
   *
   * This method is useful for non-input elements that can be used in and
   * outside the context of a form.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processGroup(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = implode('][', $element['#parents']);

    // Each details element forms a new group. The #type 'vertical_tabs' basically
    // only injects a new details element.
    $groups = &$form_state->getGroups();
    $groups[$parents]['#group_exists'] = TRUE;
    $element['#groups'] = &$groups;

    // Process vertical tabs group member details elements.
    if (isset($element['#group'])) {
      // Add this details element to the defined group (by reference).
      $group = $element['#group'];
      $groups[$group][] = &$element;
    }

    return $element;
  }

}
