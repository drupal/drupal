<?php
// $Id$

/**
 * @file
 * Hooks provided by Drupal core and the System module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform periodic actions.
 *
 * Modules that require to schedule some commands to be executed at regular
 * intervals can implement hook_cron(). The engine will then call the hook
 * at the appropriate intervals defined by the administrator. This interface
 * is particularly handy to implement timers or to automate certain tasks.
 * Database maintenance, recalculation of settings or parameters, and
 * automatic mailings are good candidates for cron tasks.
 *
 * @return
 *   None.
 *
 * This hook will only be called if cron.php is run (e.g. by crontab).
 */
function hook_cron() {
  $result = db_query('SELECT * FROM {site} WHERE checked = 0 OR checked + refresh < :time', array(':time' => REQUEST_TIME));

  foreach ($result as $site) {
    cloud_update($site);
  }
}

/**
 * Rewrite database queries, usually for access control.
 *
 * Add JOIN and WHERE statements to queries and decide whether the primary_field
 * shall be made DISTINCT. For node objects, primary field is always called nid.
 * For taxonomy terms, it is tid and for vocabularies it is vid. For comments,
 * it is cid. Primary table is the table where the primary object (node, file,
 * term_node etc.) is.
 *
 * You shall return an associative array. Possible keys are 'join', 'where' and
 * 'distinct'. The value of 'distinct' shall be 1 if you want that the
 * primary_field made DISTINCT.
 *
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query.
 *   Typical table names would be: {blocks}, {comments}, {forum}, {node},
 *   {menu}, {term_data} or {vocabulary}. However, it is more common for
 *   $primary_table to contain the usual table alias: b, c, f, n, m, t or v.
 * @param $primary_field
 *   Name of the primary field.
 * @param $args
 *   Array of additional arguments.
 * @return
 *   An array of join statements, where statements, distinct decision.
 */
function hook_db_rewrite_sql($query, $primary_table, $primary_field, $args) {
  switch ($primary_field) {
    case 'nid':
      // this query deals with node objects
      $return = array();
      if ($primary_table != 'n') {
        $return['join'] = "LEFT JOIN {node} n ON $primary_table.nid = n.nid";
      }
      $return['where'] = 'created >' . mktime(0, 0, 0, 1, 1, 2005);
      return $return;
      break;
    case 'tid':
      // this query deals with taxonomy objects
      break;
    case 'vid':
      // this query deals with vocabulary objects
      break;
  }
}

/**
 * Allows modules to declare their own Forms API element types and specify their
 * default values.
 *
 * This hook allows modules to declare their own form element types and to
 * specify their default values. The values returned by this hook will be
 * merged with the elements returned by hook_form() implementations and so
 * can return defaults for any Form APIs keys in addition to those explicitly
 * mentioned below.
 *
 * Each of the form element types defined by this hook is assumed to have
 * a matching theme function, e.g. theme_elementtype(), which should be
 * registered with hook_theme() as normal.
 *
 * Form more information about custom element types see the explanation at
 * http://drupal.org/node/169815.
 *
 * @return
 *  An associative array describing the element types being defined. The array
 *  contains a sub-array for each element type, with the machine-readable type
 *  name as the key. Each sub-array has a number of possible attributes:
 *  - "#input": boolean indicating whether or not this element carries a value
 *    (even if it's hidden).
 *  - "#process": array of callback functions taking $element and $form_state.
 *  - "#after_build": array of callback functions taking $element and $form_state.
 *  - "#validate": array of callback functions taking $form and $form_state.
 *  - "#element_validate": array of callback functions taking $element and
 *    $form_state.
 *  - "#pre_render": array of callback functions taking $element and $form_state.
 *  - "#post_render": array of callback functions taking $element and $form_state.
 *  - "#submit": array of callback functions taking $form and $form_state.
 */
function hook_elements() {
  $type['filter_format'] = array('#input' => TRUE);
  return $type;
}

/**
 * Perform cleanup tasks.
 *
 * This hook is run at the end of each page request. It is often used for
 * page logging and printing out debugging information.
 *
 * Only use this hook if your code must run even for cached page views.
 * If you have code which must run once on all non cached pages, use
 * hook_init instead. Thats the usual case. If you implement this hook
 * and see an error like 'Call to undefined function', it is likely that
 * you are depending on the presence of a module which has not been loaded yet.
 * It is not loaded because Drupal is still in bootstrap mode.
 *
 * @param $destination
 *   If this hook is invoked as part of a drupal_goto() call, then this argument
 *   will be a fully-qualified URL that is the destination of the redirect.
 *   Modules may use this to react appropriately; for example, nothing should
 *   be output in this case, because PHP will then throw a "headers cannot be
 *   modified" error when attempting the redirection.
 * @return
 *   None.
 */
function hook_exit($destination = NULL) {
  db_query('UPDATE {counter} SET hits = hits + 1 WHERE type = 1');
}

/**
 * Insert closing HTML.
 *
 * This hook enables modules to insert HTML just before the \</body\> closing
 * tag of web pages. This is useful for adding JavaScript code to the footer
 * and for outputting debug information. It is not possible to add JavaScript
 * to the header at this point, and developers wishing to do so should use
 * hook_init() instead.
 *
 * @param $main
 *   Whether the current page is the front page of the site.
 * @return
 *   The HTML to be inserted.
 */
function hook_footer($main = 0) {
  if (variable_get('dev_query', 0)) {
    return '<div style="clear:both;">' . devel_query_table() . '</div>';
  }
}

/**
 * Perform necessary alterations to the JavaScript before it is presented on
 * the page.
 *
 * @param $javascript
 *   An array of all JavaScript being presented on the page.
 * @see drupal_add_js()
 * @see drupal_get_js()
 * @see drupal_js_defaults()
 */
function hook_js_alter(&$javascript) {
  // Swap out jQuery to use an updated version of the library.
  $javascript['misc/jquery.js']['data'] = drupal_get_path('module', 'jquery_update') . '/jquery.js';
}

/**
 * Perform alterations before a form is rendered.
 *
 * One popular use of this hook is to add form elements to the node form. When
 * altering a node form, the node object retrieved at from $form['#node'].
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 * @return
 *   None.
 */
