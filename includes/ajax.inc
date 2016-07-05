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
 * ajax_form_callback() and a defined #ajax['callback'] function.
 * However, you may optionally specify a different path to request or a
 * different callback function to invoke, which can return updated HTML or can
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
 *   - The menu page callback for 'system/ajax', ajax_form_callback(), calls
 *     drupal_process_form() to process the form submission and rebuild the
 *     form if necessary. The form is processed in much the same way as if it
 *     were submitted without Ajax, with the same #process functions and
 *     validation and submission handlers called in either case, making it easy
 *     to create Ajax-enabled forms that degrade gracefully when JavaScript is
 *     disabled.
 *   - After form processing is complete, ajax_form_callback() calls the
 *     function named by #ajax['callback'], which returns the form element that
 *     has been updated and needs to be returned to the browser, or
 *     alternatively, an array of custom Ajax commands.
 *   - The page delivery callback for 'system/ajax', ajax_deliver(), renders the
 *     element returned by #ajax['callback'], and returns the JSON string
 *     created by ajax_render() to the browser.
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
 *   to a menu page callback that returns data using ajax_render(). Defaults to
 *   'system/ajax', which invokes ajax_form_callback(), eventually calling
 *   the function named in #ajax['callback']. If you use a custom
 *   path, you must set up the menu entry and handle the entire callback in your
 *   own code.
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
 * When responding to Ajax requests, the server should do what it needs to do
 * for that request, then create a commands array. This commands array will
 * be converted to a JSON object and returned to the client, which will then
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
 *   // Menu 'page callback' and #ajax['callback'] functions are supposed to
 *   // return render arrays. If returning an Ajax commands array, it must be
 *   // encapsulated in a render array structure.
 *   return array('#type' => 'ajax', '#commands' => $commands);
 * @endcode
 *
 * When returning an Ajax command array, it is often useful to have
 * status messages rendered along with other tasks in the command array.
 * In that case the Ajax commands array may be constructed like this:
 * @code
 *   $commands = array();
 *   $commands[] = ajax_command_replace(NULL, $output);
 *   $commands[] = ajax_command_prepend(NULL, theme('status_messages'));
 *   return array('#type' => 'ajax', '#commands' => $commands);
 * @endcode
 *
 * See @link ajax_commands Ajax framework commands @endlink
 */

/**
 * Renders a commands array into JSON.
 *
 * @param $commands
 *   A list of macro commands generated by the use of ajax_command_*()
 *   functions.
 */
