<?php
// $Id: ajax.inc,v 1.22 2010/12/31 23:58:52 merlinofchaos Exp $

// Set this so we can tell that the file has been included at some point.
define('CTOOLS_AJAX_INCLUDED', 1);

/**
 * @file
 * Extend core AJAX with some of our own stuff.
 */

/**
 * Render an image as a button link. This will automatically apply an AJAX class
 * to the link and add the appropriate javascript to make this happen.
 *
 * @param $image
 *   The path to an image to use that will be sent to theme('image') for rendering.
 * @param $dest
 *   The destination of the link.
 * @param $alt
 *   The alt text of the link.
 * @param $class
 *   Any class to apply to the link. @todo this should be a options array.
 */
function ctools_ajax_image_button($image, $dest, $alt, $class = '') {
  return ctools_ajax_text_button(theme('image', array('path' => $image)), $dest, $alt, $class);
}

/**
 * Render text as a link. This will automatically apply an AJAX class
 * to the link and add the appropriate javascript to make this happen.
 *
 * Note: 'html' => true so be sure any text is vetted! Chances are these kinds of buttons will
 * not use user input so this is a very minor concern.
 *
 * @param $text
 *   The text that will be displayed as the link.
 * @param $dest
 *   The destination of the link.
 * @param $alt
 *   The alt text of the link.
 * @param $class
 *   Any class to apply to the link. @todo this should be a options array.
 * @param $type
 *   A type to use, in case a different behavior should be attached. Defaults
 *   to ctools-use-ajax.
 */
function ctools_ajax_text_button($text, $dest, $alt, $class = '', $type = 'use-ajax') {
  drupal_add_js('misc/ajax.js');
  return l($text, $dest, array('html' => TRUE, 'attributes' => array('class' => array($type, $class), 'title' => $alt)));
}

/**
 * Set a single property to a value, on all matched elements.
 *
 * @param $selector
 *   The CSS selector. This can be any selector jquery uses in $().
 * @param $name
 *   The name or key: of the data attached to this selector.
 * @param $value
 *  The value of the data.
 */
 function ctools_ajax_command_attr($selector, $name, $value) {
   return array(
     'command' => 'attr',
     'selector' => $selector,
     'name' => $name,
     'value' => $value,
   );
 }

/**
 * Force a client-side redirect.
 *
 * @param $url
 *   The url to be redirected to. This can be an absolute URL or a
 *   Drupal path.
 * @param $delay
 *   A delay before applying the redirection, in milliseconds.
 * @param $options
 *   An array of options to pass to the url() function.
 */
function ctools_ajax_command_redirect($url, $delay = 0, $options = array()) {
  return array(
    'command' => 'redirect',
    'url' => url($url, $options),
    'delay' => $delay,
  );
}

/**
 * Force a reload of the current page.
 */
function ctools_ajax_command_reload() {
  return array(
    'command' => 'reload',
  );
}

/**
 * Submit a form.
 *
 * This is useful for submitting a parent form after a child form has finished
 * processing in a modal overlay.
 *
 * @param $selector
 *   The CSS selector to identify the form for submission. This can be any
 *   selector jquery uses in $().
 */
function ctools_ajax_command_submit($selector) {
  return array(
    'command' => 'submit',
    'selector' => $selector,
  );
}

/**
 * Send an error response back via AJAX and immediately exit.
 */
function ctools_ajax_render_error($error = '') {
  $commands = array();
  $commands[] = ajax_command_alert($error);
  print ajax_render($commands);
  exit;
}

