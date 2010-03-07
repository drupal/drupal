<?php
// $Id: profile.admin.inc,v 1.39 2010/03/07 08:05:02 webchick Exp $

/**
 * @file
 * Administrative page callbacks for the profile module.
 */

/**
 * Form builder to display a listing of all editable profile fields.
 *
 * @ingroup forms
 * @see profile_admin_overview_submit()
 */
function profile_admin_overview($form) {
  $result = db_query('SELECT title, name, type, category, fid, weight FROM {profile_field} ORDER BY category, weight');

  $categories = array();
  foreach ($result as $field) {
    // Collect all category information
    $categories[] = $field->category;

    // Save all field information
    $form[$field->fid]['name'] = array('#markup' => check_plain($field->name));
    $form[$field->fid]['title'] = array('#markup' => check_plain($field->title));
    $form[$field->fid]['type'] = array('#markup' => $field->type);
    $form[$field->fid]['category'] = array('#type' => 'select', '#default_value' => $field->category, '#options' => array());
    $form[$field->fid]['weight'] = array('#type' => 'weight', '#default_value' => $field->weight);
    $form[$field->fid]['edit'] = array('#type' => 'link', '#title' => t('edit'), '#href' => "admin/config/people/profile/edit/$field->fid");
    $form[$field->fid]['delete'] = array('#type' => 'link', '#title' => t('delete'), '#href' => "admin/config/people/profile/delete/$field->fid");
  }

  // Add the category combo boxes
  $categories = array_unique($categories);
  foreach ($form as $fid => $field) {
    foreach ($categories as $cat => $category) {
      $form[$fid]['category']['#options'][$category] = $category;
    }
  }

  // Display the submit button only when there's more than one field
  if (count($form) > 1) {
    $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  }
  else {
    // Disable combo boxes when there isn't a submit button
    foreach ($form as $fid => $field) {
      unset($form[$fid]['weight']);
      $form[$fid]['category']['#type'] = 'value';
    }
  }
  $form['#tree'] = TRUE;

  // @todo: Any reason this isn't done using an element with #theme = 'links'?
  $addnewfields = '<h2>' . t('Add new field') . '</h2>';
  $addnewfields .= '<ul>';
  foreach (_profile_field_types() as $key => $value) {
    $addnewfields .= '<li>' . l($value, "admin/config/people/profile/add/$key") . '</li>';
  }
  $addnewfields .= '</ul>';
  $form['addnewfields'] = array('#markup' => $addnewfields);

  return $form;
}

/**
 * Submit handler to update changed profile field weights and categories.
 *
 * @see profile_admin_overview()
 */
function profile_admin_overview_submit($form, &$form_state) {
  foreach (element_children($form_state['values']) as $fid) {
    if (is_numeric($fid)) {
      $weight = $form_state['values'][$fid]['weight'];
      $category = $form_state['values'][$fid]['category'];
      if ($weight != $form[$fid]['weight']['#default_value'] || $category != $form[$fid]['category']['#default_value']) {
        db_update('profile_field')
          ->fields(array(
            'weight' => $weight,
            'category' => $category,
          ))
          ->condition('fid', $fid)
          ->execute();
      }
    }
  }

  drupal_set_message(t('Profile fields have been updated.'));
  cache_clear_all();
  menu_rebuild();
}

/**
 * Theme the profile field overview into a drag and drop enabled table.
 *
 * @ingroup themeable
 * @see profile_admin_overview()
 */