function ajax_render($commands = array()) {
  // Although ajax_deliver() does this, some contributed and custom modules
  // render Ajax responses without using that delivery callback.
  ajax_set_verification_header();

  // Ajax responses aren't rendered with html.tpl.php, so we have to call
  // drupal_get_css() and drupal_get_js() here, in order to have new files added
  // during this request to be loaded by the page. We only want to send back
  // files that the page hasn't already loaded, so we implement simple diffing
  // logic using array_diff_key().
  foreach (array('css', 'js') as $type) {
    // It is highly suspicious if $_POST['ajax_page_state'][$type] is empty,
    // since the base page ought to have at least one JS file and one CSS file
    // loaded. It probably indicates an error, and rather than making the page
    // reload all of the files, instead we return no new files.
    if (empty($_POST['ajax_page_state'][$type])) {
      $items[$type] = array();
    }
    else {
      $function = 'drupal_add_' . $type;
      $items[$type] = $function();
      drupal_alter($type, $items[$type]);
      // @todo Inline CSS and JS items are indexed numerically. These can't be
      //   reliably diffed with array_diff_key(), since the number can change
      //   due to factors unrelated to the inline content, so for now, we strip
      //   the inline items from Ajax responses, and can add support for them
      //   when drupal_add_css() and drupal_add_js() are changed to use a hash
      //   of the inline content as the array key.
      foreach ($items[$type] as $key => $item) {
        if (is_numeric($key)) {
          unset($items[$type][$key]);
        }
      }
      // Ensure that the page doesn't reload what it already has.
      $items[$type] = array_diff_key($items[$type], $_POST['ajax_page_state'][$type]);
    }
  }

  // Render the HTML to load these files, and add AJAX commands to insert this
  // HTML in the page. We pass TRUE as the $skip_alter argument to prevent the
  // data from being altered again, as we already altered it above. Settings are
  // handled separately, afterwards.
  if (isset($items['js']['settings'])) {
    unset($items['js']['settings']);
  }
  $styles = drupal_get_css($items['css'], TRUE);
  $scripts_footer = drupal_get_js('footer', $items['js'], TRUE);
  $scripts_header = drupal_get_js('header', $items['js'], TRUE);

  $extra_commands = array();
  if (!empty($styles)) {
    $extra_commands[] = ajax_command_add_css($styles);
  }
  if (!empty($scripts_header)) {
    $extra_commands[] = ajax_command_prepend('head', $scripts_header);
  }
  if (!empty($scripts_footer)) {
    $extra_commands[] = ajax_command_append('body', $scripts_footer);
  }
  if (!empty($extra_commands)) {
    $commands = array_merge($extra_commands, $commands);
  }

  // Now add a command to merge changes and additions to Drupal.settings.
  $scripts = drupal_add_js();
  if (!empty($scripts['settings'])) {
    $settings = $scripts['settings'];
    array_unshift($commands, ajax_command_settings(drupal_array_merge_deep_array($settings['data']), TRUE));
  }

  // Allow modules to alter any Ajax response.
  drupal_alter('ajax_render', $commands);

  return drupal_json_encode($commands);
}

/**
 * Gets a form submitted via #ajax during an Ajax callback.
 *
 * This will load a form from the form cache used during Ajax operations. It
 * pulls the form info from $_POST.
 *
 * @return
 *   An array containing the $form, $form_state, $form_id, $form_build_id and an
 *   initial list of Ajax $commands. Use the list() function to break these
 *   apart:
 *   @code
 *     list($form, $form_state, $form_id, $form_build_id, $commands) = ajax_get_form();
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
    drupal_exit();
  }

  // When a page level cache is enabled, the form-build id might have been
  // replaced from within form_get_cache. If this is the case, it is also
  // necessary to update it in the browser by issuing an appropriate Ajax
  // command.
  $commands = array();
  if (isset($form['#build_id_old']) && $form['#build_id_old'] != $form['#build_id']) {
    // If the form build ID has changed, issue an Ajax command to update it.
    $commands[] = ajax_command_update_build_id($form);
    $form_build_id = $form['#build_id'];
  }

  // Since some of the submit handlers are run, redirects need to be disabled.
  $form_state['no_redirect'] = TRUE;

  // When a form is rebuilt after Ajax processing, its #build_id and #action
  // should not change.
  // @see drupal_rebuild_form()
  $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
  $form_state['rebuild_info']['copy']['#action'] = TRUE;

  // The form needs to be processed; prepare for that by setting a few internal
  // variables.
  $form_state['input'] = $_POST;
  $form_id = $form['#form_id'];

  return array($form, $form_state, $form_id, $form_build_id, $commands);
}

/**
 * Menu callback; handles Ajax requests for the #ajax Form API property.
 *
 * This rebuilds the form from cache and invokes the defined #ajax['callback']
 * to return an Ajax command structure for JavaScript. In case no 'callback' has
 * been defined, nothing will happen.
 *
 * The Form API #ajax property can be set both for buttons and other input
 * elements.
 *
 * This function is also the canonical example of how to implement
 * #ajax['path']. If processing is required that cannot be accomplished with
 * a callback, re-implement this function and set #ajax['path'] to the
 * enhanced function.
 *
 * @see system_menu()
 */
