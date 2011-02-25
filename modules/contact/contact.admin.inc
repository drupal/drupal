<?php

/**
 * @file
 * Admin page callbacks for the contact module.
 */

/**
 * Categories/list tab.
 */
function contact_admin_categories() {
  $result = db_query('SELECT cid, category, recipients, selected FROM {contact} ORDER BY weight, category');
  $rows = array();
  while ($category = db_fetch_object($result)) {
    $rows[] = array(check_plain($category->category), check_plain($category->recipients), ($category->selected ? t('Yes') : t('No')), l(t('edit'), 'admin/build/contact/edit/'. $category->cid), l(t('delete'), 'admin/build/contact/delete/'. $category->cid));
  }
  $header = array(t('Category'), t('Recipients'), t('Selected'), array('data' => t('Operations'), 'colspan' => 2));

  return theme('table', $header, $rows);
}

/**
 * Category edit page.
 */
function contact_admin_edit($form_state = array(), $op, $contact = NULL) {

  if (empty($contact) || $op == 'add') {
    $contact = array(
      'category' => '',
      'recipients' => '',
      'reply' => '',
      'weight' => 0,
      'selected' => 0,
      'cid' => NULL,
    );
  }
  $form['contact_op'] = array('#type' => 'value', '#value' => $op);
  $form['category'] = array('#type' => 'textfield',
    '#title' => t('Category'),
    '#maxlength' => 255,
    '#default_value' => $contact['category'],
    '#description' => t("Example: 'website feedback' or 'product information'."),
    '#required' => TRUE,
  );
  $form['recipients'] = array('#type' => 'textarea',
    '#title' => t('Recipients'),
    '#default_value' => $contact['recipients'],
    '#description' => t("Example: 'webmaster@example.com' or 'sales@example.com,support@example.com'. To specify multiple recipients, separate each e-mail address with a comma."),
    '#required' => TRUE,
  );
  $form['reply'] = array('#type' => 'textarea',
    '#title' => t('Auto-reply'),
    '#default_value' => $contact['reply'],
    '#description' => t('Optional auto-reply. Leave empty if you do not want to send the user an auto-reply message.'),
  );
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $contact['weight'],
    '#description' => t('When listing categories, those with lighter (smaller) weights get listed before categories with heavier (larger) weights. Categories with equal weights are sorted alphabetically.'),
  );
  $form['selected'] = array('#type' => 'select',
    '#title' => t('Selected'),
    '#options' => array('0' => t('No'), '1' => t('Yes')),
    '#default_value' => $contact['selected'],
    '#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
  );
  $form['cid'] = array('#type' => 'value',
    '#value' => $contact['cid'],
  );
  $form['submit'] = array('#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validate the contact category edit page form submission.
 */
function contact_admin_edit_validate($form, &$form_state) {
  if (empty($form_state['values']['category'])) {
    form_set_error('category', t('You must enter a category.'));
  }
  if (empty($form_state['values']['recipients'])) {
    form_set_error('recipients', t('You must enter one or more recipients.'));
  }
  else {
    $recipients = explode(',', $form_state['values']['recipients']);
    foreach ($recipients as $recipient) {
      if (!valid_email_address(trim($recipient))) {
        form_set_error('recipients', t('%recipient is an invalid e-mail address.', array('%recipient' => $recipient)));
      }
    }
  }
}

/**
 * Process the contact category edit page form submission.
 */
function contact_admin_edit_submit($form, &$form_state) {
  if ($form_state['values']['selected']) {
    // Unselect all other contact categories.
    db_query('UPDATE {contact} SET selected = 0');
  }
  $recipients = explode(',', $form_state['values']['recipients']);
  foreach ($recipients as $key => $recipient) {
    // E-mail address validation has already been done in _validate.
    $recipients[$key] = trim($recipient);
  }
  $form_state['values']['recipients'] = implode(',', $recipients);
  if (empty($form_state['values']['cid']) || $form_state['values']['contact_op'] == 'add') {
    drupal_write_record('contact', $form_state['values']);
    drupal_set_message(t('Category %category has been added.', array('%category' => $form_state['values']['category'])));
    watchdog('mail', 'Contact form: category %category added.', array('%category' => $form_state['values']['category']), WATCHDOG_NOTICE, l(t('view'), 'admin/build/contact'));

  }
  else {
    drupal_write_record('contact', $form_state['values'], 'cid');
    drupal_set_message(t('Category %category has been updated.', array('%category' => $form_state['values']['category'])));
    watchdog('mail', 'Contact form: category %category updated.', array('%category' => $form_state['values']['category']), WATCHDOG_NOTICE, l(t('view'), 'admin/build/contact'));
  }

  $form_state['redirect'] = 'admin/build/contact';
  return;
}

/**
 * Category delete page.
 */
function contact_admin_delete(&$form_state, $contact) {

  $form['contact'] = array(
    '#type' => 'value',
    '#value' => $contact,
  );

  return confirm_form($form, t('Are you sure you want to delete %category?', array('%category' => $contact['category'])), 'admin/build/contact', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Process category delete form submission.
 */
function contact_admin_delete_submit($form, &$form_state) {
  $contact = $form_state['values']['contact'];
  db_query("DELETE FROM {contact} WHERE cid = %d", $contact['cid']);
  drupal_set_message(t('Category %category has been deleted.', array('%category' => $contact['category'])));
  watchdog('mail', 'Contact form: category %category deleted.', array('%category' => $contact['category']), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/build/contact';
  return;
}

function contact_admin_settings() {
  $form['contact_form_information'] = array('#type' => 'textarea',
    '#title' => t('Additional information'),
    '#default_value' => variable_get('contact_form_information', t('You can leave a message using the contact form below.')),
    '#description' => t('Information to show on the <a href="@form">contact page</a>. Can be anything from submission guidelines to your postal address or telephone number.', array('@form' => url('contact'))),
  );
  $form['contact_hourly_threshold'] = array('#type' => 'select',
    '#title' => t('Hourly threshold'),
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50)),
    '#default_value' => variable_get('contact_hourly_threshold', 3),
    '#description' => t('The maximum number of contact form submissions a user can perform per hour.'),
  );
  $form['contact_default_status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable personal contact form by default'),
    '#default_value' => variable_get('contact_default_status', 1),
    '#description' => t('Default status of the personal contact form for new users.'),
  );
  return system_settings_form($form);
}
