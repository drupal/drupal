<?php

/**
 * @file
 * Functions for use with Drupal's Ajax framework.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * @defgroup ajax Ajax API
 * @{
 * Overview for Drupal's Ajax API.
 *
 * @section sec_overview Overview of Ajax
 * Ajax is the process of dynamically updating parts of a page's HTML based on
 * data from the server. When a specified event takes place, a PHP callback is
 * triggered, which performs server-side logic and may return updated markup or
 * JavaScript commands to run. After the return, the browser runs the JavaScript
 * or updates the markup on the fly, with no full page refresh necessary.
 *
 * Many different events can trigger Ajax responses, including:
 * - Clicking a button
 * - Pressing a key
 * - Moving the mouse
 *
 * @section sec_framework Ajax responses in forms
 * Forms that use the Drupal Form API (see the
 * @link form_api Form API topic @endlink for more information about forms) can
 * trigger AJAX responses. Here is an outline of the steps:
 * - Add property '#ajax' to a form element in your form array, to trigger an
 *   Ajax response.
 * - Write an Ajax callback to process the input and respond.
 * See sections below for details on these two steps.
 *
 * @subsection sub_form Adding Ajax triggers to a form
 * As an example of adding Ajax triggers to a form, consider editing a date
 * format, where the user is provided with a sample of the generated date output
 * as they type. To accomplish this, typing in the text field should trigger an
 * Ajax response. This is done in the text field form array element
 * in \Drupal\config_translation\FormElement\DateFormat::getFormElement():
 * @code
 * '#ajax' => array(
 *   'callback' => 'Drupal\config_translation\FormElement\DateFormat::ajaxSample',
 *   'event' => 'keyup',
 *   'progress' => array(
 *     'type' => 'throbber',
 *     'message' => NULL,
 *   ),
 * ),
 * @endcode
 *
 * As you can see from this example, the #ajax property for a form element is
 * an array. Here are the details of its elements, all of which are optional:
 * - callback: The callback to invoke to handle the server side of the
 *   Ajax event. More information on callbacks is below in @ref sub_callback.
 * - path: The URL path to use for the request. If omitted, defaults to
 *   'system/ajax', which invokes the default Drupal Ajax processing (this will
 *   call the callback supplied in the 'callback' element). If you supply a
 *   path, you must set up a routing entry to handle the request yourself and
 *   return output described in @ref sub_callback below. See the
 *   @link menu Routing topic @endlink for more information on routing.
 * - wrapper: The HTML 'id' attribute of the area where the content returned by
 *   the callback should be placed. Note that callbacks have a choice of
 *   returning content or JavaScript commands; 'wrapper' is used for content
 *   returns.
 * - method: The jQuery method for placing the new content (used with
 *   'wrapper'). Valid options are 'replaceWith' (default), 'append', 'prepend',
 *   'before', 'after', or 'html'. See
 *   http://api.jquery.com/category/manipulation/ for more information on these
 *   methods.
 * - effect: The jQuery effect to use when placing the new HTML (used with
 *   'wrapper'). Valid options are 'none' (default), 'slide', or 'fade'.
 * - speed: The effect speed to use (used with 'effect' and 'wrapper'). Valid
 *   options are 'slow' (default), 'fast', or the number of milliseconds the
 *   effect should run.
 * - event: The JavaScript event to respond to. This is selected automatically
 *   for the type of form element; provide a value to override the default.
 * - prevent: A JavaScript event to prevent when the event is triggered. For
 *   example, if you use event 'mousedown' on a button, you might want to
 *   prevent 'click' events from also being triggered.
 * - progress: An array indicating how to show Ajax processing progress. Can
 *   contain one or more of these elements:
 *   - type: Type of indicator: 'throbber' (default) or 'bar'.
 *   - message: Translated message to display.
 *   - url: For a bar progress indicator, URL path for determining progress.
 *   - interval: For a bar progress indicator, how often to update it.
 *
 * @subsection sub_callback Setting up a callback to process Ajax
 * Once you have set up your form to trigger an Ajax response (see @ref sub_form
 * above), you need to write some PHP code to process the response. If you use
 * 'path' in your Ajax set-up, your route controller will be triggered with only
 * the information you provide in the URL. If you use 'callback', your callback
 * method is a function, which will receive the $form and $form_state from the
 * triggering form. You can use $form_state to get information about the
 * data the user has entered into the form. For instance, in the above example
 * for the date format preview,
 * \Drupal\config_translation\FormElement\DateFormat\ajaxSample() does this to
 * get the format string entered by the user:
 * @code
 * $format_value = \Drupal\Component\Utility\NestedArray::getValue(
 *   $form_state->getValues(),
 *   $form_state->getTriggeringElement()['#array_parents']);
 * @endcode
 *
 * Once you have processed the input, you have your choice of returning HTML
 * markup or a set of Ajax commands. If you choose to return HTML markup, you
 * can return it as a string or a renderable array, and it will be placed in
 * the defined 'wrapper' element (see documentation above in @ref sub_form).
 * In addition, any messages returned by drupal_get_messages(), themed as in
 * status-messages.html.twig, will be prepended.
 *
 * To return commands, you need to set up an object of class
 * \Drupal\Core\Ajax\AjaxResponse, and then use its addCommand() method to add
 * individual commands to it. In the date format preview example, the format
 * output is calculated, and then it is returned as replacement markup for a div
 * like this:
 * @code
 * $response = new AjaxResponse();
 * $response->addCommand(new ReplaceCommand(
 *   '#edit-date-format-suffix',
 *   '<small id="edit-date-format-suffix">' . $format . '</small>'));
 * return $response;
 * @endcode
 *
 * The individual commands that you can return implement interface
 * \Drupal\Core\Ajax\CommandInterface. Available commands provide the ability
 * to pop up alerts, manipulate text and markup in various ways, redirect
 * to a new URL, and the generic \Drupal\Core\Ajax\InvokeCommand, which
 * invokes an arbitrary jQuery commnd.
 *
 * As noted above, status messages are prepended automatically if you use the
 * 'wrapper' method and return HTML markup. This is not the case if you return
 * commands, but if you would like to show status messages, you can add
 * @code
 * array('#theme' => 'status_messages')
 * @endcode
 * to a render array, use drupal_render() to render it, and add a command to
 * place the messages in an appropriate location.
 *
 * @section sec_other Other methods for triggering Ajax
 * Here are some additional methods you can use to trigger Ajax responses in
 * Drupal:
 * - Add class 'use-ajax' to a link. The link will be loaded using an Ajax
 *   call. When using this method, the href of the link can contain '/nojs/' as
 *   part of the path. When the Ajax JavaScript processes the page, it will
 *   convert this to '/ajax/'. The server is then able to easily tell if this
 *   request was made through an actual Ajax request or in a degraded state, and
 *   respond appropriately.
 * - Add class 'use-ajax-submit' to a submit button in a form. The form will
 *   then be submitted via Ajax to the path specified in the #action.  Like the
 *   ajax-submit class on links, this path will have '/nojs/' replaced with
 *   '/ajax/' so that the submit handler can tell if the form was submitted in a
 *   degraded state or not.
 * - Add property '#autocomplete_route_name' to a text field in a form. The
 *   route controller for this route must return an array of options for
 *   autocomplete, as a \Symfony\Component\HttpFoundation\JsonResponse object.
 *   See the @link menu Routing topic @endlink for more information about
 *   routing.
 */