function ajax_form_callback() {
  list($form, $form_state, $form_id, $form_build_id, $commands) = ajax_get_form();
  drupal_process_form($form['#form_id'], $form, $form_state);

  // We need to return the part of the form (or some other content) that needs
  // to be re-rendered so the browser can update the page with changed content.
  // Since this is the generic menu callback used by many Ajax elements, it is
  // up to the #ajax['callback'] function of the element (may or may not be a
  // button) that triggered the Ajax request to determine what needs to be
  // rendered.
  if (!empty($form_state['triggering_element'])) {
    $callback = $form_state['triggering_element']['#ajax']['callback'];
  }
  if (!empty($callback) && is_callable($callback)) {
    $result = $callback($form, $form_state);

    if (!(is_array($result) && isset($result['#type']) && $result['#type'] == 'ajax')) {
      // Turn the response into a #type=ajax array if it isn't one already.
      $result = array(
        '#type' => 'ajax',
        '#commands' => ajax_prepare_response($result),
      );
    }

    $result['#commands'] = array_merge($commands, $result['#commands']);

    return $result;
  }
}

/**
 * Theme callback for Ajax requests.
 *
 * Many different pages can invoke an Ajax request to system/ajax or another
 * generic Ajax path. It is almost always desired for an Ajax response to be
 * rendered using the same theme as the base page, because most themes are built
 * with the assumption that they control the entire page, so if the CSS for two
 * themes are both loaded for a given page, they may conflict with each other.
 * For example, Bartik is Drupal's default theme, and Seven is Drupal's default
 * administration theme. Depending on whether the "Use the administration theme
 * when editing or creating content" checkbox is checked, the node edit form may
 * be displayed in either theme, but the Ajax response to the Field module's
 * "Add another item" button should be rendered using the same theme as the rest
 * of the page. Therefore, system_menu() sets the 'theme callback' for
 * 'system/ajax' to this function, and it is recommended that modules
 * implementing other generic Ajax paths do the same.
 *
 * @see system_menu()
 * @see file_menu()
 */
function ajax_base_page_theme() {
  if (!empty($_POST['ajax_page_state']['theme']) && !empty($_POST['ajax_page_state']['theme_token'])) {
    $theme = $_POST['ajax_page_state']['theme'];
    $token = $_POST['ajax_page_state']['theme_token'];

    // Prevent a request forgery from giving a person access to a theme they
    // shouldn't be otherwise allowed to see. However, since everyone is allowed
    // to see the default theme, token validation isn't required for that, and
    // bypassing it allows most use-cases to work even when accessed from the
    // page cache.
    if ($theme === variable_get('theme_default', 'bartik') || drupal_valid_token($token, $theme)) {
      return $theme;
    }
  }
}

/**
 * Packages and sends the result of a page callback as an Ajax response.
 *
 * This function is the equivalent of drupal_deliver_html_page(), but for Ajax
 * requests. Like that function, it:
 * - Adds needed HTTP headers.
 * - Prints rendered output.
 * - Performs end-of-request tasks.
 *
 * @param $page_callback_result
 *   The result of a page callback. Can be one of:
 *   - NULL: to indicate no content.
 *   - An integer menu status constant: to indicate an error condition.
 *   - A string of HTML content.
 *   - A renderable array of content.
 *
 * @see drupal_deliver_html_page()
 */
