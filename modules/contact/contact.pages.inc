<?php

/**
 * @file
 * User page callbacks for the contact module.
 */

/**
 * Form builder; the site-wide contact form.
 *
 * @see contact_site_form_validate()
 * @see contact_site_form_submit()
 */
function contact_site_form($form, &$form_state) {
  global $user;

  // Check if flood control has been activated for sending e-mails.
  $limit = variable_get('contact_threshold_limit', 5);
  $window = variable_get('contact_threshold_window', 3600);
  if (!flood_is_allowed('contact', $limit, $window) && !user_access('administer contact forms')) {
    drupal_set_message(t("You cannot send more than %limit messages in @interval. Try again later.", array('%limit' => $limit, '@interval' => format_interval($window))), 'error');
    drupal_access_denied();
    drupal_exit();
  }

  // Get an array of the categories and the current default category.
  $categories = db_select('contact', 'c')
    ->addTag('translatable')
    ->fields('c', array('cid', 'category'))
    ->orderBy('weight')
    ->orderBy('category')
    ->execute()
    ->fetchAllKeyed();
  $default_category = db_query("SELECT cid FROM {contact} WHERE selected = 1")->fetchField();

  // If there are no categories, do not display the form.
  if (!$categories) {
    if (user_access('administer contact forms')) {
      drupal_set_message(t('The contact form has not been configured. <a href="@add">Add one or more categories</a> to the form.', array('@add' => url('admin/structure/contact/add'))), 'error');
    }
    else {
      drupal_not_found();
      drupal_exit();
    }
  }

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
    $form['#attached']['library'][] = array('system', 'jquery.cookie');
    $form['#attributes']['class'][] = 'user-info-from-cookie';
  }

  $form['#attributes']['class'][] = 'contact-form';
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Your name'),
    '#maxlength' => 255,
    '#default_value' => $user->uid ? format_username($user) : '',
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
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
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
  $values['sender'] = $user;
  $values['sender']->name = $values['name'];
  $values['sender']->mail = $values['mail'];
  $values['category'] = contact_load($values['cid']);

  // Save the anonymous user information to a cookie for reuse.
  if (!$user->uid) {
    user_cookie_save(array_intersect_key($values, array_flip(array('name', 'mail'))));
  }

  // Get the to and from e-mail addresses.
  $to = $values['category']['recipients'];
  $from = $values['sender']->mail;

  // Send the e-mail to the recipients using the site default language.
  drupal_mail('contact', 'page_mail', $to, language_default(), $values, $from);

  // If the user requests it, send a copy using the current language.
  if ($values['copy']) {
    drupal_mail('contact', 'page_copy', $from, $language, $values, $from);
  }

  // Send an auto-reply if necessary using the current language.
  if ($values['category']['reply']) {
    drupal_mail('contact', 'page_autoreply', $from, $language, $values, $to);
  }

  flood_register_event('contact', variable_get('contact_threshold_window', 3600));
  watchdog('mail', '%sender-name (@sender-from) sent an e-mail regarding %category.', array('%sender-name' => $values['name'], '@sender-from' => $from, '%category' => $values['category']['category']));

  // Jump to home page rather than back to contact page to avoid
  // contradictory messages if flood control has been activated.
  drupal_set_message(t('Your message has been sent.'));
  $form_state['redirect'] = '';
}

/**
 * Form builder; the personal contact form.
 *
 * @see contact_personal_form_validate()
 * @see contact_personal_form_submit()
 */
function contact_personal_form($form, &$form_state, $recipient) {
  global $user;

  // Check if flood control has been activated for sending e-mails.
  $limit = variable_get('contact_threshold_limit', 5);
  $window = variable_get('contact_threshold_window', 3600);
  if (!flood_is_allowed('contact', $limit, $window) && !user_access('administer contact forms') && !user_access('administer users')) {
    drupal_set_message(t("You cannot send more than %limit messages in @interval. Try again later.", array('%limit' => $limit, '@interval' => format_interval($window))), 'error');
    drupal_access_denied();
    drupal_exit();
  }

  drupal_set_title(t('Contact @username', array('@username' => format_username($recipient))), PASS_THROUGH);

  if (!$user->uid) {
    $form['#attached']['library'][] = array('system', 'jquery.cookie');
    $form['#attributes']['class'][] = 'user-info-from-cookie';
  }

  $form['#attributes']['class'][] = 'contact-form';
  $form['recipient'] = array(
    '#type' => 'value',
    '#value' => $recipient,
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Your name'),
    '#maxlength' => 255,
    '#default_value' => $user->uid ? format_username($user) : '',
    '#required' => TRUE,
  );
  $form['mail'] = array(
    '#type' => 'textfield',
    '#title' => t('Your e-mail address'),
    '#maxlength' => 255,
    '#default_value' => $user->uid ? $user->mail : '',
    '#required' => TRUE,
  );
  $form['to'] = array(
    '#type' => 'item',
    '#title' => t('To'),
    '#markup' => theme('username', array('account' => $recipient)),
  );
  $form['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#maxlength' => 50,
    '#required' => TRUE,
  );
  $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message'),
    '#rows' => 15,
    '#required' => TRUE,
  );
  // We do not allow anonymous users to send themselves a copy
  // because it can be abused to spam people.
  $form['copy'] = array(
    '#type' => 'checkbox',
    '#title' => t('Send yourself a copy.'),
    '#access' => $user->uid,
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Send message'),
  );
  return $form;
}

/**
 * Form validation handler for contact_personal_form().
 *
 * @see contact_personal_form()
 */
function contact_personal_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['mail'])) {
    form_set_error('mail', t('You must enter a valid e-mail address.'));
  }
}

/**
 * Form submission handler for contact_personal_form().
 *
 * @see contact_personal_form()
 */
function contact_personal_form_submit($form, &$form_state) {
  global $user, $language;

  $values = $form_state['values'];
  $values['sender'] = $user;
  $values['sender']->name = $values['name'];
  $values['sender']->mail = $values['mail'];

  // Save the anonymous user information to a cookie for reuse.
  if (!$user->uid) {
    user_cookie_save(array_intersect_key($values, array_flip(array('name', 'mail'))));
  }

  // Get the to and from e-mail addresses.
  $to = $values['recipient']->mail;
  $from = $values['sender']->mail;

  // Send the e-mail in the requested user language.
  drupal_mail('contact', 'user_mail', $to, user_preferred_language($values['recipient']), $values, $from);

  // Send a copy if requested, using current page language.
  if ($values['copy']) {
    drupal_mail('contact', 'user_copy', $from, $language, $values, $from);
  }

  flood_register_event('contact', variable_get('contact_threshold_window', 3600));
  watchdog('mail', '%sender-name (@sender-from) sent %recipient-name an e-mail.', array('%sender-name' => $values['name'], '@sender-from' => $from, '%recipient-name' => $values['recipient']->name));

  // Jump to the contacted user's profile page.
  drupal_set_message(t('Your message has been sent.'));
  $form_state['redirect'] = user_access('access user profiles') ? 'user/' . $values['recipient']->uid : '';
}
