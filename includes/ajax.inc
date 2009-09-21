<?php
// $Id$

/**
 * @file
 * Functions for use with Drupal's AJAX framework.
 */

/**
 * @defgroup ajax AJAX framework
 * @{
 * Drupal's AJAX framework is used to dynamically update parts of a page's HTML
 * based on data from the server. Upon a specified event, such as a button
 * click, a callback function is triggered which performs server-side logic and
 * may return updated markup, which is then replaced on-the-fly with no page
 * refresh necessary.
 *
 * This framework creates a PHP macro language that allows the server to
 * instruct JavaScript to perform actions on the client browser. When using
 * forms, it can be used with the #ajax property.
 * The #ajax property can be used to bind events to the AJAX framework. By
 * default, #ajax uses 'system/ajax' as path, along with its defined page
 * callback. However, you may optionally specify a different path to request or
 * a different callback function to invoke, which can return updated HTML or can
 * also return a richer set of AJAX framework commands.
 *
 * @see @link ajax_commands AJAX framework commands @endlink
 *
 * To implement AJAX handling in a normal form, just add '#ajax' to the form
 * definition of a field. That field will trigger an AJAX event when it is
 * clicked (or changed, depending on the kind of field). #ajax supports
 * the following parameters (either 'path' or 'callback' is required at least):
 * - #ajax['path']: The menu path to use for the request. This path should map
 *   to a menu page callback that returns data using ajax_render(). By default,
 *   this is 'system/ajax'. Be warned that the default path currently only works
 *   for buttons. It will not work for selects, textfields, or textareas.
 * - #ajax['callback']: The callback to invoke, which will receive a $form and
 *   $form_state as arguments, and should return the HTML to replace. By
 *   default, the page callback defined for the menu path 'system/ajax' is
 *   triggered to handle the server side of the #ajax event.
 * - #ajax['wrapper']: The CSS ID of the AJAX area. The HTML returned from the
 *   callback will replace whatever is currently in this wrapper. It is
 *   important to ensure that this wrapper exists in the form. The wrapper is
 *   usually created using #prefix and #suffix properties in the form.
 * - #ajax['effect']: The jQuery effect to use when placing the new HTML.
 *   Defaults to no effect. Valid options are 'none', 'slide', or 'fade'.
 * - #ajax['speed']: The effect speed to use. Defaults to 'slow'. May be
 *   'slow', 'fast' or a number in milliseconds which represents the length
 *   of time the effect should run.
 * - #ajax['event']: The JavaScript event to respond to. This is normally
 *   selected automatically for the type of form widget being used, and
 *   is only needed if you need to override the default behavior.
 * - #ajax['method']: The jQuery method to use to place the new HTML.
 *   Defaults to 'replace'. May be: 'replace', 'append', 'prepend',
 *   'before', 'after', or 'html'. See the jQuery documentation for more
 *   information on these methods.
 *
 * In addition to using Form API for doing in-form modification, AJAX may be
 * enabled by adding classes to buttons and links. By adding the 'use-ajax'
 * class to a link, the link will be loaded via an AJAX call. When using this
 * method, the href of the link can contain '/nojs/' as part of the path. When
 * the AJAX framework makes the request, it will convert this to '/ajax/'.
 * The server is then able to easily tell if this request was made through an
 * actual AJAX request or in a degraded state, and respond appropriately.
 *
 * Similarly, submit buttons can be given the class 'use-ajax-submit'. The
 * form will then be submitted via AJAX to the path specified in the #action.
 * Like the ajax-submit class above, this path will have '/nojs/' replaced with
 * '/ajax/' so that the submit handler can tell if the form was submitted
 * in a degraded state or not.
 *
 * When responding to AJAX requests, the server should do what it needs to do
 * for that request, then create a commands array. This commands array will
 * be converted to a JSON object and returned to the client, which will then
 * iterate over the array and process it like a macro language.
 *
 * @see @link ajax_commands AJAX framework commands @endlink
 *
 * Each command is an object. $object->command is the type of command and will
 * be used to find the method (it will correlate directly to a method in
 * the Drupal.ajax[command] space). The object may contain any other data that
 * the command needs to process.
 *
 * Commands are usually created with a couple of helper functions, so they
 * look like this:
 *
 * @code
 *   $commands = array();
 *   // Replace the content of '#object-1' on the page with 'some html here'.
 *   $commands[] = ajax_command_replace('#object-1', 'some html here');
 *   // Add a visual "changed" marker to the '#object-1' element.
 *   $commands[] = ajax_command_changed('#object-1');
 *   // Output new markup to the browser and end the request.
 *   ajax_render($commands);
 * @endcode
 */