function ajax_deliver($page_callback_result) {
  // Browsers do not allow JavaScript to read the contents of a user's local
  // files. To work around that, the jQuery Form plugin submits forms containing
  // a file input element to an IFRAME, instead of using XHR. Browsers do not
  // normally expect JSON strings as content within an IFRAME, so the response
  // must be customized accordingly.
  // @see http://malsup.com/jquery/form/#file-upload
  // @see Drupal.ajax.prototype.beforeSend()
  $iframe_upload = !empty($_POST['ajax_iframe_upload']);

  // Emit a Content-Type HTTP header if none has been added by the page callback
  // or by a wrapping delivery callback.
  if (is_null(drupal_get_http_header('Content-Type'))) {
    if (!$iframe_upload) {
      // Standard JSON can be returned to a browser's XHR object, and to
      // non-browser user agents.
      // @see http://www.ietf.org/rfc/rfc4627.txt?number=4627
      drupal_add_http_header('Content-Type', 'application/json; charset=utf-8');
    }
    else {
      // Browser IFRAMEs expect HTML. With most other content types, Internet
      // Explorer presents the user with a download prompt.
      drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }
  }

  // Let ajax.js know that this response is safe to process.
  ajax_set_verification_header();

  // Print the response.
  $commands = ajax_prepare_response($page_callback_result);
  $json = ajax_render($commands);
  if (!$iframe_upload) {
    // Standard JSON can be returned to a browser's XHR object, and to
    // non-browser user agents.
    print $json;
  }
  else {
    // Browser IFRAMEs expect HTML. Browser extensions, such as Linkification
    // and Skype's Browser Highlighter, convert URLs, phone numbers, etc. into
    // links. This corrupts the JSON response. Protect the integrity of the
    // JSON data by making it the value of a textarea.
    // @see http://malsup.com/jquery/form/#file-upload
    // @see http://drupal.org/node/1009382
    print '<textarea>' . $json . '</textarea>';
  }

  // Perform end-of-request tasks.
  ajax_footer();
}

/**
 * Converts the return value of a page callback into an Ajax commands array.
 *
 * @param $page_callback_result
 *   The result of a page callback. Can be one of:
 *   - NULL: to indicate no content.
 *   - An integer menu status constant: to indicate an error condition.
 *   - A string of HTML content.
 *   - A renderable array of content.
 *
 * @return
 *   An Ajax commands array that can be passed to ajax_render().
 */