function theme_profile_admin_overview($variables) {
  $form = $variables['form'];

  drupal_add_css(drupal_get_path('module', 'profile') . '/profile.css');
  // Add javascript if there's more than one field.
  if (isset($form['submit'])) {
    drupal_add_js(drupal_get_path('module', 'profile') . '/profile.js');
  }

  $rows = array();
  $categories = array();
  $category_number = 0;
  foreach (element_children($form) as $key) {
    // Don't take form control structures.
    if (array_key_exists('category', $form[$key])) {
      $field = &$form[$key];
      $category = $field['category']['#default_value'];

      if (!isset($categories[$category])) {
        // Category classes are given numeric IDs because there's no guarantee
        // class names won't contain invalid characters.
        $categories[$category] = $category_number;
        $category_field['#attributes']['class'] = array('profile-category', 'profile-category-' . $category_number);
        $rows[] = array(array('data' => $category, 'colspan' => 7, 'class' => array('category')));
        $rows[] = array('data' => array(array('data' => '<em>' . t('No fields in this category. If this category remains empty when saved, it will be removed.') . '</em>', 'colspan' => 7)), 'class' => array('category-' . $category_number . '-message', 'category-message', 'category-populated'));

        // Make it draggable only if there is more than one field
        if (isset($form['submit'])) {
          drupal_add_tabledrag('profile-fields', 'order', 'sibling', 'profile-weight', 'profile-weight-' . $category_number);
          drupal_add_tabledrag('profile-fields', 'match', 'sibling', 'profile-category', 'profile-category-' . $category_number);
        }
        $category_number++;
      }

      // Add special drag and drop classes that group fields together.
      $field['weight']['#attributes']['class'] = array('profile-weight', 'profile-weight-' . $categories[$category]);
      $field['category']['#attributes']['class'] = array('profile-category', 'profile-category-' . $categories[$category]);

      // Add the row
      $row = array();
      $row[] = drupal_render($field['title']);
      $row[] = drupal_render($field['name']);
      $row[] = drupal_render($field['type']);
      if (isset($form['submit'])) {
        $row[] = drupal_render($field['category']);
        $row[] = drupal_render($field['weight']);
      }
      $row[] = drupal_render($field['edit']);
      $row[] = drupal_render($field['delete']);
      $rows[] = array('data' => $row, 'class' => array('draggable'));
    }
  }

  $header = array(t('Title'), t('Name'), t('Type'));
  if (isset($form['submit'])) {
    $header[] = t('Category');
    $header[] = t('Weight');
  }
  $header[] = array('data' => t('Operations'), 'colspan' => 2);

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No fields available.'), 'attributes' => array('id' => 'profile-fields')));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Menu callback: Generate a form to add/edit a user profile field.
 *
 * @ingroup forms
 * @see profile_field_form_validate()
 * @see profile_field_form_submit()
 */
function profile_field_form($form, &$form_state, $arg = NULL) {
  if (arg(4) == 'edit') {
    if (is_numeric($arg)) {
      $fid = $arg;

      $edit = db_query('SELECT * FROM {profile_field} WHERE fid = :fid', array('fid' => $fid))->fetchAssoc();

      if (!$edit) {
        drupal_not_found();
        return;
      }
      drupal_set_title(t('Edit %title', array('%title' => $edit['title'])), PASS_THROUGH);
      $form['fid'] = array('#type' => 'value',
        '#value' => $fid,
      );
      $type = $edit['type'];
    }
    else {
      drupal_not_found();
      return;
    }
  }
  else {
    $types = _profile_field_types();
    if (!isset($types[$arg])) {
      drupal_not_found();
      return;
    }
    $type = $arg;
    drupal_set_title(t('Add new %type', array('%type' => $types[$type])), PASS_THROUGH);
    $edit = array('name' => 'profile_');
    $form['type'] = array('#type' => 'value', '#value' => $type);
  }
  $edit += array(
    'category' => '',
    'title' => '',
    'explanation' => '',
    'weight' => 0,
    'page' => '',
    'autocomplete' => '',
    'required' => '',
    'register' => '',
  );
  $form['category'] = array('#type' => 'textfield',
    '#title' => t('Category'),
    '#default_value' => $edit['category'],
    '#autocomplete_path' => 'admin/config/people/profile/autocomplete',
    '#description' => t('The category the new field should be part of. Categories are used to group fields logically. An example category is "Personal information".'),
    '#required' => TRUE,
  );
  $form['title'] = array('#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $edit['title'],
    '#description' => t('The title of the new field. The title will be shown to the user. An example title is "Favorite color".'),
    '#required' => TRUE,
  );
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Form name'),
    '#default_value' => $edit['name'],
    '#description' => t('The name of the field. The form name is not shown to the user but used internally in the HTML code and URLs.
Unless you know what you are doing, it is highly recommended that you prefix the form name with <code>profile_</code> to avoid name clashes with other fields. Spaces or any other special characters except dash (-) and underscore (_) are not allowed. An example name is "profile_favorite_color" or perhaps just "profile_color".'),
    '#required' => TRUE,
  );
  $form['explanation'] = array('#type' => 'textarea',
    '#title' => t('Explanation'),
    '#default_value' => $edit['explanation'],
    '#description' => t('An optional explanation to go with the new field. The explanation will be shown to the user.'),
  );
  if ($type == 'selection') {
    $form['fields']['options'] = array('#type' => 'textarea',
      '#title' => t('Selection options'),
      '#default_value' => isset($edit['options']) ? $edit['options'] : '',
      '#description' => t('A list of all options. Put each option on a separate line. Example options are "red", "blue", "green", etc.'),
    );
  }
  $form['visibility'] = array('#type' => 'radios',
    '#title' => t('Visibility'),
    '#default_value' => isset($edit['visibility']) ? $edit['visibility'] : PROFILE_PUBLIC,
    '#options' => array(PROFILE_HIDDEN => t('Hidden profile field, only accessible by administrators, modules and themes.'), PROFILE_PRIVATE => t('Private field, content only available to privileged users.'), PROFILE_PUBLIC => t('Public field, content shown on profile page but not used on member list pages.'), PROFILE_PUBLIC_LISTINGS => t('Public field, content shown on profile page and on member list pages.')),
  );
  if ($type == 'selection' || $type == 'list' || $type == 'textfield') {
    $form['fields']['page'] = array('#type' => 'textfield',
      '#title' => t('Page title'),
      '#default_value' => $edit['page'],
      '#description' => t('To enable browsing this field by value, enter a title for the resulting page. The word <code>%value</code> will be substituted with the corresponding value. An example page title is "People whose favorite color is %value" . This is only applicable for a public field.'),
    );
  }
  elseif ($type == 'checkbox') {
    $form['fields']['page'] = array('#type' => 'textfield',
      '#title' => t('Page title'),
      '#default_value' => $edit['page'],
      '#description' => t('To enable browsing this field by value, enter a title for the resulting page. An example page title is "People who are employed" . This is only applicable for a public field.'),
    );
  }
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('The weights define the order in which the form fields are shown. Lighter fields "float up" towards the top of the category.'),
  );
  $form['autocomplete'] = array('#type' => 'checkbox',
    '#title' => t('Form will auto-complete while user is typing.'),
    '#default_value' => $edit['autocomplete'],
    '#description' => t('For security, auto-complete will be disabled if the user does not have access to user profiles.'),
  );
  $form['required'] = array('#type' => 'checkbox',
    '#title' => t('The user must enter a value.'),
    '#default_value' => $edit['required'],
  );
  $form['register'] = array('#type' => 'checkbox',
    '#title' => t('Visible in user registration form.'),
    '#default_value' => $edit['register'],
  );

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array('#type' => 'submit',
    '#value' => t('Save field'),
  );
  return $form;
}

