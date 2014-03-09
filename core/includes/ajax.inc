<?php

/**
 * @file
 * Functions for use with Drupal's Ajax framework.
 */

/**
 * @defgroup ajax Ajax framework
 * @{
 * Functions for Drupal's Ajax framework.
 *
 * Drupal's Ajax framework is used to dynamically update parts of a page's HTML
 * based on data from the server. Upon a specified event, such as a button
 * click, a callback function is triggered which performs server-side logic and
 * may return updated markup, which is then replaced on-the-fly with no page
 * refresh necessary.
 *
 * This framework creates a PHP macro language that allows the server to
 * instruct JavaScript to perform actions on the client browser. When using
 * forms, it can be used with the #ajax property.
 * The #ajax property can be used to bind events to the Ajax framework. By
 * default, #ajax uses 'system/ajax' as its path for submission and thus calls
 * \Drupal\system\FormAjaxController::content() and a defined #ajax['callback']
 * function. However, you may optionally specify a different path to request or
 * a different callback function to invoke, which can return updated HTML or can
 * also return a richer set of
 * @link ajax_commands Ajax framework commands @endlink.
 *
 * Standard form handling is as follows:
 *   - A form element has a #ajax property that includes #ajax['callback'] and
 *     omits #ajax['path']. See below about using #ajax['path'] to implement
 *     advanced use-cases that require something other than standard form
 *     handling.
 *   - On the specified element, Ajax processing is triggered by a change to
 *     that element.
 *   - The browser submits an HTTP POST request to the 'system/ajax' Drupal
 *     path.
 *   - The controller for the route '/system/ajax',
 *     \Drupal\system\FormAjaxController::content(), calls drupal_process_form()
 *     to process the form submission and rebuild the form if necessary. The
 *     form is processed in much the same way as if it were submitted without
 *     Ajax, with the same #process functions and validation and submission
 *     handlers called in either case, making it easy to create Ajax-enabled
 *     forms that degrade gracefully when JavaScript is disabled.
 *   - After form processing is complete,
 *     \Drupal\system\FormAjaxController::content() calls the function named by
 *     #ajax['callback'], which returns the form element that has been updated
 *     and needs to be returned to the browser, or alternatively, an array of
 *     custom Ajax commands.
 *   - The array is serialized using
 *     \Drupal\Core\Ajax\AjaxResponse::ajaxRender() and sent to the browser.
 *   - The browser unserializes the returned JSON string into an array of
 *     command objects and executes each command, resulting in the old page
 *     content within and including the HTML element specified by
 *     #ajax['wrapper'] being replaced by the new content returned by
 *     #ajax['callback'], using a JavaScript animation effect specified by
 *     #ajax['effect'].
 *
 * A simple example of basic Ajax use from the
 * @link http://drupal.org/project/examples Examples module @endlink follows:
 * @code
 * function main_page() {
 *   return drupal_get_form('ajax_example_simplest');
 * }
 *
 * function ajax_example_simplest($form, &$form_state) {
 *   $form = array();
 *   $form['changethis'] = array(
 *     '#type' => 'select',
 *     '#options' => array(
 *       'one' => 'one',
 *       'two' => 'two',
 *       'three' => 'three',
 *     ),
 *     '#ajax' => array(
 *       'callback' => 'ajax_example_simplest_callback',
 *       'wrapper' => 'replace_textfield_div',
 *      ),
 *   );
 *
 *   // This entire form element will be replaced with an updated value.
 *   $form['replace_textfield'] = array(
 *     '#type' => 'textfield',
 *     '#title' => t("The default value will be changed"),
 *     '#description' => t("Say something about why you chose") . "'" .
 *       (!empty($form_state['values']['changethis'])
 *       ? $form_state['values']['changethis'] : t("Not changed yet")) . "'",
 *     '#prefix' => '<div id="replace_textfield_div">',
 *     '#suffix' => '</div>',
 *   );
 *   return $form;
 * }
 *
 * function ajax_example_simplest_callback($form, $form_state) {
 *   // The form has already been submitted and updated. We can return the replaced
 *   // item as it is.
 *   return $form['replace_textfield'];
 * }
 * @endcode
 *
 * In the above example, the 'changethis' element is Ajax-enabled. The default
 * #ajax['event'] is 'change', so when the 'changethis' element changes,
 * an Ajax call is made. The form is submitted and reprocessed, and then the
 * callback is called. In this case, the form has been automatically
 * built changing $form['replace_textfield']['#description'], so the callback
 * just returns that part of the form.
 *
 * To implement Ajax handling in a form, add '#ajax' to the form
 * definition of a field. That field will trigger an Ajax event when it is
 * clicked (or changed, depending on the kind of field). #ajax supports
 * the following parameters (either 'path' or 'callback' is required at least):
 * - #ajax['callback']: The callback to invoke to handle the server side of the
 *   Ajax event, which will receive a $form and $form_state as arguments, and
 *   returns a renderable array (most often a form or form fragment), an HTML
 *   string, or an array of Ajax commands. If returning a renderable array or
 *   a string, the value will replace the original element named in
 *   #ajax['wrapper'], and
 *   theme_status_messages()
 *   will be prepended to that
 *   element. (If the status messages are not wanted, return an array
 *   of Ajax commands instead.)
 *   #ajax['wrapper']. If an array of Ajax commands is returned, it will be
 *   executed by the calling code.
 * - #ajax['path']: The menu path to use for the request. This is often omitted
 *   and the default is used. This path should map
 *   to a controller that returns data using
 *   \Drupal\Core\Ajax\AjaxResponse::ajaxRender(). Defaults to 'system/ajax',
 *   which invokes \Drupal\system\FormAjaxController::content(), eventually
 *   calling the function named in #ajax['callback']. If you use a custom path,
 *   you must set up the menu entry and handle the entire callback in your own
 *   code.
 * - #ajax['wrapper']: The CSS ID of the area to be replaced by the content
 *   returned by the #ajax['callback'] function. The content returned from
 *   the callback will replace the entire element named by #ajax['wrapper'].
 *   The wrapper is usually created using #prefix and #suffix properties in the
 *   form. Note that this is the wrapper ID, not a CSS selector. So to replace
 *   the element referred to by the CSS selector #some-selector on the page,
 *   use #ajax['wrapper'] = 'some-selector', not '#some-selector'.
 * - #ajax['effect']: The jQuery effect to use when placing the new HTML.
 *   Defaults to no effect. Valid options are 'none', 'slide', or 'fade'.
 * - #ajax['speed']: The effect speed to use. Defaults to 'slow'. May be
 *   'slow', 'fast' or a number in milliseconds which represents the length
 *   of time the effect should run.
 * - #ajax['event']: The JavaScript event to respond to. This is normally
 *   selected automatically for the type of form widget being used, and
 *   is only needed if you need to override the default behavior.
 * - #ajax['prevent']: A JavaScript event to prevent when 'event' is triggered.
 *   Defaults to 'click' for #ajax on #type 'submit', 'button', and
 *   'image_button'. Multiple events may be specified separated by spaces.
 *   For example, when binding #ajax behaviors to form buttons, pressing the
 *   ENTER key within a textfield triggers the 'click' event of the form's first
 *   submit button. Triggering Ajax in this situation leads to problems, like
 *   breaking autocomplete textfields. Because of that, Ajax behaviors are bound
 *   to the 'mousedown' event on form buttons by default. However, binding to
 *   'mousedown' rather than 'click' means that it is possible to trigger a
 *   click by pressing the mouse, holding the mouse button down until the Ajax
 *   request is complete and the button is re-enabled, and then releasing the
 *   mouse button. For this case, 'prevent' can be set to 'click', so an
 *   additional event handler is bound to prevent such a click from triggering a
 *   non-Ajax form submission. This also prevents a textfield's ENTER press
 *   triggering a button's non-Ajax form submission behavior.
 * - #ajax['method']: The jQuery method to use to place the new HTML.
 *   Defaults to 'replaceWith'. May be: 'replaceWith', 'append', 'prepend',
 *   'before', 'after', or 'html'. See the
 *   @link http://api.jquery.com/category/manipulation/ jQuery manipulators documentation @endlink
 *   for more information on these methods.
 * - #ajax['progress']: Choose either a throbber or progress bar that is
 *   displayed while awaiting a response from the callback, and add an optional
 *   message. Possible keys: 'type', 'message', 'url', 'interval'.
 *   More information is available in the
 *   @link forms_api_reference.html Form API Reference @endlink
 *
 * In addition to using Form API for doing in-form modification, Ajax may be
 * enabled by adding classes to buttons and links. By adding the 'use-ajax'
 * class to a link, the link will be loaded via an Ajax call. When using this
 * method, the href of the link can contain '/nojs/' as part of the path. When
 * the Ajax framework makes the request, it will convert this to '/ajax/'.
 * The server is then able to easily tell if this request was made through an
 * actual Ajax request or in a degraded state, and respond appropriately.
 *
 * Similarly, submit buttons can be given the class 'use-ajax-submit'. The
 * form will then be submitted via Ajax to the path specified in the #action.
 * Like the ajax-submit class above, this path will have '/nojs/' replaced with
 * '/ajax/' so that the submit handler can tell if the form was submitted
 * in a degraded state or not.
 *
 * As a developer you basically create a \Drupal\Core\Ajax\AjaxResponse and add
 * a couple of \Drupal\Core\Ajax\CommandInterface onto it, which will be
 * converted to a commands array automatically. This commands array will be
 * converted to a JSON object and returned to the client, which will then
 * iterate over the array and process it like a macro language.
 *
 * Each command item is an associative array which will be converted to a
 * command object on the JavaScript side. $command_item['command'] is the type
 * of command, e.g. 'alert' or 'replace', and will correspond to a method in the
 * Drupal.ajax[command] space. The command array may contain any other data that
 * the command needs to process, e.g. 'method', 'selector', 'settings', etc.
 *
 * Commands are usually created with a couple of helper functions, so they
 * look like this:
 * @code
 *   $commands = array();
 *   // Replace the content of '#object-1' on the page with 'some html here'.
 *   $commands[] = ajax_command_replace('#object-1', 'some html here');
 *   // Add a visual "changed" marker to the '#object-1' element.
 *   $commands[] = ajax_command_changed('#object-1');
 *   // #ajax['callback'] functions are supposed to return render arrays. If
 *   // returning an Ajax commands array, it must be encapsulated in a render
 *   // array structure.
 *   return array('#type' => 'ajax', '#commands' => $commands);
 * @endcode
 *
 * When returning an Ajax command array, it is often useful to have
 * status messages rendered along with other tasks in the command array.
 * In that case the the Ajax commands array may be constructed like this:
 * @code
 *   $commands = array();
 *   $commands[] = ajax_command_replace(NULL, $output);
 *   $status_messages = array('#theme' => 'status_messages');
 *   $commands[] = ajax_command_prepend(NULL, drupal_render($status_messages));
 *   return array('#type' => 'ajax', '#commands' => $commands);
 * @endcode
 *
 * See @link ajax_commands Ajax framework commands @endlink
 */

/**
 * Form element processing handler for the #ajax form property.
 *
 * @param $element
 *   An associative array containing the properties of the element.
 *
 * @return
 *   The processed element.
 *
 * @see ajax_pre_render_element()
 */
function ajax_process_form($element, &$form_state) {
  $element = ajax_pre_render_element($element);
  if (!empty($element['#ajax_processed'])) {
    $form_state['cache'] = TRUE;
  }
  return $element;
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