function ajax_prepare_response($page_callback_result) {
  $commands = array();
  if (!isset($page_callback_result)) {
    // Simply delivering an empty commands array is sufficient. This results
    // in the Ajax request being completed, but nothing being done to the page.
  }
  elseif (is_int($page_callback_result)) {
    switch ($page_callback_result) {
      case MENU_NOT_FOUND:
        $commands[] = ajax_command_alert(t('The requested page could not be found.'));
        break;

      case MENU_ACCESS_DENIED:
        $commands[] = ajax_command_alert(t('You are not authorized to access this page.'));
        break;

      case MENU_SITE_OFFLINE:
        $commands[] = ajax_command_alert(filter_xss_admin(variable_get('maintenance_mode_message',
          t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal'))))));
        break;
    }
  }
  elseif (is_array($page_callback_result) && isset($page_callback_result['#type']) && ($page_callback_result['#type'] == 'ajax')) {
    // Complex Ajax callbacks can return a result that contains an error message
    // or a specific set of commands to send to the browser.
    $page_callback_result += element_info('ajax');
    $error = $page_callback_result['#error'];
    if (isset($error) && $error !== FALSE) {
      if ((empty($error) || $error === TRUE)) {
        $error = t('An error occurred while handling the request: The server received invalid input.');
      }
      $commands[] = ajax_command_alert($error);
    }
    else {
      $commands = $page_callback_result['#commands'];
    }
  }
  else {
    // Like normal page callbacks, simple Ajax callbacks can return HTML
    // content, as a string or render array. This HTML is inserted in some
    // relationship to #ajax['wrapper'], as determined by which jQuery DOM
    // manipulation method is used. The method used is specified by
    // #ajax['method']. The default method is 'replaceWith', which completely
    // replaces the old wrapper element and its content with the new HTML.
    $html = is_string($page_callback_result) ? $page_callback_result : drupal_render($page_callback_result);
    $commands[] = ajax_command_insert(NULL, $html);
    // Add the status messages inside the new content's wrapper element, so that
    // on subsequent Ajax requests, it is treated as old content.
    $commands[] = ajax_command_prepend(NULL, theme('status_messages'));
  }

  return $commands;
}

/**
 * Sets a response header for ajax.js to trust the response body.
 *
 * It is not safe to invoke Ajax commands within user-uploaded files, so this
 * header protects against those being invoked.
 *
 * @see Drupal.ajax.options.success()
 */
function ajax_set_verification_header() {
  $added = &drupal_static(__FUNCTION__);

  // User-uploaded files cannot set any response headers, so a custom header is
  // used to indicate to ajax.js that this response is safe. Note that most
  // Ajax requests bound using the Form API will be protected by having the URL
  // flagged as trusted in Drupal.settings, so this header is used only for
  // things like custom markup that gets Ajax behaviors attached.
  if (empty($added)) {
    drupal_add_http_header('X-Drupal-Ajax-Token', '1');
    // Avoid sending the header twice.
    $added = TRUE;
  }
}

/**
 * Performs end-of-Ajax-request tasks.
 *
 * This function is the equivalent of drupal_page_footer(), but for Ajax
 * requests.
 *
 * @see drupal_page_footer()
 */
function ajax_footer() {
  // Even for Ajax requests, invoke hook_exit() implementations. There may be
  // modules that need very fast Ajax responses, and therefore, run Ajax
  // requests with an early bootstrap.
  if (drupal_get_bootstrap_phase() == DRUPAL_BOOTSTRAP_FULL && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update')) {
    module_invoke_all('exit');
  }

  // Commit the user session. See above comment about the possibility of this
  // function running without session.inc loaded.
  if (function_exists('drupal_session_commit')) {
    drupal_session_commit();
  }
}

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

  // Nothing to do if there is neither a callback nor a path.
  if (!(isset($element['#ajax']['callback']) || isset($element['#ajax']['path']))) {
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
    $element['#attached']['library'][] = array('system', 'jquery.form');
    $element['#attached']['library'][] = array('system', 'drupal.ajax');

    $settings = $element['#ajax'];

    // Assign default settings.
    $settings += array(
      'path' => 'system/ajax',
      'options' => array(),
    );

    // @todo Legacy support. Remove in Drupal 8.
    if (isset($settings['method']) && $settings['method'] == 'replace') {
      $settings['method'] = 'replaceWith';
    }

    // Change path to URL.
    $settings['url'] = url($settings['path'], $settings['options']);
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
      if (isset($element['#button_type']) && empty($element['#has_garbage_value'])) {
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
      'data' => array(
        'ajax' => array($element['#id'] => $settings),
        'urlIsAjaxTrusted' => array(
          $settings['url'] => TRUE,
        ),
      ),
    );

    // Indicate that Ajax processing was successful.
    $element['#ajax_processed'] = TRUE;
  }
  return $element;
}

/**
 * @} End of "defgroup ajax".
 */

/**
 * @defgroup ajax_commands Ajax framework commands
 * @{
 * Functions to create various Ajax commands.
 *
 * These functions can be used to create arrays for use with the
 * ajax_render() function.
 */

/**
 * Creates a Drupal Ajax 'alert' command.
 *
 * The 'alert' command instructs the client to display a JavaScript alert
 * dialog box.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.alert()
 * defined in misc/ajax.js.
 *
 * @param $text
 *   The message string to display to the user.
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
 * Creates a Drupal Ajax 'insert' command using the method in #ajax['method'].
 *
 * This command instructs the client to insert the given HTML using whichever
 * jQuery DOM manipulation method has been specified in the #ajax['method']
 * variable of the element that triggered the request.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.insert()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $html
 *   The data to use with the jQuery method.
 * @param $settings
 *   An optional array of settings that will be used for this command only.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_insert($selector, $html, $settings = NULL) {
  return array(
    'command' => 'insert',
    'method' => NULL,
    'selector' => $selector,
    'data' => $html,
    'settings' => $settings,
  );
}

/**
 * Creates a Drupal Ajax 'insert/replaceWith' command.
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
 * See
 * @link http://docs.jquery.com/Manipulation/replaceWith#content jQuery replaceWith command @endlink
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
 * Creates a Drupal Ajax 'insert/html' command.
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
 * Creates a Drupal Ajax 'insert/prepend' command.
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
 * Creates a Drupal Ajax 'insert/append' command.
 *
 * The 'insert/append' command instructs the client to use jQuery's append()
 * method to append the given HTML content to the inside of each element matched
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
 * Creates a Drupal Ajax 'insert/after' command.
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
 * Creates a Drupal Ajax 'insert/before' command.
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
 * Creates a Drupal Ajax 'remove' command.
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
 * Creates a Drupal Ajax 'changed' command.
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
    'asterisk' => $asterisk,
  );
}

/**
 * Creates a Drupal Ajax 'css' command.
 *
 * The 'css' command will instruct the client to use the jQuery css() method
 * to apply the CSS arguments to elements matched by the given selector.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.css()
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
 * Creates a Drupal Ajax 'settings' command.
 *
 * The 'settings' command instructs the client either to use the given array as
 * the settings for ajax-loaded content or to extend Drupal.settings with the
 * given array, depending on the value of the $merge parameter.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.settings()
 * defined in misc/ajax.js.
 *
 * @param $argument
 *   An array of key/value pairs to add to the settings. This will be utilized
 *   for all commands after this if they do not include their own settings
 *   array.
 * @param $merge
 *   Whether or not the passed settings in $argument should be merged into the
 *   global Drupal.settings on the page. By default (FALSE), the settings that
 *   are passed to Drupal.attachBehaviors will not include the global
 *   Drupal.settings.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_settings($argument, $merge = FALSE) {
  return array(
    'command' => 'settings',
    'settings' => $argument,
    'merge' => $merge,
  );
}

/**
 * Creates a Drupal Ajax 'data' command.
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
 *   The value of the data. Not just limited to strings can be any format.
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
 * Creates a Drupal Ajax 'invoke' command.
 *
 * The 'invoke' command will instruct the client to invoke the given jQuery
 * method with the supplied arguments on the elements matched by the given
 * selector. Intended for simple jQuery commands, such as attr(), addClass(),
 * removeClass(), toggleClass(), etc.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.invoke()
 * defined in misc/ajax.js.
 *
 * @param $selector
 *   A jQuery selector string. If the command is a response to a request from
 *   an #ajax form element then this value can be NULL.
 * @param $method
 *   The jQuery method to invoke.
 * @param $arguments
 *   (optional) A list of arguments to the jQuery $method, if any.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function ajax_command_invoke($selector, $method, array $arguments = array()) {
  return array(
    'command' => 'invoke',
    'selector' => $selector,
    'method' => $method,
    'arguments' => $arguments,
  );
}

/**
 * Creates a Drupal Ajax 'restripe' command.
 *
 * The 'restripe' command instructs the client to restripe a table. This is
 * usually used after a table has been modified by a replace or append command.
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

/**
 * Creates a Drupal Ajax 'update_build_id' command.
 *
 * This command updates the value of a hidden form_build_id input element on a
 * form. It requires the form passed in to have keys for both the old build ID
 * in #build_id_old and the new build ID in #build_id.
 *
 * The primary use case for this Ajax command is to serve a new build ID to a
 * form served from the cache to an anonymous user, preventing one anonymous
 * user from accessing the form state of another anonymous users on Ajax enabled
 * forms.
 *
 * @param $form
 *   The form array representing the form whose build ID should be updated.
 */
function ajax_command_update_build_id($form) {
  return array(
    'command' => 'updateBuildId',
    'old' => $form['#build_id_old'],
    'new' => $form['#build_id'],
  );
}

/**
 * Creates a Drupal Ajax 'add_css' command.
 *
 * This method will add css via ajax in a cross-browser compatible way.
 *
 * This command is implemented by Drupal.ajax.prototype.commands.add_css()
 * defined in misc/ajax.js.
 *
 * @param $styles
 *   A string that contains the styles to be added.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 *
 * @see misc/ajax.js
 */
function ajax_command_add_css($styles) {
  return array(
    'command' => 'add_css',
    'data' => $styles,
  );
}