/**
 * Render a commands array into JSON and exit.
 *
 * Commands are immediately handed back to the AJAX requester. This function
 * will render and immediately exit.
 *
 * @param $commands
 *   A list of macro commands generated by the use of ajax_command_*()
 *   functions.
 * @param $header
 *   If set to FALSE the 'text/javascript' header used by drupal_json() will
 *   not be used, which is necessary when using an IFRAME. If set to
 *   'multipart' the output will be wrapped in a textarea, which can also be
 *   used as an alternative method when uploading files.
 */
function ajax_render($commands = array(), $header = TRUE) {
  // Automatically extract any 'settings' added via drupal_add_js() and make
  // them the first command.
  $scripts = drupal_add_js(NULL, NULL);
  if (!empty($scripts['settings'])) {
    array_unshift($commands, ajax_command_settings($scripts['settings']['data']));
  }

  // Allow modules to alter any AJAX response.
  drupal_alter('ajax_render', $commands);

  // Use === here so that bool TRUE doesn't match 'multipart'.
  if ($header === 'multipart') {
    // We do not use drupal_json() here because the header is not true. We are
    // not really returning JSON, strictly-speaking, but rather JSON content
    // wrapped in a textarea as per the "file uploads" example here:
    // http://malsup.com/jquery/form/#code-samples
    print '<textarea>' . drupal_to_js($commands) . '</textarea>';
  }
  else if ($header) {
    drupal_json($commands);
  }
  else {
    print drupal_to_js($commands);
  }
  exit;
}

/**
 * Send an error response back via AJAX and immediately exit.
 *
 * This function can be used to quickly create a command array with an error
 * string and send it, short-circuiting the error handling process.
 *
 * @param $error
 *   A string to display in an alert.
 */
function ajax_render_error($error = '') {
  $commands = array();
  $commands[] = ajax_command_alert(empty($error) ? t('An error occurred while handling the request: The server received invalid input.') : $error);
  ajax_render($commands);
}

/**
 * Get a form submitted via #ajax during an AJAX callback.
 *
 * This will load a form from the form cache used during AJAX operations. It
 * pulls the form info from $_POST.
 *
 * @return
 *   An array containing the $form and $form_state. Use the list() function
 *   to break these apart:
 *   @code
 *     list($form, $form_state, $form_id, $form_build_id) = ajax_get_form();
 *   @endcode
 */
function ajax_get_form() {
  $form_state = form_state_defaults();

  $form_build_id = $_POST['form_build_id'];

  // Get the form from the cache.
  $form = form_get_cache($form_build_id, $form_state);
  if (!$form) {
    // If $form cannot be loaded from the cache, the form_build_id in $_POST
    // must be invalid, which means that someone performed a POST request onto
    // system/ajax without actually viewing the concerned form in the browser.
    // This is likely a hacking attempt as it never happens under normal
    // circumstances, so we just do nothing.
    watchdog('ajax', 'Invalid form POST data.', array(), WATCHDOG_WARNING);
    exit;
  }

  // Since some of the submit handlers are run, redirects need to be disabled.
  $form_state['no_redirect'] = TRUE;

  // The form needs to be processed; prepare for that by setting a few internal
  // variables.
  $form_state['input'] = $_POST;
  $form_state['args'] = $form['#args'];
  $form_id = $form['#form_id'];

  return array($form, $form_state, $form_id, $form_build_id);
}

/**
 * Menu callback for AJAX callbacks through the #ajax['callback'] Form API property.
 */
function ajax_form_callback() {
  list($form, $form_state, $form_id, $form_build_id) = ajax_get_form();

  // Build, validate and if possible, submit the form.
  drupal_process_form($form_id, $form, $form_state);

  // This call recreates the form relying solely on the $form_state that
  // drupal_process_form() set up.
  $form = drupal_rebuild_form($form_id, $form_state, $form_build_id);

  // Get the callback function from the clicked button.
  $ajax = $form_state['clicked_button']['#ajax'];
  $callback = $ajax['callback'];
  if (function_exists($callback)) {
    $html = $callback($form, $form_state);

    // If the returned value is a string, assume it is HTML and create
    // a command object to return automatically.
    if (is_string($html)) {
      $commands = array();
      $commands[] = ajax_command_replace(NULL, $html);
    }
    // Otherwise, $html is supposed to be an array of commands, suitable for
    // Drupal.ajax, so we pass it on as is.
    else {
      $commands = $html;
    }

    ajax_render($commands);
  }

  // Return a 'do nothing' command if there was no callback.
  ajax_render(array());
}

