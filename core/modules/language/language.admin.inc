<?php

/**
 * @file
 * Administration functions for language.module.
 */

/**
 * User interface for the language overview screen.
 */
function language_admin_overview_form($form, &$form_state) {
  drupal_static_reset('language_list');
  $languages = language_list('language');
  $default = language_default();

  $form['languages'] = array(
    '#languages' => $languages,
    '#language_default' => $default,
    '#tree' => TRUE,
    '#header' => array(
      t('Name'),
      t('Enabled'),
      t('Default'),
      t('Weight'),
      t('Operations'),
    ),
    '#theme' => 'language_admin_overview_form_table',
  );

  foreach ($languages as $langcode => $language) {
    $form['languages'][$langcode]['#weight'] = $language->weight;
    $form['languages'][$langcode]['name'] = array(
      '#markup' => check_plain($language->name),
    );
    $form['languages'][$langcode]['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable @title', array('@title' => $language->name)),
      '#title_display' => 'invisible',
      '#default_value' => (int) $language->enabled,
      '#disabled' => $langcode == $default->language,
    );
    $form['languages'][$langcode]['default'] = array(
      '#type' => 'radio',
      '#parents' => array('site_default'),
      '#title' => t('Set @title as default', array('@title' => $language->name)),
      '#title_display' => 'invisible',
      '#return_value' => $langcode,
      '#default_value' => ($langcode == $default->language ? $langcode : NULL),
      '#id' => 'edit-site-default-' . $langcode,
    );
    $form['languages'][$langcode]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $language->name)),
      '#title_display' => 'invisible',
      '#default_value' => $language->weight,
      '#attributes' => array(
        'class' => array('language-order-weight'),
      ),
    );
    $form['languages'][$langcode]['operations'] = array(
      '#theme_wrappers' => array('language_admin_operations'),
      '#weight' => 100,
    );
    $form['languages'][$langcode]['operations']['edit'] = array(
      '#type' => 'link',
      '#title' => t('edit'),
      '#href' => 'admin/config/regional/language/edit/' . $langcode,
    );
    $form['languages'][$langcode]['operations']['delete'] = array(
      '#type' => 'link',
      '#title' => t('delete'),
      '#href' => 'admin/config/regional/language/delete/' . $langcode,
      '#access' => $langcode != $default->language,
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Returns HTML for operation links in language_admin_overview_form() table.
 *
 * @todo Introduce #type '[table_]operations' or just simply #type 'links'.
 */
function theme_language_admin_operations($variables) {
  $links = array();
  foreach (element_children($variables['elements']) as $key) {
    // Children are only rendered if the current user has access.
    if (isset($variables['elements'][$key]['#children'])) {
      $links[$key] = $variables['elements'][$key]['#children'];
    }
  }
  // If there are links, render a link list.
  if (!empty($links)) {
    return theme('item_list__operations', array(
      'items' => $links,
      'attributes' => array('class' => array('links', 'inline')),
    ));
  }
  // Otherwise, ensure to produce no output.
  return '';
}

/**
 * Returns HTML for the language overview form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_language_admin_overview_form_table($variables) {
  $form = $variables['form'];

  $rows = array();
  foreach (element_children($form, TRUE) as $langcode) {
    $element = &$form[$langcode];
    $row = array(
      'class' => array('draggable'),
    );
    foreach (element_children($element, TRUE) as $column) {
      $cell = &$element[$column];
      $row['data'][] = drupal_render($cell);
    }
    $rows[] = $row;
  }

  $output = theme('table', array(
    'header' => $form['#header'],
    'rows' => $rows,
    'attributes' => array('id' => 'language-order'),
  ));
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('language-order', 'order', 'sibling', 'language-order-weight');

  return $output;
}

/**
 * Process language overview form submissions, updating existing languages.
 */
function language_admin_overview_form_submit($form, &$form_state) {
  $languages = language_list();
  $old_default = language_default();

  foreach ($languages as $langcode => $language) {
    $language->default = ($form_state['values']['site_default'] == $langcode);
    $language->weight = $form_state['values']['languages'][$langcode]['weight'];

    if ($language->default || $old_default->language == $langcode) {
      // Automatically enable the default language and the language
      // which was default previously (because we will not get the
      // value from that disabled checkbox).
      $form_state['values']['languages'][$langcode]['enabled'] = 1;
    }
    $language->enabled = (int) !empty($form_state['values']['languages'][$langcode]['enabled']);

    language_save($language);
  }

  drupal_set_message(t('Configuration saved.'));
}

/**
 * User interface for the language addition screen.
 */
function language_admin_add_form($form, &$form_state) {
  $predefined_languages = language_admin_predefined_list();
  $predefined_languages['custom'] = t('Custom language...');
  $predefined_default = !empty($form_state['values']['predefined_langcode']) ? $form_state['values']['predefined_langcode'] : key($predefined_languages);
  $form['predefined_langcode'] = array(
    '#type' => 'select',
    '#title' => t('Language name'),
    '#default_value' => $predefined_default,
    '#options' => $predefined_languages,
  );
  $form['predefined_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add language'),
    '#limit_validation_errors' => array(array('predefined_langcode'), array('predefined_submit')),
    '#states' => array(
      'invisible' => array(
        'select#edit-predefined-langcode' => array('value' => 'custom'),
      ),
    ),
    '#validate' => array('language_admin_add_predefined_form_validate'),
    '#submit' => array('language_admin_add_predefined_form_submit'),
  );

  $form['custom_language'] = array(
    '#type' => 'container',
    '#states' => array(
      'visible' => array(
        'select#edit-predefined-langcode' => array('value' => 'custom'),
      ),
    ),
  );
  _language_admin_common_controls($form['custom_language']);
  $form['custom_language']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add custom language'),
    '#validate' => array('language_admin_add_custom_form_validate'),
    '#submit' => array('language_admin_add_custom_form_submit'),
  );

  return $form;
}

