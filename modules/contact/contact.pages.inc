<?php

/**
 * @file
 * User page callbacks for the contact module.
 */


/**
 * Site-wide contact page.
 */
function contact_site_page() {
  global $user;

  if (!flood_is_allowed('contact', variable_get('contact_hourly_threshold', 3))) {
    $output = t("You cannot send more than %number messages per hour. Please try again later.", array('%number' => variable_get('contact_hourly_threshold', 3)));
  }
  else {
    $output = drupal_get_form('contact_mail_page');
  }

  return $output;
}

function contact_mail_page() {
  global $user;

  $form = $categories = array();

  $result = db_query('SELECT cid, category, selected FROM {contact} ORDER BY weight, category');
  while ($category = db_fetch_object($result)) {
    $categories[$category->cid] = $category->category;
    if ($category->selected) {
      $default_category = $category->cid;
    }
  }

  if (count($categories) > 0) {
    $form['#token'] = $user->uid ? $user->name . $user->mail : '';
    $form['contact_information'] = array('#value' => filter_xss_admin(variable_get('contact_form_information', t('You can leave a message using the contact form below.'))));
    $form['name'] = array('#type' => 'textfield',
      '#title' => t('Your name'),
      '#maxlength' => 255,
      '#default_value' => $user->uid ? $user->name : '',
      '#required' => TRUE,
    );
    $form['mail'] = array('#type' => 'textfield',
      '#title' => t('Your e-mail address'),
      '#maxlength' => 255,
      '#default_value' => $user->uid ? $user->mail : '',
      '#required' => TRUE,
    );
    $form['subject'] = array('#type' => 'textfield',
      '#title' => t('Subject'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    if (count($categories) > 1) {
      // If there is more than one category available and no default category has been selected,
      // prepend a default placeholder value.
      if (!isset($default_category)) {
        $default_category = t('- Please choose -');
        $categories = array($default_category) + $categories;
      }
      $form['cid'] = array('#type' => 'select',
        '#title' => t('Category'),
        '#default_value' => $default_category,
        '#options' => $categories,
        '#required' => TRUE,
      );
    }
    else {
      // If there is only one category, store its cid.
      $category_keys = array_keys($categories);
      $form['cid'] = array('#type' => 'value',
        '#value' => array_shift($category_keys),
      );
    }
    $form['message'] = array('#type' => 'textarea',
      '#title' => t('Message'),
      '#required' => TRUE,
    );
    // We do not allow anonymous users to send themselves a copy
    // because it can be abused to spam people.
    if ($user->uid) {
      $form['copy'] = array('#type' => 'checkbox',
        '#title' => t('Send yourself a copy.'),
      );
    }
    else {
      $form['copy'] = array('#type' => 'value', '#value' => FALSE);
    }
    $form['submit'] = array('#type' => 'submit',
      '#value' => t('Send e-mail'),
    );
  }
  else {
    drupal_set_message(t('The contact form has not been configured. <a href="@add">Add one or more categories</a> to the form.', array('@add' => url('admin/build/contact/add'))), 'error');
  }
  return $form;
}

/**
 * Validate the site-wide contact page form submission.
 */
function contact_mail_page_validate($form, &$form_state) {
  if (!$form_state['values']['cid']) {
    form_set_error('cid', t('You must select a valid category.'));
  }
  if (!valid_email_address($form_state['values']['mail'])) {
    form_set_error('mail', t('You must enter a valid e-mail address.'));
  }
}

/**
 * Process the site-wide contact page form submission.
 */
function contact_mail_page_submit($form, &$form_state) {
  global $language;

  $values = $form_state['values'];

  // E-mail address of the sender: as the form field is a text field,
  // all instances of \r and \n have been automatically stripped from it.
  $from = $values['mail'];

  // Load category properties and save form values for email composition.
  $contact = contact_load($values['cid']);
  $values['contact'] = $contact;

  // Send the e-mail to the recipients using the site default language.
  drupal_mail('contact', 'page_mail', $contact['recipients'], language_default(), $values, $from);

  // If the user requests it, send a copy using the current language.
  if ($values['copy']) {
    drupal_mail('contact', 'page_copy', $from, $language, $values, $from);
  }

  // Send an auto-reply if necessary using the current language.
  if ($contact['reply']) {
    drupal_mail('contact', 'page_autoreply', $from, $language, $values, $contact['recipients']);
  }

  flood_register_event('contact');
  watchdog('mail', '%name-from sent an e-mail regarding %category.', array('%name-from' => $values['name'] ." [$from]", '%category' => $contact['category']));
  drupal_set_message(t('Your message has been sent.'));

  // Jump to home page rather than back to contact page to avoid
  // contradictory messages if flood control has been activated.
  $form_state['redirect'] = '';
}

/**
 * Personal contact page.
 */
function contact_user_page($account) {
  global $user;

  if (!valid_email_address($user->mail)) {
    $output = t('You need to provide a valid e-mail address to contact other users. Please update your <a href="@url">user information</a> and try again.', array('@url' => url("user/$user->uid/edit")));
  }
  else if (!flood_is_allowed('contact', variable_get('contact_hourly_threshold', 3))) {
    $output = t('You cannot contact more than %number users per hour. Please try again later.', array('%number' => variable_get('contact_hourly_threshold', 3)));
  }
  else {
    drupal_set_title(check_plain($account->name));
    $output = drupal_get_form('contact_mail_user', $account);
  }

  return $output;
}

function contact_mail_user(&$form_state, $recipient) {
  global $user;
  $form['#token'] = $user->name . $user->mail;
  $form['recipient'] = array('#type' => 'value', '#value' => $recipient);
  $form['from'] = array('#type' => 'item',
    '#title' => t('From'),
    '#value' => theme('username', $user) .' &lt;'. check_plain($user->mail) .'&gt;',
  );
  $form['to'] = array('#type' => 'item',
    '#title' => t('To'),
    '#value' => theme('username', $recipient),
  );
  $form['subject'] = array('#type' => 'textfield',
    '#title' => t('Subject'),
    '#maxlength' => 50,
    '#required' => TRUE,
  );
  $form['message'] = array('#type' => 'textarea',
    '#title' => t('Message'),
    '#rows' => 15,
    '#required' => TRUE,
  );
  $form['copy'] = array('#type' => 'checkbox',
    '#title' => t('Send yourself a copy.'),
  );
  $form['submit'] = array('#type' => 'submit',
    '#value' => t('Send e-mail'),
  );
  return $form;
}

/**
 * Process the personal contact page form submission.
 */
function contact_mail_user_submit($form, &$form_state) {
  global $user, $language;

  $account = $form_state['values']['recipient'];

  // Send from the current user to the requested user.
  $to = $account->mail;
  $from = $user->mail;

  // Save both users and all form values for email composition.
  $values = $form_state['values'];
  $values['account'] = $account;
  $values['user'] = $user;

  // Send the e-mail in the requested user language.
  drupal_mail('contact', 'user_mail', $to, user_preferred_language($account), $values, $from);

  // Send a copy if requested, using current page language.
  if ($form_state['values']['copy']) {
    drupal_mail('contact', 'user_copy', $from, $language, $values, $from);
  }

  flood_register_event('contact');
  watchdog('mail', '%name-from sent %name-to an e-mail.', array('%name-from' => $user->name, '%name-to' => $account->name));
  drupal_set_message(t('The message has been sent.'));

  // Back to the requested users profile page.
  $form_state['redirect'] = "user/$account->uid";
}
