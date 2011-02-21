<?php
// $Id: modal.inc,v 1.13 2010/12/31 23:58:52 merlinofchaos Exp $

/**
 * @file
 * Implement a modal form using AJAX.
 *
 * The modal form is implemented primarily from mc.js; this contains the
 * Drupal specific stuff to use it. The modal is fairly generic and can
 * be activated mostly by setting up the right classes, but if you are
 * using the modal you must include links to the images in settings,
 * because the javascript does not inherently know where the images are
 * at.
 *
 * You can accomplish this with this PHP code:
 * @code {
 *   ctools_include('modal');
 *   ctools_modal_add_js();
 * }
 *
 * You can have links and buttons bound to use the modal by adding the
 * class ctools-use-modal.
 *
 * For links, the href of the link will be the destination, with any
 * appearance of /nojs/ converted to /ajax/.
 *
 * For submit buttons, however, the URL is found a different, slightly
 * more complex way. The ID of the item is taken and -url is appended to
 * it to derive a class name. Then, all form elements that contain that
 * class name are founded and their values put together to form a
 * URL.
 *
 * For example, let's say you have an 'add' button, and it has a select
 * form item that tells your system what widget it is adding. If the id
 * of the add button is edit-add, you would place a hidden input with
 * the base of your URL in the form and give it a class of 'edit-add-url'.
 * You would then add 'edit-add-url' as a class to the select widget
 * allowing you to convert this value to the form without posting.
 *
 * If no URL is found, the action of the form will be used and the entire
 * form posted to it.
 */

function ctools_modal_add_js() {
  // Provide a gate so we only do this once.
  static $done = FALSE;
  if ($done) {
    return;
  }

  $settings = array(
    'CToolsModal' => array(
      'loadingText' => t('Loading...'),
      'closeText' => t('Close Window'),
      'closeImage' => theme('image', array(
        'path' => ctools_image_path('icon-close-window.png'),
        'title' => t('Close window'),
        'alt' => t('Close window'),
      )),
      'throbber' => theme('image', array(
          'path' => ctools_image_path('throbber.gif'),
          'title' => t('Loading...'),
          'alt' => t('Loading'),
      )),
    ),
  );

  drupal_add_js($settings, 'setting');
  drupal_add_js('misc/jquery.form.js');
  drupal_add_js('misc/progress.js');
  drupal_add_js('misc/ajax.js');
  ctools_add_js('modal');

  ctools_add_css('modal');
  $done = TRUE;
}

/**
 * @todo this is deprecated
 */
function ctools_modal_add_plugin_js($plugins) {
  $css = array();
  $js = array(drupal_get_path('module', 'ctools') . '/js/dependent.js' => TRUE);
  foreach ($plugins as $subtype) {
    if (isset($subtype['js'])) {
      foreach ($subtype['js'] as $file) {
        if (file_exists($file)) {
          $js[$file] = TRUE;
        }
        else if (file(exists($subtype['path'] . '/' . $file))) {
          $js[$subtype['path'] . '/' . $file] = TRUE;
        }
      }
    }
    if (isset($subtype['css'])) {
      foreach ($subtype['css'] as $file) {
        if (file_exists($file)) {
          $css[$file] = TRUE;
        }
        else if (file(exists($subtype['path'] . '/' . $file))) {
          $css[$subtype['path'] . '/' . $file] = TRUE;
        }
      }
    }
  }

  foreach (array_keys($js) as $file) {
    drupal_add_js($file);
  }
  foreach (array_keys($css) as $file) {
    drupal_add_css($file);
  }
}

/**
 * Place HTML within the modal.
 *
 * @param $title
 *   The title of the modal.
 * @param $html
 *   The html to place within the modal.
 */
function ctools_modal_command_display($title, $html) {
  if (is_array($html)) {
    $html = drupal_render($html);
  }

  return array(
    'command' => 'modal_display',
    'title' => $title,
    'output' => $html,
  );
}

/**
 * Dismiss the modal.
 */
function ctools_modal_command_dismiss() {
  return array(
    'command' => 'modal_dismiss',
  );
}

/**
 * Display loading screen in the modal
 */
function ctools_modal_command_loading() {
  return array(
    'command' => 'modal_loading',
  );
}

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
function ctools_modal_image_button($image, $dest, $alt, $class = '') {
  return ctools_ajax_text_button(theme('image', array('path' => $image)), $dest, $alt, $class, 'ctools-use-modal');
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
 */
function ctools_modal_text_button($text, $dest, $alt, $class = '') {
  return ctools_ajax_text_button($text, $dest, $alt, $class, 'ctools-use-modal');
}

/**
 * Wrap a form so that we can use it properly with AJAX. Essentially if the
 * form wishes to render, it automatically does that, otherwise it returns
 * so we can see submission results.
 *
 * @return
 *   The output of the form, if it was rendered. If $form_state['ajax']
 *   is set, this will use ctools_modal_form_render so it will be
 *   a $command object suitable for ajax_render already.
 *
 *   The return will be NULL if the form was successfully submitted unless
 *   you specifically set re_render = TRUE. If ajax is set the
 *   form will never be redirected.
 */
function ctools_modal_form_wrapper($form_id, &$form_state) {
  // This won't override settings already in.
  $form_state += array(
    're_render' => FALSE,
    'no_redirect' => !empty($form_state['ajax']),
  );

  $output = drupal_build_form($form_id, $form_state);
  if (!empty($form_state['ajax']) && empty($form_state['executed'])) {
    return ctools_modal_form_render($form_state, $output);
  }

  return $output;
}

/**
 * Render a form into an AJAX display.
 */
function ctools_modal_form_render($form_state, $output) {
  if (is_array($output)) {
    $output = drupal_render($output);
  }

  $title = empty($form_state['title']) ? drupal_get_title() : $form_state['title'];

  // If there are messages for the form, render them.
  if ($messages = theme('status_messages')) {
    $output = '<div class="messages">' . $messages . '</div>' . $output;
  }

  $commands = array();
  // If the form has not yet been rendered, render it.
  $commands[] = ctools_modal_command_display($title, $output);
  return $commands;
}

/**
 * Perform a simple modal render and immediately exit.
 *
 * This is primarily used for error displays, since usually modals will
 * contain forms.
 */
function ctools_modal_render($title, $output) {
  $commands = array();
  $commands[] = ctools_modal_command_display($title, $output);
  ajax_render($commands);
}