/**
 * Add AJAX information about a form element to the page to communicate with JavaScript.
 *
 * If #ajax['path'] is set on an element, this additional JavaScript is added
 * to the page header to attach the AJAX behaviors. See ajax.js for more
 * information.
 *
 * @param $element
 *   An associative array containing the properties of the element.
 *   Properties used:
 *   - #ajax['event']
 *   - #ajax['path']
 *   - #ajax['wrapper']
 *   - #ajax['parameters']
 *   - #ajax['effect']
 *
 * @return
 *   None. Additional code is added to the header of the page using
 *   drupal_add_js().
 */
function ajax_process_form($element) {
  $js_added = &drupal_static(__FUNCTION__, array());

  // Add a reasonable default event handler if none was specified.
  if (isset($element['#ajax']) && !isset($element['#ajax']['event'])) {
    switch ($element['#type']) {
      case 'submit':
      case 'button':
      case 'image_button':
        // Use the mousedown instead of the click event because form
        // submission via pressing the enter key triggers a click event on
        // submit inputs, inappropriately triggering AJAX behaviors.
        $element['#ajax']['event'] = 'mousedown';
        // Attach an additional event handler so that AJAX behaviors
        // can be triggered still via keyboard input.
        $element['#ajax']['keypress'] = TRUE;
        break;

      case 'password':
      case 'textfield':
      case 'textarea':
        $element['#ajax']['event'] = 'blur';
        break;

      case 'radio':
      case 'checkbox':
      case 'select':
        $element['#ajax']['event'] = 'change';
        break;

      default:
        return $element;
    }
  }

  // Adding the same JavaScript settings twice will cause a recursion error,
  // we avoid the problem by checking if the JavaScript has already been added.
  if (!isset($js_added[$element['#id']]) && (isset($element['#ajax']['callback']) || isset($element['#ajax']['path'])) && isset($element['#ajax']['event'])) {
    drupal_add_library('system', 'form');
    $element['#attached']['js'][] = 'misc/ajax.js';

    $ajax_binding = array(
      'url'      => isset($element['#ajax']['callback']) ? url('system/ajax') : url($element['#ajax']['path']),
      'event'    => $element['#ajax']['event'],
      'keypress' => empty($element['#ajax']['keypress']) ? NULL : $element['#ajax']['keypress'],
      'wrapper'  => empty($element['#ajax']['wrapper']) ? NULL : $element['#ajax']['wrapper'],
      'selector' => empty($element['#ajax']['selector']) ? '#' . $element['#id'] : $element['#ajax']['selector'],
      'effect'   => empty($element['#ajax']['effect']) ? 'none' : $element['#ajax']['effect'],
      'speed'    => empty($element['#ajax']['effect']) ? 'none' : $element['#ajax']['effect'],
      'method'   => empty($element['#ajax']['method']) ? 'replace' : $element['#ajax']['method'],
      'progress' => empty($element['#ajax']['progress']) ? array('type' => 'throbber') : $element['#ajax']['progress'],
      'button'   => isset($element['#executes_submit_callback']) ? array($element['#name'] => $element['#value']) : FALSE,
    );

    // Convert a simple #ajax['progress'] type string into an array.
    if (is_string($ajax_binding['progress'])) {
      $ajax_binding['progress'] = array('type' => $ajax_binding['progress']);
    }
    // Change progress path to a full URL.
    if (isset($ajax_binding['progress']['path'])) {
      $ajax_binding['progress']['url'] = url($ajax_binding['progress']['path']);
    }
    // Add progress.js if we're doing a bar display.
    if ($ajax_binding['progress']['type'] == 'bar') {
      drupal_add_js('misc/progress.js', array('cache' => FALSE));
    }

    drupal_add_js(array('ajax' => array($element['#id'] => $ajax_binding)), 'setting');

    $js_added[$element['#id']] = TRUE;
    $element['#cache'] = TRUE;
  }
  return $element;
}

/**
 * @} End of "defgroup ajax".
 */

/**
 * @defgroup ajax_commands AJAX framework commands
 * @{
 * @ingroup ajax
 */