/**
 * Validate profile_field_form submissions.
 */
function profile_field_form_validate($form, &$form_state) {
  // Validate the 'field name':
  if (preg_match('/[^a-zA-Z0-9_-]/', $form_state['values']['name'])) {
    form_set_error('name', t('The specified form name contains one or more illegal characters. Spaces or any other special characters except dash (-) and underscore (_) are not allowed.'));
  }

  $users_table = drupal_get_schema('users');
  if (!empty($users_table['fields'][$form_state['values']['name']])) {
    form_set_error('name', t('The specified form name is reserved for use by Drupal.'));
  }
  // Validate the category:
  if (!$form_state['values']['category']) {
    form_set_error('category', t('You must enter a category.'));
  }
  if (strtolower($form_state['values']['category']) == 'account') {
    form_set_error('category', t('The specified category name is reserved for use by Drupal.'));
  }
  $query = db_select('profile_field');
  $query->fields('profile_field', array('fid'));

  if (isset($form_state['values']['fid'])) {
    $query->condition('fid', $form_state['values']['fid'], '<>');
  }
  $query_name = clone $query;

  $title = $query
    ->condition('title', $form_state['values']['title'])
    ->condition('category', $form_state['values']['category'])
    ->execute()
    ->fetchField();
  if ($title) {
    form_set_error('title', t('The specified title is already in use.'));
  }
  $name = $query_name
    ->condition('name', $form_state['values']['name'])
    ->execute()
    ->fetchField();
  if ($name) {
    form_set_error('name', t('The specified name is already in use.'));
  }
  if ($form_state['values']['visibility'] == PROFILE_HIDDEN) {
    if ($form_state['values']['required']) {
      form_set_error('required', t('A hidden field cannot be required.'));
    }
    if ($form_state['values']['register']) {
      form_set_error('register', t('A hidden field cannot be set to visible on the user registration form.'));
    }
  }
}