/**
 * Editing screen for a particular language.
 *
 * @param $langcode
 *   Language code of the language to edit.
 */
function language_admin_edit_form($form, &$form_state, $language) {
  _language_admin_common_controls($form, $language);
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save language')
  );
  $form['#submit'][] = 'language_admin_edit_form_submit';
  $form['#validate'][] = 'language_admin_edit_form_validate';
  return $form;
}

/**
 * Common elements of the language addition and editing form.
 *
 * @param $form
 *   A parent form item (or empty array) to add items below.
 * @param $language
 *   Language object to edit.
 */
function _language_admin_common_controls(&$form, $language = NULL) {
  if (!is_object($language)) {
    $language = new stdClass();
  }
  if (isset($language->language)) {
    $form['langcode_view'] = array(
      '#type' => 'item',
      '#title' => t('Language code'),
      '#markup' => $language->language
    );
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $language->language
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'textfield',
      '#title' => t('Language code'),
      '#maxlength' => 12,
      '#required' => TRUE,
      '#default_value' => @$language->language,
      '#disabled' => (isset($language->language)),
      '#description' => t('Use language codes as <a href="@w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array('@w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
    );
  }
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Language name'),
    '#maxlength' => 64,
    '#default_value' => @$language->name,
    '#required' => TRUE,
  );
  $form['direction'] = array(
    '#type' => 'radios',
    '#title' => t('Direction'),
    '#required' => TRUE,
    '#description' => t('Direction that text in this language is presented.'),
    '#default_value' => @$language->direction,
    '#options' => array(LANGUAGE_LTR => t('Left to right'), LANGUAGE_RTL => t('Right to left'))
  );
  return $form;
}

/**
 * Element specific validator for the Add language button.
 */
function language_admin_add_predefined_form_validate($form, &$form_state) {
  $langcode = $form_state['values']['predefined_langcode'];
  if ($langcode == 'custom') {
    form_set_error('predefined_langcode', t('Fill in the language details and save the language with <em>Add custom language</em>.'));
  }
  else {
    if (language_load($langcode)) {
      form_set_error('predefined_langcode', t('The language %language (%langcode) already exists.', array('%language' => $languages[$langcode]->name, '%langcode' => $langcode)));
    }
  }
}

/**
 * Validate the language addition form on custom language button.
 */