/**
 * Form element processing handler for the #ajax form property.
 *
 * @deprecated Use \Drupal\Core\Render\Element\RenderElement::processAjaxForm().
 */
function ajax_process_form(&$element, FormStateInterface $form_state, &$complete_form) {
  return Element\RenderElement::processAjaxForm($element, $form_state, $complete_form);
}

/**
 * Adds Ajax information about an element to communicate with JavaScript.
 *
 * If #ajax['path'] is set on an element, this additional JavaScript is added
 * to the page header to attach the Ajax behaviors. See ajax.js for more
 * information.
 *
 * @param $element
 *   An associative array containing the properties of the element.
 *   Properties used:
 *   - #ajax['event']
 *   - #ajax['prevent']
 *   - #ajax['path']
 *   - #ajax['options']
 *   - #ajax['wrapper']
 *   - #ajax['parameters']
 *   - #ajax['effect']
 *   - #ajax['accepts']
 *
 * @return
 *   The processed element with the necessary JavaScript attached to it.
 */
function ajax_pre_render_element($element) {
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
        // @see http://drupal.org/node/216059
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

    // Assign default settings. When 'path' is set to NULL, ajax.js submits the
    // Ajax request to the same URL as the form or link destination is for
    // someone with JavaScript disabled. This is generally preferred as a way to
    // ensure consistent server processing for js and no-js users, and Drupal's
    // content negotiation takes care of formatting the response appropriately.
    // However, 'path' and 'options' may be set when wanting server processing
    // to be substantially different for a JavaScript triggered submission.
    // One such substantial difference is form elements that use
    // #ajax['callback'] for determining which part of the form needs
    // re-rendering. For that, we have a special 'system/ajax' route.
    $settings += array(
      'path' => isset($settings['callback']) ? 'system/ajax' : NULL,
      'options' => array(),
      'accepts' => 'application/vnd.drupal-ajax'
    );

    // @todo Legacy support. Remove in Drupal 8.
    if (isset($settings['method']) && $settings['method'] == 'replace') {
      $settings['method'] = 'replaceWith';
    }

    // Change path to URL.
    $settings['url'] = isset($settings['path']) ? url($settings['path'], $settings['options']) : NULL;
    unset($settings['path'], $settings['options']);

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
    if (isset($settings['progress']['path'])) {
      $settings['progress']['url'] = url($settings['progress']['path']);
      unset($settings['progress']['path']);
    }

    $element['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('ajax' => array($element['#id'] => $settings)),
    );

    // Indicate that Ajax processing was successful.
    $element['#ajax_processed'] = TRUE;
  }
  return $element;
}

/**
 * @} End of "defgroup ajax".
 */