function hook_form_alter(&$form, $form_state, $form_id) {
  if (isset($form['type']) && $form['type']['#value'] . '_node_settings' == $form_id) {
    $form['workflow']['upload_' . $form['type']['#value']] = array(
      '#type' => 'radios',
      '#title' => t('Attachments'),
      '#default_value' => variable_get('upload_' . $form['type']['#value'], 1),
      '#options' => array(t('Disabled'), t('Enabled')),
    );
  }
}

/**
 * Provide a form-specific alteration instead of the global hook_form_alter().
 *
 * Modules can implement hook_form_FORM_ID_alter() to modify a specific form,
 * rather than implementing hook_form_alter() and checking the form ID, or
 * using long switch statements to alter multiple forms.
 *
 * Note that this hook fires before hook_form_alter(). Therefore all
 * implementations of hook_form_FORM_ID_alter() will run before all implementations
 * of hook_form_alter(), regardless of the module order.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 * @return
 *   None.
 *
 * @see drupal_prepare_form().
 */
function hook_form_FORM_ID_alter(&$form, &$form_state) {
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Map form_ids to builder functions.
 *
 * This hook allows modules to build multiple forms from a single form "factory"
 * function but each form will have a different form id for submission,
 * validation, theming or alteration by other modules.
 *
 * The callback arguments will be passed as parameters to the function. Callers
 * of drupal_get_form() are also able to pass in parameters. These will be
 * appended after those specified by hook_forms().
 *
 * See node_forms() for an actual example of how multiple forms share a common
 * building function.
 *
 * @return
 *   An array keyed by form id with callbacks and optional, callback arguments.
 */
function hook_forms() {
  $forms['mymodule_first_form'] = array(
    'callback' => 'mymodule_form_builder',
    'callback arguments' => array('some parameter'),
  );
  $forms['mymodule_second_form'] = array(
    'callback' => 'mymodule_form_builder',
  );
  return $forms;
}

/**
 * Perform setup tasks. See also, hook_init.
 *
 * This hook is run at the beginning of the page request. It is typically
 * used to set up global parameters which are needed later in the request.
 *
 * Only use this hook if your code must run even for cached page views.This hook
 * is called before modules or most include files are loaded into memory.
 * It happens while Drupal is still in bootstrap mode.
 *
 * @return
 *   None.
 */
function hook_boot() {
  // we need user_access() in the shutdown function. make sure it gets loaded
  drupal_load('module', 'user');
  register_shutdown_function('devel_shutdown');
}

/**
 * Perform setup tasks. See also, hook_boot.
 *
 * This hook is run at the beginning of the page request. It is typically
 * used to set up global parameters which are needed later in the request.
 * when this hook is called, all modules are already loaded in memory.
 *
 * For example, this hook is a typical place for modules to add CSS or JS
 * that should be present on every page. This hook is not run on cached
 * pages - though CSS or JS added this way will be present on a cached page.
 *
 * @return
 *   None.
 */
function hook_init() {
  drupal_add_css(drupal_get_path('module', 'book') . '/book.css');
}

/**
* Define image toolkits provided by this module.
*
* The file which includes each toolkit's functions must be declared as part of
* the files array in the module  .info file so that the registry will find and
* parse it.
*
* @return
*   An array of image toolkit names.
*/
function hook_image_toolkits() {
  return array('gd');
}

/**
 * Define internal Drupal links.
 *
 * This hook enables modules to add links to many parts of Drupal. Links
 * may be added in nodes or in the navigation block, for example.
 *
 * The returned array should be a keyed array of link entries. Each link can
 * be in one of two formats.
 *
 * The first format will use the l() function to render the link:
 *   - attributes: Optional. See l() for usage.
 *   - fragment: Optional. See l() for usage.
 *   - href: Required. The URL of the link.
 *   - html: Optional. See l() for usage.
 *   - query: Optional. See l() for usage.
 *   - title: Required. The name of the link.
 *
 * The second format can be used for non-links. Leaving out the href index will
 * select this format:
 *   - title: Required. The text or HTML code to display.
 *   - attributes: Optional. An associative array of HTML attributes to apply to the span tag.
 *   - html: Optional. If not set to true, check_plain() will be run on the title before it is displayed.
 *
 * @param $type
 *   An identifier declaring what kind of link is being requested.
 *   Possible values:
 *   - comment: Links to be placed below a comment being viewed.
 *   - node: Links to be placed below a node being viewed.
 * @param $object
 *   A node object or a comment object according to the $type.
 * @param $teaser
 *   In case of node link: a 0/1 flag depending on whether the node is
 *   displayed with its teaser or its full form.
 * @return
 *   An array of the requested links.
 *
 */
function hook_link($type, $object, $teaser = FALSE) {
  $links = array();

  if ($type == 'node' && isset($object->parent)) {
    if (!$teaser) {
      if (book_access('create', $object)) {
        $links['book_add_child'] = array(
          'title' => t('add child page'),
          'href' => "node/add/book/parent/$object->nid",
        );
      }
      if (user_access('see printer-friendly version')) {
        $links['book_printer'] = array(
          'title' => t('printer-friendly version'),
          'href' => 'book/export/html/' . $object->nid,
          'attributes' => array('title' => t('Show a printer-friendly version of this book page and its sub-pages.'))
        );
      }
    }
  }

  $links['sample_link'] = array(
    'title' => t('go somewhere'),
    'href' => 'node/add',
    'query' => 'foo=bar',
    'fragment' => 'anchorname',
    'attributes' => array('title' => t('go to another page')),
  );

  // Example of a link that's not an anchor
  if ($type == 'video') {
    if (variable_get('video_playcounter', 1) && user_access('view play counter')) {
      $links['play_counter'] = array(
        'title' => format_plural($object->play_counter, '1 play', '@count plays'),
      );
    }
  }

  return $links;
}

/**
 * Perform alterations before links on a node are rendered. One popular use of
 * this hook is to add/delete links from other modules.
 *
 * @param $links
 *   Nested array of links for the node
 * @param $node
 *   A node object for editing links on
 * @return
 *   None.
 */
function hook_link_alter(&$links, $node) {
  foreach ($links AS $module => $link) {
    if (strstr($module, 'taxonomy_term')) {
      // Link back to the forum and not the taxonomy term page
      $links[$module]['#href'] = str_replace('taxonomy/term', 'forum', $link['#href']);
    }
  }
}

/**
 * Perform alterations profile items before they are rendered. You may omit/add/re-sort/re-categorize, etc.
 *
 * @param $account
 *   A user object whose profile is being rendered. Profile items
 *   are stored in $account->content.
 * @return
 *   None.
 */
function hook_profile_alter(&$account) {
  foreach ($account->content AS $key => $field) {
    // do something
  }
}

/**
 * Alter any aspect of the emails sent by Drupal. You can use this hook
 * to add a common site footer to all outgoing emails; add extra header
 * fields and/or modify the mails sent out in any way. HTML-izing the
 * outgoing mails is one possibility. See also drupal_mail().
 *
 * @param $message
 *   A structured array containing the message to be altered. Keys in this
 *   array include:
 *   mail_id
 *     An id to identify the mail sent. Look into the module source codes
 *     for possible mail_id values.
 *   to
 *     The mail address or addresses where the message will be send to. The
 *     formatting of this string must comply with RFC 2822.
 *   subject
 *     Subject of the e-mail to be sent. This must not contain any newline
 *     characters, or the mail may not be sent properly.
 *   body
 *     An array of lines containing the message to be sent. Drupal will format
 *     the correct line endings for you.
 *   from
 *     The From, Reply-To, Return-Path and Error-To headers in $headers
 *     are already set to this value (if given).
 *   headers
 *     Associative array containing the headers to add. This is typically
 *     used to add extra headers (From, Cc, and Bcc).
 * @return
 *   None.
 */
function hook_mail_alter(&$message) {
  if ($message['mail_id'] == 'my_message') {
    $message['body'] .= "\n\n--\nMail sent out from " . variable_get('sitename', t('Drupal'));
  }
}

/**
 * Alter the information parsed from module and theme .info files
 *
 * This hook is invoked in  module_rebuild_cache() and in system_theme_data().
 * A module may implement this hook in order to add to or alter the data
 * generated by reading the .info file with drupal_parse_info_file().
 *
 * @param &$info
 *   The .info file contents, passed by reference so that it can be altered.
 * @param $file
 *   Full information about the module or theme, including $file->name, and
 *   $file->filename
 */
function hook_system_info_alter(&$info, $file) {
  // Only fill this in if the .info file does not define a 'datestamp'.
  if (empty($info['datestamp'])) {
    $info['datestamp'] = filemtime($file->filename);
  }
}

/**
 * Define user permissions.
 *
 * This hook can supply permissions that the module defines, so that they
 * can be selected on the user permissions page and used to restrict
 * access to actions the module performs.
 *
 * @return
 *   An array of which permission names are the keys and their corresponding value is a description of the permission
 *
 * The permissions in the array do not need to be wrapped with the function t(),
 * since the string extractor takes care of extracting permission names defined in the perm hook for translation.
 *
 * Permissions are checked using user_access().
 *
 * For a detailed usage example, see page_example.module.
 */
function hook_perm() {
  return array(
    'administer my module' => t('Perform maintenance tasks for my module'),
  );
}

/**
 * Register a module (or theme's) theme implementations.
 *
 * Modules and themes implementing this return an array of arrays. The key
 * to each sub-array is the internal name of the hook, and the array contains
 * info about the hook. Each array may contain the following items:
 *
 * - arguments: (required) An array of arguments that this theme hook uses. This
 *   value allows the theme layer to properly utilize templates. The
 *   array keys represent the name of the variable, and the value will be
 *   used as the default value if not specified to the theme() function.
 *   These arguments must be in the same order that they will be given to
 *   the theme() function.
 * - file: The file the implementation resides in. This file will be included
 *   prior to the theme being rendered, to make sure that the function or
 *   preprocess function (as needed) is actually loaded; this makes it possible
 *   to split theme functions out into separate files quite easily.
 * - path: Override the path of the file to be used. Ordinarily the module or
 *   theme path will be used, but if the file will not be in the default path,
 *   include it here. This path should be relative to the Drupal root
 *   directory.
 * - template: If specified, this theme implementation is a template, and this
 *   is the template file <b>without an extension</b>. Do not put .tpl.php
 *   on this file; that extension will be added automatically by the default
 *   rendering engine (which is PHPTemplate). If 'path', above, is specified,
 *   the template should also be in this path.
 * - function: If specified, this will be the function name to invoke for this
 *   implementation. If neither file nor function is specified, a default
 *   function name will be assumed. For example, if a module registers
 *   the 'node' theme hook, 'theme_node' will be assigned to its function.
 *   If the chameleon theme registers the node hook, it will be assigned
 *   'chameleon_node' as its function.
 * - pattern: A regular expression pattern to be used to allow this theme
 *   implementation to have a dynamic name. The convention is to use __ to
 *   differentiate the dynamic portion of the theme. For example, to allow
 *   forums to be themed individually, the pattern might be: 'forum__'. Then,
 *   when the forum is themed, call: <code>theme(array('forum__' . $tid, 'forum'),
 *   $forum)</code>.
 * - preprocess functions: A list of functions used to preprocess this data.
 *   Ordinarily this won't be used; it's automatically filled in. By default,
 *   for a module this will be filled in as template_preprocess_HOOK. For
 *   a theme this will be filled in as phptemplate_preprocess and
 *   phptemplate_preprocess_HOOK as well as themename_preprocess and
 *   themename_preprocess_HOOK.
 * - override preprocess functions: Set to TRUE when a theme does NOT want the
 *   standard preprocess functions to run. This can be used to give a theme
 *   FULL control over how variables are set. For example, if a theme wants
 *   total control over how certain variables in the page.tpl.php are set,
 *   this can be set to true. Please keep in mind that when this is used
 *   by a theme, that theme becomes responsible for making sure necessary
 *   variables are set.
 * - type: (automatically derived) Where the theme hook is defined:
 *   'module', 'theme_engine', or 'theme'.
 * - theme path: (automatically derived) The directory path of the theme or
 *   module, so that it doesn't need to be looked up.
 * - theme paths: (automatically derived) An array of template suggestions where
 *   .tpl.php files related to this theme hook may be found.
 *
 * The following parameters are all optional.
 *
 * @param $existing
 *   An array of existing implementations that may be used for override
 *   purposes. This is primarily useful for themes that may wish to examine
 *   existing implementations to extract data (such as arguments) so that
 *   it may properly register its own, higher priority implementations.
 * @param $type
 *   What 'type' is being processed. This is primarily useful so that themes
 *   tell if they are the actual theme being called or a parent theme.
 *   May be one of:
 *     - module: A module is being checked for theme implementations.
 *     - base_theme_engine: A theme engine is being checked for a theme which is a parent of the actual theme being used.
 *     - theme_engine: A theme engine is being checked for the actual theme being used.
 *     - base_theme: A base theme is being checked for theme implementations.
 *     - theme: The actual theme in use is being checked.
 * @param $theme
 *   The actual name of theme that is being being checked (mostly only useful for
 *   theme engine).
 * @param $path
 *   The directory path of the theme or module, so that it doesn't need to be
 *   looked up.
 *
 * @return
 *   A keyed array of theme hooks.
 */
function hook_theme($existing, $type, $theme, $path) {
  return array(
    'forum_display' => array(
      'arguments' => array('forums' => NULL, 'topics' => NULL, 'parents' => NULL, 'tid' => NULL, 'sortby' => NULL, 'forum_per_page' => NULL),
    ),
    'forum_list' => array(
      'arguments' => array('forums' => NULL, 'parents' => NULL, 'tid' => NULL),
    ),
    'forum_topic_list' => array(
      'arguments' => array('tid' => NULL, 'topics' => NULL, 'sortby' => NULL, 'forum_per_page' => NULL),
    ),
    'forum_icon' => array(
      'arguments' => array('new_posts' => NULL, 'num_posts' => 0, 'comment_mode' => 0, 'sticky' => 0),
    ),
    'forum_topic_navigation' => array(
      'arguments' => array('node' => NULL),
    ),
  );
}

/**
 * Alter the theme registry information returned from hook_theme().
 *
 * The theme registry stores information about all available theme hooks,
 * including which callback functions those hooks will call when triggered,
 * what template files are exposed by these hooks, and so on.
 *
 * Note that this hook is only executed as the theme cache is re-built.
 * Changes here will not be visible until the next cache clear.
 *
 * The $theme_registry array is keyed by theme hook name, and contains the
 * information returned from hook_theme(), as well as additional properties
 * added by _theme_process_registry().
 *
 * For example:
 * @code
 *  $theme_registry['user_profile'] = array(
 *    'arguments' => array(
 *      'account' => NULL,
 *    ),
 *    'template' => 'modules/user/user-profile',
 *    'file' => 'modules/user/user.pages.inc',
 *    'type' => 'module',
 *    'theme path' => 'modules/user',
 *    'theme paths' => array(
 *      0 => 'modules/user',
 *    ),
 *    'preprocess functions' => array(
 *      0 => 'template_preprocess',
 *      1 => 'template_preprocess_user_profile',
 *     ),
 *   )
 * );
 * @endcode
 *
 * @param $theme_registry
 *   The entire cache of theme registry information, post-processing.
 * @see hook_theme()
 * @see _theme_process_registry()
 */
function hook_theme_registry_alter(&$theme_registry) {
  // Kill the next/previous forum topic navigation links.
  foreach ($theme_registry['forum_topic_navigation']['preprocess functions'] as $key => $value) {
    if ($value = 'template_preprocess_forum_topic_navigation') {
      unset($theme_registry['forum_topic_navigation']['preprocess functions'][$key]);
    }
  }
}

/**
 * Register XML-RPC callbacks.
 *
 * This hook lets a module register callback functions to be called when
 * particular XML-RPC methods are invoked by a client.
 *
 * @return
 *   An array which maps XML-RPC methods to Drupal functions. Each array
 *   element is either a pair of method => function or an array with four
 *   entries:
 *   - The XML-RPC method name (for example, module.function).
 *   - The Drupal callback function (for example, module_function).
 *   - The method signature is an array of XML-RPC types. The first element
 *     of this array is the type of return value and then you should write a
 *     list of the types of the parameters. XML-RPC types are the following
 *     (See the types at http://www.xmlrpc.com/spec):
 *       - "boolean": 0 (false) or 1 (true).
 *       - "double": a floating point number (for example, -12.214).
 *       - "int": a integer number (for example,  -12).
 *       - "array": an array without keys (for example, array(1, 2, 3)).
 *       - "struct": an associative array or an object (for example,
 *          array('one' => 1, 'two' => 2)).
 *       - "date": when you return a date, then you may either return a
 *          timestamp (time(), mktime() etc.) or an ISO8601 timestamp. When
 *          date is specified as an input parameter, then you get an object,
 *          which is described in the function xmlrpc_date
 *       - "base64": a string containing binary data, automatically
 *          encoded/decoded automatically.
 *       - "string": anything else, typically a string.
 *   - A descriptive help string, enclosed in a t() function for translation
 *     purposes.
 *   Both forms are shown in the example.
 */
function hook_xmlrpc() {
  return array(
    'drupal.login' => 'drupal_login',
    array(
      'drupal.site.ping',
      'drupal_directory_ping',
      array('boolean', 'string', 'string', 'string', 'string', 'string'),
      t('Handling ping request'))
  );
}

/**
 * Log an event message
 *
 * This hook allows modules to route log events to custom destinations, such as
 * SMS, Email, pager, syslog, ...etc.
 *
 * @param $log_entry
 *   The log_entry is an associative array containing the following keys:
 *   - type: The type of message for this entry. For contributed modules, this is
 *     normally the module name. Do not use 'debug', use severity WATCHDOG_DEBUG instead.
 *   - user: The user object for the user who was logged in when the event happened.
 *   - request_uri: The Request URI for the page the event happened in.
 *   - referer: The page that referred the use to the page where the event occurred.
 *   - ip: The IP address where the request for the page came from.
 *   - timestamp: The UNIX timetamp of the date/time the event occurred
 *   - severity: One of the following values as defined in RFC 3164 http://www.faqs.org/rfcs/rfc3164.html
 *     WATCHDOG_EMERG     Emergency: system is unusable
 *     WATCHDOG_ALERT     Alert: action must be taken immediately
 *     WATCHDOG_CRITICAL  Critical: critical conditions
 *     WATCHDOG_ERROR     Error: error conditions
 *     WATCHDOG_WARNING   Warning: warning conditions
 *     WATCHDOG_NOTICE    Notice: normal but significant condition
 *     WATCHDOG_INFO      Informational: informational messages
 *     WATCHDOG_DEBUG     Debug: debug-level messages
 *   - link: an optional link provided by the module that called the watchdog() function.
 *   - message: The text of the message to be logged.
 *
 * @return
 *   None.
 */
function hook_watchdog($log_msg) {
  global $base_url;

  $severity_list = array(
    WATCHDOG_EMERG    => t('Emergency'),
    WATCHDOG_ALERT    => t('Alert'),
    WATCHDOG_CRITICAL => t('Critical'),
    WATCHDOG_ERROR    => t('Error'),
    WATCHDOG_WARNING  => t('Warning'),
    WATCHDOG_NOTICE   => t('Notice'),
    WATCHDOG_INFO     => t('Info'),
    WATCHDOG_DEBUG    => t('Debug'),
  );

  $to = "someone@example.com";
  $subject = t('[@site_name] @severity_desc: Alert from your web site', array(
      '@site_name' => variable_get('site_name', 'Drupal'),
      '@severity_desc' => $severity_list[$log['severity']]));

  $message  = "\nSite:         @base_url";
  $message .= "\nSeverity:     (@severity) @severity_desc";
  $message .= "\nTimestamp:    @timestamp";
  $message .= "\nType:         @type";
  $message .= "\nIP Address:   @ip";
  $message .= "\nRequest URI:  @request_uri";
  $message .= "\nReferrer URI: @referer_uri";
  $message .= "\nUser:         (@uid) @name";
  $message .= "\nLink:         @link";
  $message .= "\nMessage:      \n\n@message";

  $message = t($message, array(
    '@base_url'      => $base_url,
    '@severity'      => $log_msg['severity'],
    '@severity_desc' => $severity_list[$log_msg['severity']],
    '@timestamp'     => format_date($log_msg['timestamp']),
    '@type'          => $log_msg['type'],
    '@ip'            => $log_msg['ip'],
    '@request_uri'   => $log_msg['request_uri'],
    '@referer_uri'   => $log_msg['referer'],
    '@uid'           => $log_msg['user']->uid,
    '@name'          => $log_msg['user']->name,
    '@link'          => strip_tags($log_msg['link']),
    '@message'       => strip_tags($log_msg['message']),
  ));

  drupal_mail('emaillog', $to, $subject, $body, $from = NULL, $headers = array());
}

/**
 * Prepare a message based on parameters. @see drupal_mail for more.
 *
 * @param $key
 *   An identifier of the mail.
 * @param $message
 *  An array to be filled in. Keys in this array include:
 *  - 'mail_id':
 *     An id to identify the mail sent. Look into the module source codes
 *     for possible mail_id values.
 *  - 'to':
 *     The mail address or addresses where the message will be send to. The
 *     formatting of this string must comply with RFC 2822.
 *  - 'subject':
 *     Subject of the e-mail to be sent. This must not contain any newline
 *     characters, or the mail may not be sent properly. Empty string when
 *     the hook is invoked.
 *  - 'body':
 *     An array of lines containing the message to be sent. Drupal will format
 *     the correct line endings for you. Empty array when the hook is invoked.
 *  - 'from':
 *     The From, Reply-To, Return-Path and Error-To headers in $headers
 *     are already set to this value (if given).
 *  - 'headers':
 *     Associative array containing the headers to add. This is typically
 *     used to add extra headers (From, Cc, and Bcc).
 * @param $params
 *   An arbitrary array of parameters set by the caller to drupal_mail.
 */
function hook_mail($key, &$message, $params) {
  $account = $params['account'];
  $context = $params['context'];
  $variables = array(
    '%site_name' => variable_get('site_name', 'Drupal'),
    '%username' => $account->name,
  );
  if ($context['hook'] == 'taxonomy') {
    $object = $params['object'];
    $vocabulary = taxonomy_vocabulary_load($object->vid);
    $variables += array(
      '%term_name' => $object->name,
      '%term_description' => $object->description,
      '%term_id' => $object->tid,
      '%vocabulary_name' => $vocabulary->name,
      '%vocabulary_description' => $vocabulary->description,
      '%vocabulary_id' => $vocabulary->vid,
    );
  }

  // Node-based variable translation is only available if we have a node.
  if (isset($params['node'])) {
    $node = $params['node'];
    $variables += array(
      '%uid' => $node->uid,
      '%node_url' => url('node/' . $node->nid, array('absolute' => TRUE)),
      '%node_type' => node_get_types('name', $node),
      '%title' => $node->title,
      '%teaser' => $node->teaser,
      '%body' => $node->body,
    );
  }
  $subject = strtr($context['subject'], $variables);
  $body = strtr($context['message'], $variables);
  $message['subject'] .= str_replace(array("\r", "\n"), '', $subject);
  $message['body'][] = drupal_html_to_text($body);
}

/**
 * Add a list of cache tables to be cleared.
 *
 * This hook allows your module to add cache table names to the list of cache
 * tables that will be cleared by the Clear button on the Performance page or
 * whenever drupal_flush_all_caches is invoked.
 *
 * @see drupal_flush_all_caches()
 *
 * @param None.
 *
 * @return
 *   An array of cache table names.
 */
function hook_flush_caches() {
  return array('cache_example');
}

/**
 * Perform necessary actions after modules are installed.
 *
 * This function differs from hook_install() as it gives all other
 * modules a chance to perform actions when a module is installed,
 * whereas hook_install() will only be called on the module actually
 * being installed.
 *
 * @see hook_install()
 *
 * @param $modules
 *   An array of the installed modules.
 */
function hook_modules_installed($modules) {
  if (in_array('lousy_module', $modules)) {
    variable_set('lousy_module_conflicting_variable', FALSE);
  }
}

/**
 * Perform necessary actions after modules are enabled.
 *
 * This function differs from hook_enable() as it gives all other
 * modules a chance to perform actions when modules are enabled,
 * whereas hook_enable() will only be called on the module actually
 * being enabled.
 *
 * @see hook_enable()
 *
 * @param $modules
 *   An array of the enabled modules.
 */
function hook_modules_enabled($modules) {
  if (in_array('lousy_module', $modules)) {
    drupal_set_message(t('mymodule is not compatible with lousy_module'), 'error');
    mymodule_disable_functionality();
  }
}

/**
 * Perform necessary actions after modules are disabled.
 *
 * This function differs from hook_disable() as it gives all other
 * modules a chance to perform actions when modules are disabled,
 * whereas hook_disable() will only be called on the module actually
 * being disabled.
 *
 * @see hook_disable()
 *
 * @param $modules
 *   An array of the disabled modules.
 */
function hook_modules_disabled($modules) {
  if (in_array('lousy_module', $modules)) {
    mymodule_enable_functionality();
  }
}

/**
 * Perform necessary actions after modules are uninstalled.
 *
 * This function differs from hook_uninstall() as it gives all other
 * modules a chance to perform actions when a module is uninstalled,
 * whereas hook_uninstall() will only be called on the module actually
 * being uninstalled.
 *
 * It is recommended that you implement this module if your module
 * stores data that may have been set by other modules.
 *
 * @see hook_uninstall()
 *
 * @param $modules
 *   The name of the uninstalled module.
 */
function hook_modules_uninstalled($modules) {
  foreach ($modules as $module) {
    db_delete('mymodule_table')
      ->condition('module', $module)
      ->execute();
  }
  mymodule_cache_rebuild();
}

/**
 * custom_url_rewrite_outbound is not a hook, it's a function you can add to
 * settings.php to alter all links generated by Drupal. This function is called from url().
 * This function is called very frequently (100+ times per page) so performance is
 * critical.
 *
 * This function should change the value of $path and $options by reference.
 *
 * @param $path
 *   The alias of the $priginal_path as defined in the database.
 *   If there is no match in the database it'll be the same as $original_path
 * @param $options
 *   An array of link attributes such as querystring and fragment. See url().
 * @param $orignal_path
 *   The unaliased Drupal path that is being linked to.
 */
function custom_url_rewrite_outbound(&$path, &$options, $original_path) {
  global $user;

  // Change all 'node' to 'article'.
  if (preg_match('|^node(/.*)|', $path, $matches)) {
    $path = 'article' . $matches[1];
  }
  // Create a path called 'e' which lands the user on her profile edit page.
  if ($path == 'user/' . $user->uid . '/edit') {
    $path = 'e';
  }

}

/**
 * custom_url_rewrite_inbound is not a hook, it's a function you can add to
 * settings.php to alter incoming requests so they map to a Drupal path.
 * This function is called before modules are loaded and
 * the menu system is initialized and it changes $_GET['q'].
 *
 * This function should change the value of $result by reference.
 *
 * @param $result
 *   The Drupal path based on the database. If there is no match in the database it'll be the same as $path.
 * @param $path
 *   The path to be rewritten.
 * @param $path_language
 *   An optional language code to rewrite the path into.
 */
function custom_url_rewrite_inbound(&$result, $path, $path_language) {
  global $user;

  // Change all article/x requests to node/x
  if (preg_match('|^article(/.*)|', $path, $matches)) {
    $result = 'node' . $matches[1];
  }
  // Redirect a path called 'e' to the user's profile edit page.
  if ($path == 'e') {
    $result = 'user/' . $user->uid . '/edit';
  }
}

/**
 * Load additional information into a file object.
 *
 * file_load() calls this hook to allow modules to load additional information
 * into the $file.
 *
 * @param $file
 *   The file object being loaded.
 * @return
 *   None.
 *
 * @see file_load()
 */
function hook_file_load(&$file) {
  // Add the upload specific data into the file object.
  $values = db_query('SELECT * FROM {upload} u WHERE u.fid = :fid', array(':fid' => $file->fid))->fetch(PDO::FETCH_ASSOC);
  foreach ((array)$values as $key => $value) {
    $file->{$key} = $value;
  }
}

/**
 * Check that files meet a given criteria.
 *
 * This hook lets modules perform additional validation on files. They're able
 * to report a failure by returning one or more error messages.
 *
 * @param $file
 *   The file object being validated.
 * @return
 *   An array of error messages. If there are no problems with the file return
 *   an empty array.
 *
 * @see file_validate()
 */
function hook_file_validate(&$file) {
  $errors = array();

  if (empty($file->filename)) {
    $errors[] = t("The file's name is empty. Please give a name to the file.");
  }
  if (strlen($file->filename) > 255) {
    $errors[] = t("The file's name exceeds the 255 characters limit. Please rename the file and try again.");
  }

  return $errors;
}

/**
 * Respond to a file being added.
 *
 * This hook is called when a file has been added to the database. The hook
 * doesn't distinguish between files created as a result of a copy or those
 * created by an upload.
 *
 * @param $file
 *   The file that has just been created.
 * @return
 *   None.
 *
 * @see file_save()
 */
function hook_file_insert(&$file) {

}

/**
 * Respond to a file being updated.
 *
 * This hook is called when file_save() is called on an existing file.
 *
 * @param $file
 *   The file that has just been updated.
 * @return
 *   None.
 *
 * @see file_save()
 */
function hook_file_update(&$file) {

}

/**
 * Respond to a file that has been copied.
 *
 * @param $file
 *   The newly copied file object.
 * @param $source
 *   The original file before the copy.
 * @return
 *   None.
 *
 * @see file_copy()
 */
function hook_file_copy($file, $source) {

}

/**
 * Respond to a file that has been moved.
 *
 * @param $file
 *   The updated file object after the move.
 * @param $source
 *   The original file object before the move.
 * @return
 *   None.
 *
 * @see file_move()
 */
function hook_file_move($file, $source) {

}

/**
 * Report the number of times a file is referenced by a module.
 *
 * This hook is called to determine if a files is in use. Multiple modules may
 * be referencing the same file and to prevent one from deleting a file used by
 * another this hook is called.
 *
 * @param $file
 *   The file object being checked for references.
 * @return
 *   If the module uses this file return an array with the module name as the
 *   key and the value the number of times the file is used.
 *
 * @see file_delete()
 * @see upload_file_references()
 */
function hook_file_references($file) {
  // If upload.module is still using a file, do not let other modules delete it.
  $count = db_query('SELECT COUNT(*) FROM {upload} WHERE fid = :fid', array(':fid' => $file->fid))->fetchField();
  if ($count) {
    // Return the name of the module and how many references it has to the file.
    return array('upload' => $count);
  }
}

/**
 * Respond to a file being deleted.
 *
 * @param $file
 *   The file that has just been deleted.
 * @return
 *   None.
 *
 * @see file_delete()
 * @see upload_file_delete()
 */
function hook_file_delete($file) {
  // Delete all information associated with the file.
  db_delete('upload')->condition('fid', $file->fid)->execute();
}

/**
 * Respond to a file that has changed status.
 *
 * The typical change in status is from temporary to permanent.
 *
 * @param $file
 *   The file being changed.
 * @return
 *   None.
 *
 * @see hook_file_status()
 */
function hook_file_status($file) {
}

/**
 * Control access to private file downloads and specify HTTP headers.
 *
 * This hook allows modules enforce permissions on file downloads when the
 * private file download method is selected. Modules can also provide headers
 * to specify information like the file's name or MIME type.
 *
 * @param $filepath
 *   String of the file's path.
 * @return
 *   If the user does not have permission to access the file, return -1. If the
 *   user has permission, return an array with the appropriate headers. If the
 *   file is not controlled by the current module, the return value should be
 *   NULL.
 *
 * @see file_download()
 * @see upload_file_download()
 */
function hook_file_download($filepath) {
  // Check if the file is controlled by the current module.
  $filepath = file_create_path($filepath);
  $result = db_query("SELECT f.* FROM {files} f INNER JOIN {upload} u ON f.fid = u.fid WHERE filepath = '%s'", $filepath);
  if ($file = db_fetch_object($result)) {
    if (!user_access('view uploaded files')) {
      return -1;
    }
    return array(
      'Content-Type: ' . $file->filemime,
      'Content-Length: ' . $file->filesize,
    );
  }
}

/**
 * Check installation requirements and do status reporting.
 *
 * This hook has two closely related uses, determined by the $phase argument:
 * checking installation requirements ($phase == 'install')
 * and status reporting ($phase == 'runtime').
 *
 * Note that this hook, like all others dealing with installation and updates,
 * must reside in a module_name.install file, or it will not properly abort
 * the installation of the module if a critical requirement is missing.
 *
 * During the 'install' phase, modules can for example assert that
 * library or server versions are available or sufficient.
 * Note that the installation of a module can happen during installation of
 * Drupal itself (by install.php) with an installation profile or later by hand.
 * As a consequence, install-time requirements must be checked without access
 * to the full Drupal API, because it is not available during install.php.
 * For localisation you should for example use $t = get_t() to
 * retrieve the appropriate localisation function name (t() or st()).
 * If a requirement has a severity of REQUIREMENT_ERROR, install.php will abort
 * or at least the module will not install.
 * Other severity levels have no effect on the installation.
 * Module dependencies do not belong to these installation requirements,
 * but should be defined in the module's .info file.
 *
 * The 'runtime' phase is not limited to pure installation requirements
 * but can also be used for more general status information like maintenance
 * tasks and security issues.
 * The returned 'requirements' will be listed on the status report in the
 * administration section, with indication of the severity level.
 * Moreover, any requirement with a severity of REQUIREMENT_ERROR severity will
 * result in a notice on the the administration overview page.
 *
 * @param $phase
 *   The phase in which hook_requirements is run:
 *   - 'install': the module is being installed.
 *   - 'runtime': the runtime requirements are being checked and shown on the
 *              status report page.
 *
 * @return
 *   A keyed array of requirements. Each requirement is itself an array with
 *   the following items:
 *     - 'title': the name of the requirement.
 *     - 'value': the current value (e.g. version, time, level, ...). During
 *       install phase, this should only be used for version numbers, do not set
 *       it if not applicable.
 *     - 'description': description of the requirement/status.
 *     - 'severity': the requirement's result/severity level, one of:
 *         - REQUIREMENT_INFO:    For info only.
 *         - REQUIREMENT_OK:      The requirement is satisfied.
 *         - REQUIREMENT_WARNING: The requirement failed with a warning.
 *         - REQUIREMENT_ERROR:   The requirement failed with an error.
 */
function hook_requirements($phase) {
  $requirements = array();
  // Ensure translations don't break at install time
  $t = get_t();

  // Report Drupal version
  if ($phase == 'runtime') {
    $requirements['drupal'] = array(
      'title' => $t('Drupal'),
      'value' => VERSION,
      'severity' => REQUIREMENT_INFO
    );
  }

  // Test PHP version
  $requirements['php'] = array(
    'title' => $t('PHP'),
    'value' => ($phase == 'runtime') ? l(phpversion(), 'admin/logs/status/php') : phpversion(),
  );
  if (version_compare(phpversion(), DRUPAL_MINIMUM_PHP) < 0) {
    $requirements['php']['description'] = $t('Your PHP installation is too old. Drupal requires at least PHP %version.', array('%version' => DRUPAL_MINIMUM_PHP));
    $requirements['php']['severity'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == 'runtime') {
    $cron_last = variable_get('cron_last', NULL);

    if (is_numeric($cron_last)) {
      $requirements['cron']['value'] = $t('Last run !time ago', array('!time' => format_interval(REQUEST_TIME - $cron_last)));
    }
    else {
      $requirements['cron'] = array(
        'description' => $t('Cron has not run. It appears cron jobs have not been setup on your system. Please check the help pages for <a href="@url">configuring cron jobs</a>.', array('@url' => 'http://drupal.org/cron')),
        'severity' => REQUIREMENT_ERROR,
        'value' => $t('Never run'),
      );
    }

    $requirements['cron']['description'] .= ' ' . t('You can <a href="@cron">run cron manually</a>.', array('@cron' => url('admin/logs/status/run-cron')));

    $requirements['cron']['title'] = $t('Cron maintenance tasks');
  }

  return $requirements;
}

/**
 * Define the current version of the database schema.
 *
 * A Drupal schema definition is an array structure representing one or
 * more tables and their related keys and indexes. A schema is defined by
 * hook_schema() which must live in your module's .install file.
 *
 * By implementing hook_schema() and specifying the tables your module
 * declares, you can easily create and drop these tables on all
 * supported database engines. You don't have to deal with the
 * different SQL dialects for table creation and alteration of the
 * supported database engines.
 *
 * See the Schema API Handbook at http://drupal.org/node/146843 for
 * details on schema definition structures.
 *
 * @return
 * A schema definition structure array.  For each element of the
 * array, the key is a table name and the value is a table structure
 * definition.
 */
function hook_schema() {
  $schema['node'] = array(
    // example (partial) specification for table "node"
    'description' => t('The base table for nodes.'),
    'fields' => array(
      'nid' => array(
        'description' => t('The primary identifier for a node.'),
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE),
      'vid' => array(
        'description' => t('The current {node_revisions}.vid version identifier.'),
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0),
      'type' => array(
        'description' => t('The {node_type} of this node.'),
        'type' => 'varchar',
        'length' => 32,
        'not null' => TRUE,
        'default' => ''),
      'title' => array(
        'description' => t('The title of this node, always treated a non-markup plain text.'),
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => ''),
      ),
    'indexes' => array(
      'node_changed'        => array('changed'),
      'node_created'        => array('created'),
      ),
    'unique keys' => array(
      'nid_vid' => array('nid', 'vid'),
      'vid'     => array('vid')
      ),
    'primary key' => array('nid'),
  );
  return $schema;
}

/**
 * Perform alterations to existing database schemas.
 *
 * When a module modifies the database structure of another module (by
 * changing, adding or removing fields, keys or indexes), it should
 * implement hook_schema_alter() to update the default $schema to take
 * it's changes into account.
 *
 * See hook_schema() for details on the schema definition structure.
 *
 * @param $schema
 *   Nested array describing the schemas for all modules.
 * @return
 *   None.
 */
function hook_schema_alter(&$schema) {
  // Add field to existing schema.
  $schema['users']['fields']['timezone_id'] = array(
    'type' => 'int',
    'not null' => TRUE,
    'default' => 0,
    'description' => t('Per-user timezone configuration.'),
  );
}

/**
 * Install the current version of the database schema, and any other setup tasks.
 *
 * The hook will be called the first time a module is installed, and the
 * module's schema version will be set to the module's greatest numbered update
 * hook. Because of this, anytime a hook_update_N() is added to the module, this
 * function needs to be updated to reflect the current version of the database
 * schema.
 *
 * See the Schema API documentation at http://drupal.org/node/146843
 * for details on hook_schema, where a database tables are defined.
 *
 * Note that since this function is called from a full bootstrap, all functions
 * (including those in modules enabled by the current page request) are
 * available when this hook is called. Use cases could be displaying a user
 * message, or calling a module function necessary for initial setup, etc.
 *
 * Please be sure that anything added or modified in this function that can
 * be removed during uninstall should be removed with hook_uninstall().
 *
 * @see hook_uninstall()
 */
function hook_install() {
  drupal_install_schema('upload');
}

/**
 * Perform a single update. For each patch which requires a database change add
 * a new hook_update_N() which will be called by update.php.
 *
 * The database updates are numbered sequentially according to the version of Drupal you are compatible with.
 *
 * Schema updates should adhere to the Schema API: http://drupal.org/node/150215
 *
 * Database updates consist of 3 parts:
 * - 1 digit for Drupal core compatibility
 * - 1 digit for your module's major release version (e.g. is this the 5.x-1.* (1) or 5.x-2.* (2) series of your module?)
 * - 2 digits for sequential counting starting with 00
 *
 * The 2nd digit should be 0 for initial porting of your module to a new Drupal
 * core API.
 *
 * Examples:
 * - mymodule_update_5200()
 *   - This is the first update to get the database ready to run mymodule 5.x-2.*.
 * - mymodule_update_6000()
 *   - This is the required update for mymodule to run with Drupal core API 6.x.
 * - mymodule_update_6100()
 *   - This is the first update to get the database ready to run mymodule 6.x-1.*.
 * - mymodule_update_6200()
 *   - This is the first update to get the database ready to run mymodule 6.x-2.*.
 *     Users can directly update from 5.x-2.* to 6.x-2.* and they get all 60XX
 *     and 62XX updates, but not 61XX updates, because those reside in the
 *     6.x-1.x branch only.
 *
 * A good rule of thumb is to remove updates older than two major releases of
 * Drupal.
 *
 * Never renumber update functions.
 *
 * Further information about releases and release numbers:
 * - http://drupal.org/handbook/version-info
 * - http://drupal.org/node/93999 (Overview of contributions branches and tags)
 * - http://drupal.org/handbook/cvs/releases
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory at mymodule.module. Drupal core's updates are implemented
 * using the system module as a name and stored in database/updates.inc.
 *
 * @return An array with the results of the calls to update_sql(). An upate
 *   function can force the current and all later updates for this
 *   module to abort by returning a $ret array with an element like:
 *   $ret['#abort'] = array('success' => FALSE, 'query' => 'What went wrong');
 *   The schema version will not be updated in this case, and all the
 *   aborted updates will continue to appear on update.php as updates that
 *   have not yet been run.
 */
function hook_update_N() {
  $ret = array();
  db_add_field($ret, 'mytable1', 'newcol', array('type' => 'int', 'not null' => TRUE));
  return $ret;
}

/**
 * Remove any information that the module sets.
 *
 * The information that the module should remove includes:
 * - variables that the module has set using variable_set() or system_settings_form()
 * - tables the module has created, using drupal_uninstall_schema()
 * - modifications to existing tables
 *
 * The module should not remove its entry from the {system} table.
 *
 * The uninstall hook will fire when the module gets uninstalled.
 */
function hook_uninstall() {
  drupal_uninstall_schema('upload');
  variable_del('upload_file_types');
}

/**
 * Perform necessary actions after module is enabled.
 *
 * The hook is called everytime module is enabled.
 */
function hook_enable() {
  mymodule_cache_rebuild();
}

/**
 * Perform necessary actions before module is disabled.
 *
 * The hook is called everytime module is disabled.
 */
function hook_disable() {
  mymodule_cache_rebuild();
}

/**
 * @} End of "addtogroup hooks".
 */
