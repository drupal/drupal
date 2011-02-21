<?php
// $Id: jump-menu.inc,v 1.3 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Provides a simple "jump menu".
 *
 * A jump menu is a select box and an optional 'go' button which can be removed
 * if javascript is in use. Each item is keyed to the href that the button
 * should go to. With javascript, the page is immediately redirected. Without
 * javascript, the form is submitted and a drupal_goto() is given.
 *
 */

/**
 * Generate a jump menu form.
 *
 * This can either be used with drupal_get_form() or directly added to a
 * form. The button provides its own submit handler so by default, other
 * submit handlers will not be called.
 *
 * One note: Do not use #tree = TRUE or it will be unable to find the
 * proper value.
 *
 * @code
 * ctools_include('jump-menu');
 * $output = drupal_get_form('ctools_jump_menu', $targets, $options);
 * @endcode
 *
 * @param $select
 *   An array suitable for use as the #options. The keys will be the direct
 *   URLs that will be jumped to, so you absolutely must encode these using
 *   url() in order for them to work reliably.
 *
 * @param $options
 *   $options may be an array with the following options:
 *   - 'title': The text to display for the #title attribute.
 *   - 'description': The text to display for the #description attribute.
 *   - 'default_value': The text to display for the #default_value attribute.
 *   - 'hide': If TRUE the go button will be set to hide via javascript and
 *     will submit on change.
 *   - 'button': The text to display on the button.
 *   - 'image': If set, an image button will be used instead, and the image
 *     set to this.
 *   - 'inline': If set to TRUE (default) the display will be forced inline.
 */
function ctools_jump_menu($form_state, $select, $options = array()) {
  $options += array(
    'button' => t('Go'),
    'choose' => t('- Choose -'),
    'inline' => TRUE,
    'hide' => TRUE,
  );

  ctools_add_js('jump-menu');

  if (!empty($options['choose'])) {
    $select = array('' => $options['choose']) + $select;
  }

  $form['jump'] = array(
    '#type' => 'select',
    '#options' => $select,
    '#attributes' => array(
      'class' => array('ctools-jump-menu-select'),
    ),
  );

  if (!empty($options['title'])) {
    $form['jump']['#title'] = $options['title'];
  }

  if (!empty($options['description'])) {
    $form['jump']['#description'] = $options['description'];
  }

  if (!empty($options['default_value'])) {
    $form['jump']['#default_value'] = $options['default_value'];
  }

  if (isset($options['image'])) {
    $form['go'] = array(
      '#type' => 'image_button',
      '#src' => $options['image'],
      '#submit' => array('ctools_jump_menu_submit'),
      '#attributes' => array(
        'class' => array('ctools-jump-menu-button'),
      ),
    );
  }
  else {
    $form['go'] = array(
      '#type' => 'submit',
      '#value' => $options['button'],
      '#attributes' => array(
        'class' => array('ctools-jump-menu-button'),
      ),
    );
  }

  if ($options['inline']) {
    $form['jump']['#prefix'] = '<div class="container-inline">';
    $form['go']['#suffix'] = '</div>';
  }

  if ($options['hide']) {
    $form['jump']['#attributes']['class'][] = 'ctools-jump-menu-change';
    $form['go']['#attributes']['class'][] = 'ctools-jump-menu-hide';
  }

  return $form;
}

/**
 * Submit handler for the jump menu.
 *
 * This is normally only invoked upon submit without javascript enabled.
 */
function ctools_jump_menu_submit($form, &$form_state) {
  $form_state['redirect'] = $form_state['values']['jump'];
}