/**
 * Creates a Drupal AJAX 'alert' command.
 *
 * The 'alert' command instructs the client to display a JavaScript alert
 * dialog box.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.alert()
 * defined in misc/ajax.js.
 *
 * @param $text
 *   The message string to dipslay to the user.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_alert($text) {
  return array(
    'command' => 'alert',
    'text' => $text,
  );
}

/**
 * Creates a Drupal AJAX 'insert/replaceWith' command.
 *
 * The 'insert/replaceWith' command instructs the client to use jQuery's
 * replaceWith() method to replace each element matched matched by the given
 * selector with the given HTML.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery replaceWith() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/replaceWith#content
 */
function ajax_command_replace($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'replaceWith',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'insert/html' command.
 *
 * The 'insert/html' command instructs the client to use jQuery's html()
 * method to set the HTML content of each element matched by the given
 * selector while leaving the outer tags intact.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery html() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Attributes/html#val
 */
function ajax_command_html($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'html',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'insert/prepend' command.
 *
 * The 'insert/prepend' command instructs the client to use jQuery's prepend()
 * method to prepend the given HTML content to the inside each element matched
 * by the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery prepend() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/prepend#content
 */
function ajax_command_prepend($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'prepend',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'insert/append' command.
 *
 * The 'insert/append' command instructs the client to use jQuery's append()
 * method to append the given HTML content to the inside each element matched
 * by the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery append() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/append#content
 */
function ajax_command_append($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'append',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'insert/after' command.
 *
 * The 'insert/after' command instructs the client to use jQuery's after()
 * method to insert the given HTML content after each element matched by
 * the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery after() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/after#content
 */
function ajax_command_after($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'after',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'insert/before' command.
 *
 * The 'insert/before' command instructs the client to use jQuery's before()
 * method to insert the given HTML content before each of elements matched by
 * the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery before() method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/before#content
 */
function ajax_command_before($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => 'before',
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal AJAX 'remove' command.
 *
 * The 'remove' command instructs the client to use jQuery's remove() method
 * to remove each of elements matched by the given selector, and everything
 * within them.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.remove()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Manipulation/remove#expr
 */
function ajax_command_remove($selector) {
  return array(
    'command' => 'remove',
    'selector' => $selector,
  );
}

/**
 * Creates a Drupal AJAX 'changed' command.
 *
 * This command instructs the client to mark each of the elements matched by the
 * given selector as 'ajax-changed'.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.changed()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $asterisk
 *   An optional CSS selector which must be inside $selector. If specified,
 *   an asterisk will be appended to the HTML inside the $asterisk selector.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_changed($selector, $asterisk = '') {
  return array(
    'command' => 'changed',
    'selector' => $selector,
    'star' => $asterisk,
  );
}

/**
 * Creates a Drupal AJAX 'css' command.
 *
 * The 'css' command will instruct the client to use the jQuery css() method
 * to apply the CSS arguments to elements matched by the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $argument
 *   An array of key/value pairs to set in the CSS for the selector.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/CSS/css#properties
 */
function ajax_command_css($selector, $argument) {
  return array(
    'command' => 'css',
    'selector' => $selector,
    'argument' => $argument,
  );
}

/**
 * Creates a Drupal AJAX 'settings' command.
 *
 * The 'settings' command instructs the client to extend Drupal.settings with
 * the given array.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.settings()
 * defined in misc/ajax.js.
 *
 * @param $argument
 *   An array of key/value pairs to add to the settings. This will be utilized
 *   for all commands after this if they do not include their own settings
 *   array.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_settings($argument) {
  return array(
    'command' => 'settings',
    'argument' => $argument,
  );
}

/**
 * Creates a Drupal AJAX 'data' command.
 *
 * The 'data' command instructs the client to attach the name=value pair of
 * data to the selector via jQuery's data cache.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.data()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $name
 *   The name or key (in the key value pair) of the data attached to this
 *   selector.
 * @param $value
 *  The value of the data. Not just limited to strings can be any format.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see http://docs.jquery.com/Core/data#namevalue
 */
function ajax_command_data($selector, $name, $value) {
  return array(
    'command' => 'data',
    'selector' => $selector,
    'name' => $name,
    'value' => $value,
  );
}

/**
 * Creates a Drupal AJAX 'restripe' command.
 *
 * The 'restripe' command instructs the client to restripe a table. This is
 * usually used after a table has been modifed by a replace or append command.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.restripe()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_restripe($selector) {
  return array(
    'command' => 'restripe',
    'selector' => $selector,
  );
}

