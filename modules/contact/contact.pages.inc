<?php
// $Id$

/**
 * @file
 * User page callbacks for the contact module.
 */


/**
 * Site-wide contact page.
 */
function contact_site_page() {
  $limit = variable_get('contact_threshold_limit', 5);
  $window = variable_get('contact_threshold_window', 3600);

  if (!flood_is_allowed('contact', $limit, $window) && !user_access('administer contact forms')) {
    $output = t("You cannot send more than %limit messages in @interval. Please try again later.", array('%limit' => $limit, '@interval' => format_interval($window)));
  }
  elseif (!db_query("SELECT 1 FROM {contact}")->fetchField()) {
    if (user_access('administer contact forms')) {
      $output = t('The contact form has not been configured. <a href="@add">Add one or more categories</a> to the form.', array('@add' => url('admin/structure/contact/add')));
    }
    else {
      return drupal_not_found();
    }
  }
  else {
    $output = drupal_get_form('contact_site_form');
  }

  return $output;
}

/**
 * Form builder; the site-wide contact form.
 */
function contact_site_form($form, &$form_state) {
  global $user;

  $categories = db_query("SELECT cid, category FROM {contact} ORDER BY weight, category")->fetchAllKeyed();
  $default_category = db_query("SELECT cid FROM {contact} WHERE selected = 1")->fetchField();

  // If there is more than one category available and no default category has
  // been selected, prepend a default placeholder value.
  if (!$default_category) {
    if (count($categories) > 1) {
      $categories = array(0 => t('- Please choose -')) + $categories;
    }
    else {
      $default_category = key($categories);
    }
  }

  if (!$user->uid) {
    $form['#attached']['library'][] = array('system', 'cookie');
    $form['#attached']['js'][] = drupal_get_path('module', 'contact') . '/contact.js';
  }

  $form['#token'] = $user->uid ? $user->name . $user->mail : '';
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Your name'),
    '#maxlength' => 255,
    '#default_value' => $user->uid ? $user->name : '',
    '#required' => TRUE,
  );
  $form['mail'] = array(
    '#type' => 'textfield',
    '#title' => t('Your e-mail address'),
    '#maxlength' => 255,
    '#default_value' => $user->uid ? $user->mail : '',
    '#required' => TRUE,
  );
  $form['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  $form['cid'] = array(
    '#type' => 'select',
    '#title' => t('Category'),
    '#default_value' => $default_category,
    '#options' => $categories,
    '#required' => TRUE,
    '#access' => count($categories) > 1,
  );
  $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message'),
    '#required' => TRUE,
  );
  // We do not allow anonymous users to send themselves a copy
  // because it can be abused to spam people.
  $form['copy'] = array(
    '#type' => 'checkbox',
    '#title' => t('Send yourself a copy.'),
    '#access' => $user->uid,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Send message'),
  );

  return $form;
}

/**
 * Form validation handler for contact_site_form().
 */
function contact_site_form_validate($form, &$form_state) {
  if (!$form_state['values']['cid']) {
    form_set_error('cid', t('You must select a valid category.'));
  }
  if (!valid_email_address($form_state['values']['mail'])) {
    form_set_error('mail', t('You must enter a valid e-mail address.'));
  }
}

/**
 * Form submission handler for contact_site_form().
 */
function contact_site_form_submit($form, &$form_state) {
  global $user, $language;

  $values = $form_state['values'];

  // Save the anonymous user information to a cookie for reuse.
  if (!$user->uid) {
    user_cookie_save($values);
  }

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
  watchdog('mail', '%name-from sent an e-mail regarding %category.', array('%name-from' => $values['name'] . " [$from]", '%category' => $contact['category']));
  drupal_set_message(t('Your message has been sent.'));

  // Jump to home page rather than back to contact page to avoid
  // contradictory messages if flood control has been activated.
  $form_state['redirect'] = '';
}

/**
 * Personal contact page.
 */
function contact_personal_page(stdClass $recipient) {
  global $user;

  $limit = variable_get('contact_threshold_limit', 5);
  $window = variable_get('contact_threshold_window', 3600);

  if (!valid_email_address($user->mail)) {
    $output = t('You need to provide a valid e-mail address to contact other users. Please update your <a href="@url">user information</a> and try again.', array('@url' => url("user/$user->uid/edit", array('query' => drupal_get_destination()))));
  }
  elseif (!flood_is_allowed('contact', $limit, $window) && !user_access('administer contact forms') && !user_access('administer users')) {
    $output = t("You cannot send more than %limit messages in @interval. Please try again later.", array('%limit' => $limit, '@interval' => format_interval($window)));
  }
  else {
    drupal_set_title(t('Contact @username', array('@username' => $recipient->name)), PASS_THROUGH);
    $output = drupal_get_form('contact_personal_form', $recipient);
  }

  return $output;
}

/**
 * Form builder; the personal contact form.
 */
function contact_personal_form($form, &$form_state, stdClass $recipient) {
  global $user;
  $form['#token'] = $user->name . $user->mail;
  $form['recipient'] = array('#type' => 'value', '#value' => $recipient);
  $form['from'] = array('#type' => 'item',
    '#title' => t('From'),
    '#markup' => theme('username', array('account' => $user)) . ' &lt;' . check_plain($user->mail) . '&gt;',
  );
  $form['to'] = array('#type' => 'item',
    '#title' => t('To'),
    '#markup' => theme('username', array('account' => $recipient)),
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
    '#value' => t('Send message'),
  );
  return $form;
}

/**
 * Form submission handler for contact_personal_form().
 */
function contact_personal_form_submit($form, &$form_state) {
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
  drupal_set_message(t('Your message has been sent.'));

  // Back to the requested users profile page.
  $form_state['redirect'] = "user/$account->uid";
}