function language_admin_add_custom_form_validate($form, &$form_state) {
  if ($form_state['values']['predefined_langcode'] == 'custom') {
    $langcode = $form_state['values']['langcode'];
    // Reuse the editing form validation routine if we add a custom language.
    language_admin_edit_form_validate($form, $form_state);

    $languages = language_list();
    if (isset($languages[$langcode])) {
     form_set_error('langcode', t('The language %language (%langcode) already exists.', array('%language' => $languages[$langcode]->name, '%langcode' => $langcode)));
    }
  }
  else {
    form_set_error('predefined_langcode', t('Use the <em>Add language</em> button to save a predefined language.'));
  }
}

/**
 * Process the custom language addition form submission.
 */
function language_admin_add_custom_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  // Custom language form.
  $language = (object) array(
    'language' => $langcode,
    'name' => $form_state['values']['name'],
    'direction' => $form_state['values']['direction'],
  );
  language_save($language);
  drupal_set_message(t('The language %language has been created and can now be used.', array('%language' => $form_state['values']['name'])));
  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * Process the predefined language addition form submission.
 */
function language_admin_add_predefined_form_submit($form, &$form_state) {
  // Predefined language selection.
  $langcode = $form_state['values']['predefined_langcode'];
  include_once DRUPAL_ROOT . '/core/includes/standard.inc';
  $predefined = standard_language_list();
  $language = (object) array(
    'language' => $langcode,
  );
  language_save($language);
  drupal_set_message(t('The language %language has been created and can now be used.', array('%language' => t($predefined[$langcode][0]))));
  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * Validate the language editing form. Reused for custom language addition too.
 */
function language_admin_edit_form_validate($form, &$form_state) {
  // Ensure sane field values for langcode and name.
  if (!isset($form['langcode_view']) && preg_match('@[^a-zA-Z_-]@', $form_state['values']['langcode'])) {
    form_set_error('langcode', t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => $form['langcode']['#title'])));
  }
  if ($form_state['values']['name'] != check_plain($form_state['values']['name'])) {
    form_set_error('name', t('%field cannot contain any markup.', array('%field' => $form['name']['#title'])));
  }
}

/**
 * Process the language editing form submission.
 */
function language_admin_edit_form_submit($form, &$form_state) {
  // Prepare a language object for saving
  $languages = language_list();
  $langcode = $form_state['values']['langcode'];
  $language = $languages[$langcode];
  $language->name = $form_state['values']['name'];
  $language->direction = $form_state['values']['direction'];
  language_save($language);
  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * User interface for the language deletion confirmation screen.
 */
function language_admin_delete_form($form, &$form_state, $language) {
  $langcode = $language->language;

  if (language_default()->language == $langcode) {
    drupal_set_message(t('The default language cannot be deleted.'));
    drupal_goto('admin/config/regional/language');
  }

  // For other languages, warn user that data loss is ahead.
  $languages = language_list();

  if (!isset($languages[$langcode])) {
    drupal_not_found();
    drupal_exit();
  }
  else {
    $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
    return confirm_form($form, t('Are you sure you want to delete the language %language?', array('%language' => $languages[$langcode]->name)), 'admin/config/regional/language', t('Deleting a language will remove all interface translations associated with it, and posts in this language will be set to be language neutral. This action cannot be undone.'), t('Delete'), t('Cancel'));
  }
}

/**
 * Process language deletion submissions.
 */
function language_admin_delete_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  $languages = language_list();
  $language = $languages[$langcode];

  $success = language_delete($langcode);

  if ($success) {
    $t_args = array('%language' => $language->name, '%langcode' => $language->language);
    drupal_set_message(t('The %language (%langcode) language has been removed.', $t_args));
  }

  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * Prepare a language code list for unused predefined languages.
 */
function language_admin_predefined_list() {
  include_once DRUPAL_ROOT . '/core/includes/standard.inc';
  $languages = language_list();
  $predefined = standard_language_list();
  foreach ($predefined as $key => $value) {
    if (isset($languages[$key])) {
      unset($predefined[$key]);
      continue;
    }
    $predefined[$key] = t($value[0]);
  }
  asort($predefined);
  return $predefined;
}