/**
 * Process profile_field_form submissions.
 */
function profile_field_form_submit($form, &$form_state) {
  if (!isset($form_state['values']['options'])) {
    $form_state['values']['options'] = '';
  }
  if (!isset($form_state['values']['page'])) {
    $form_state['values']['page'] = '';
  }
  // Remove all elements that are not profile_field columns.
  $values = array_intersect_key($form_state['values'], array_flip(array('type', 'category', 'title', 'name', 'explanation', 'visibility', 'page', 'weight', 'autocomplete', 'required', 'register', 'options')));
  if (!isset($form_state['values']['fid'])) {
    db_insert('profile_field')
      ->fields($values)
      ->execute();
    drupal_set_message(t('The field has been created.'));
    watchdog('profile', 'Profile field %field added under category %category.', array('%field' => $form_state['values']['title'], '%category' => $form_state['values']['category']), WATCHDOG_NOTICE, l(t('view'), 'admin/config/people/profile'));
  }
  else {
    db_update('profile_field')
      ->fields($values)
      ->condition('fid', $form_state['values']['fid'])
      ->execute();
    drupal_set_message(t('The field has been updated.'));
  }
  cache_clear_all();
  menu_rebuild();

  $form_state['redirect'] = 'admin/config/people/profile';
  return;
}

/**
 * Menu callback; deletes a field from all user profiles.
 */
function profile_field_delete($form, &$form_state, $fid) {
  $field = db_query("SELECT title FROM {profile_field} WHERE fid = :fid", array(':fid' => $fid))->fetchObject();
  if (!$field) {
    drupal_not_found();
    return;
  }
  $form['fid'] = array('#type' => 'value', '#value' => $fid);
  $form['title'] = array('#type' => 'value', '#value' => $field->title);

  return confirm_form($form,
    t('Are you sure you want to delete the field %field?', array('%field' => $field->title)), 'admin/config/people/profile',
    t('This action cannot be undone. If users have entered values into this field in their profile, these entries will also be deleted. If you want to keep the user-entered data, instead of deleting the field you may wish to <a href="@edit-field">edit this field</a> and change it to a hidden profile field so that it may only be accessed by administrators.', array('@edit-field' => url('admin/config/people/profile/edit/' . $fid))),
    t('Delete'), t('Cancel'));
}

/**
 * Process a field delete form submission.
 */
function profile_field_delete_submit($form, &$form_state) {
  db_delete('profile_field')
    ->condition('fid', $form_state['values']['fid'])
    ->execute();
  db_delete('profile_value')
    ->condition('fid', $form_state['values']['fid'])
    ->execute();

  cache_clear_all();

  drupal_set_message(t('The field %field has been deleted.', array('%field' => $form_state['values']['title'])));
  watchdog('profile', 'Profile field %field deleted.', array('%field' => $form_state['values']['title']), WATCHDOG_NOTICE, l(t('view'), 'admin/config/people/profile'));

  $form_state['redirect'] = 'admin/config/people/profile';
  return;
}

/**
 * Retrieve a pipe delimited string of autocomplete suggestions for profile categories
 */
function profile_admin_settings_autocomplete($string) {
  $matches = array();
  $result = db_select('profile_field')
    ->fields('profile_field', array('category'))
    ->condition('category', db_like($string) . '%', 'LIKE')
    ->range(0, 10)
    ->execute();

  foreach ($result as $data) {
    $matches[$data->category] = check_plain($data->category);
  }
  drupal_json_output($matches);
}
