<?php
// $Id: simplenews.admin.inc,v 1.8 2011/01/23 04:50:29 mirodietiker Exp $

/**
 * @file
 * Newsletter admin, subscription admin, simplenews settings
 *
 * @ingroup simplenews
 * @todo Redo all database functions: http://drupal.org/node/224333#dbtng
 * @todo Where possible build re-usable functions wrapped around database calls.
 */

/**
 * Menu callback: Admin form for sent and draft newsletters.
 *
 * @see simplenews_admin_newsletter_submit()
 */
function simplenews_admin_newsletter_issues($form, &$form_state, $action = 'sent') {
/*
  // TODO Fix the delete operation
  if (isset($form_state['input']['operation']) && $form_state['input']['operation'] == 'delete') {
    return simplenews_issue_multiple_delete_confirm($form, $form_state);
  }
*/

  $form['filter'] = simplenews_issue_filter_form();
  $form['#submit'][] = 'simplenews_issue_filter_form_submit';
  $form['filter']['#theme'] = 'simplenews_filter_form';
  $form['admin'] = simplenews_admin_issues();

  return $form;
}

/**
 * Generate issue filters
 */
function simplenews_issue_filters() {
  // Newsletter filter
  $filters['category'] = array(
    'title' => t('Subscribed to'),
    'options' => array(
      'all'   => t('All newsletters'),
      'tid-0'    => t('Unassigned newsletters'),
    ),
  );
  foreach (simplenews_category_list() as $tid => $name) {
    $filters['category']['options']['tid-' . $tid] = $name;
  }

  return $filters;
}

/**
 * Return form for issue filters.
 *
 * @see simplenews_issue_filter_form_submit()
 */
function simplenews_issue_filter_form() {
  // Current filter selections in $session var; stored at form submission
  // Example: array('category' => 'all')
  $session = isset($_SESSION['simplenews_issue_filter']) ? $_SESSION['simplenews_issue_filter'] : _simplenews_issue_filter_default();
  $filters = simplenews_issue_filters();

  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only newsletters which'),
  );

  // Filter values are default
  $form['filters']['category'] = array(
    '#type' => 'select',
    '#title' => $filters['category']['title'],
    '#options' => $filters['category']['options'],
    '#default_value' => $session['category'],
  );
  $form['filters']['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#prefix' => '<span class="spacer" />',
  );
  // Add Reset button if filter is in use
  if ($session != _simplenews_issue_filter_default()) {
    $form['filters']['buttons']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
  }

  return $form;
}

/**
 * Helper function: returns issue filter default settings
 */
function _simplenews_issue_filter_default() {
  return array(
    'category' => 'all',
  );
}

function simplenews_issue_filter_form_submit($form, &$form_state) {
  switch ($form_state['values']['op']) {
    case t('Filter'):
      $_SESSION['simplenews_issue_filter'] = array(
        'category' => $form_state['values']['category'],
      );
      break;
    case t('Reset'):
      $_SESSION['simplenews_issue_filter'] = _simplenews_issue_filter_default();
      break;
  }
}

/**
 * Form builder: Builds a list of newsletters with operations.
 *
 * @see simplenews_admin_issues_validate()
 * @see simplenews_admin_issues_submit()
 */
function simplenews_admin_issues() {
  // Build an 'Update options' form.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $options = array();
  foreach (module_invoke_all('simplenews_issue_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => 'activate',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#submit' => array('simplenews_admin_issues_submit'),
    '#validate' => array('simplenews_admin_issues_validate'),
  );

  if (variable_get('simplenews_last_cron', '')) {
    $form['last_sent'] = array(
      '#value' => '<p>' . format_plural(variable_get('simplenews_last_sent', 0),
                                        'Last batch: 1 mail sent at !time.',
                                        'Last batch: !count mails sent at !time.',
                                        array('!time' => format_date(variable_get('simplenews_last_cron', ''), 'small'), '!count' => variable_get('simplenews_last_sent', 0))) . "</p>\n",
    );
  }
  // Table header. Used as tablesort default
  $header = array(
    'title' => array('data' => t('Title'), 'field' => 'n.title'),
    'category' => array('data' => t('Newsletter category'), 'field' => 'sc.name'),
    'created' => array('data' => t('Created'), 'field' => 'n.created', 'sort' => 'desc'),
    'published' => array('data' => t('Published')),
    'sent' => array('data' => t('Sent')),
    'subscribers' => array('data' => t('Subscribers')),
    'operations' => array('data' => t('Operations')),
  );

  $query = db_select('node', 'n')->extend('PagerDefault')->extend('TableSort');
  simplenews_build_issue_filter_query($query);
  $query->innerJoin('simplenews_newsletter', 'sn', 'n.nid = sn.nid');
  $query->leftJoin('simplenews_category', 'sc', 'sn.tid = sc.tid');
  $query->leftJoin('taxonomy_term_data', 't', 'sc.tid = t.tid');
  //$query->leftJoin('users', 'u', 'ss.uid = u.uid');
  $query->fields('n', array('nid', 'vid', 'title', 'created', 'status'))
    ->fields('sn', array('tid'))
    ->fields('t', array('name'))
    ->limit(30)
    ->orderByHeader($header);
  $query->addField('sn', 'status', 'sent_status');

  $options = array();
  $destination = drupal_get_destination();

  module_load_include('inc', 'simplenews', 'includes/simplenews.mail');
  foreach ($query->execute() as $issue) {
    $categories = simplenews_category_list();
    $subscriber_count = simplenews_count_subscriptions($issue->tid);
    $pending_count = simplenews_count_spool($issue->nid, $issue->vid);
    $send_status = $issue->sent_status == SIMPLENEWS_STATUS_SEND_PENDING ? $subscriber_count - $pending_count : theme('simplenews_status', array('source' => 'sent', 'status' => $issue->sent_status));

    $options[$issue->nid] = array(
      'title' => l($issue->title, 'node/' . $issue->nid),
      'category' => $issue->tid ? $categories[$issue->tid] : t('- Unassigned -'),
      'created' => format_date($issue->created, 'small'),
      'published' => theme('simplenews_status', array('source' => 'published', 'status' => $issue->status)),
      'sent' => $send_status,
      'subscribers' => $subscriber_count,
      'operations' => l(t('edit'), 'node/' . $issue->nid . '/edit', array('query' => drupal_get_destination())),
    );
  }

  $form['issues'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No newsletters available.'),
  );

  $form['pager'] = array('#theme' => 'pager');

  return $form;
}

/**
 * Implements hook_simplenews_issue_operations().
 */
function simplenews_simplenews_issue_operations() {
// @todo: future ideas: 'pause' => t('Pause sending'), 'resume' => t('Resume sending'), 'cancel' => t('Cancel sending'), 'publish' => t('Publish'), 'unpublish' => t('Unpublish'));
  $operations = array(
    'activate' => array(
      'label' => t('Send'),
      'callback' => 'simplenews_issue_send',
    ),
    'inactivate' => array(
      'label' => t('Resend'),
      'callback' => 'simplenews_issue_resend',
    ),
  );
  return $operations;
}

function simplenews_admin_issues_validate($form, &$form_state) {
  if (isset($form_state['input']['operation'])) {
    $nids = array_keys(array_filter($form_state['input']['issues']));
    if (empty($nids)) {
      form_set_error('', t('No items selected.'));
    }
    else {
      switch ($form_state['input']['operation']) {
        case 'send':
        //@todo If 'send': check if selected items are un-sent.

      }
    }
  }
}

function simplenews_admin_issues_submit($form, &$form_state) {
  // Call operation functions as defined in hook_simplenews_issue_operations().
  $operations = module_invoke_all('simplenews_issue_operations');
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked list issues
  $nids = array_filter($form_state['values']['issues']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($nids), $operation['callback arguments']);
    }
    else {
      $args = array($nids);
    }
    call_user_func_array($function, $args);

    drupal_set_message(t('The update has been performed.'));
  }
  else {
    // We need to rebuild the form to go to a second step. For example, to
    // show the confirmation form for the deletion of nodes.
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * ...
 */
function simplenews_issue_send($nids) {
  //TODO
  // check if $nids are not pending: error
  // check if newsletters are not sent: error
  // send newsletter
}

/**
 * ...
 */
function simplenews_issue_resend($nids) {
  //TODO
  // check if $nids are not pending: error
  // send newsletter
}

/**
 * Apply filters for subscription filters based on session.
 *
 * @param $query
 *   A SelectQuery to which the filters should be applied.
 */
function simplenews_build_issue_filter_query(SelectQueryInterface $query) {
  if (isset($_SESSION['simplenews_issue_filter'])) {
    foreach ($_SESSION['simplenews_issue_filter'] as $key => $value) {
      switch ($key) {
        case 'list':
        case 'category':
          if ($value != 'all') {
            list($key, $value) = explode('-', $value, 2);
            $query->condition('sn.' . $key, $value);
          }
          break;
      }
    }
  }
}

/**
 * Menu callback: list admin form with list of available list categories.
 *
 * @ingroup forms
 * @see simplenews_admin_newsletter_categories_submit()
 * @see theme_simplenews_admin_newsletter_categories()
 */
function simplenews_admin_categories() {
  $form['#tree'] = TRUE;
  if ($categories = simplenews_categories_load_multiple()) {
    foreach ($categories as $category) {
      $form[$category->tid]['#category'] = $category;
      $form[$category->tid]['name'] = array('#markup' => check_plain($category->name));
      $form[$category->tid]['weight'] = array('#type' => 'weight', '#delta' => 10, '#default_value' => $category->weight);
      $form[$category->tid]['edit'] = array('#type' => 'link', '#title' => t('edit newsletter category'), '#href' => "admin/structure/simplenews/$category->tid/edit");
    }
  }

  // Only make this form include a submit button and weight if more than one
  // category exists.
  if (count($categories) > 1) {
    $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  }
  elseif (!empty($categories)) {
    $form[$category->tid]['weight'] = array('#type' => 'value', '#value' => 0);
  }
  return $form;
}

function simplenews_admin_categories_submit($form, &$form_state) {
  foreach ($form_state['values'] as $tid => $category) {
    if (is_numeric($tid) && $form[$tid]['#category']->weight != $category['weight']) {
      $form[$tid]['#category']->weight = $category['weight'];
      simplenews_category_save($form[$tid]['#category']);
    }
  }
  drupal_set_message(t('Your configuration has been saved.'));
}

function theme_simplenews_admin_categories($variables) {
  $form = $variables['form'];

  $rows = array();

  foreach (element_children($form) as $key) {
    if (isset($form[$key]['name'])) {
      $category = &$form[$key];

      $row = array();
      $row[] = drupal_render($category['name']);
      if (isset($category['weight'])) {
        $category['weight']['#attributes']['class'] = array('simplenews-category-weight');
        $row[] = drupal_render($category['weight']);
      }
      $row[] = drupal_render($category['edit']);
      $rows[] = array('data' => $row, 'class' => array('draggable'));
    }
  }

  $header = array(t('Newsletter category name'));
  if (isset($form['submit'])) {
    $header[] = t('Weight');
    drupal_add_tabledrag('newsletter-category', 'order', 'self', 'simplenews-category-weight');
  }
  $header[] = array('data' => t('Operations'), 'colspan' => '3');
  return theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No newsletter categories. <a href="@link">Add category</a>.', array('@link' => url('admin/structure/simplenews/add'))), 'attributes' => array('id' => 'newsletter-category'))) . drupal_render_children($form);
}

/**
 * Menu callback: newsletter admin form for newsletter add/edit.
 *
 * @see simplenews_admin_category_form_validate()
 * @see simplenews_admin_category_form_submit()
 */
function simplenews_admin_category_form($form, &$form_state, $edit = array()) {
  if (!is_array($edit)) {
    $edit = (array)$edit;
  }
  $edit += array(
    'tid' => 0,
    'name' => '',
    'description' => '',
    'weight' => '0',
    'new_account' => 'none',
    'opt_inout' => 'double',
    'block' => 1,
    'format' => variable_get('simplenews_format', 'plain'),
    'priority' => variable_get('simplenews_priority', SIMPLENEWS_PRIORITY_NONE),
    'receipt' => variable_get('simplenews_receipt', 0),
    'from_name' => variable_get('simplenews_from_name', variable_get('site_name', 'Drupal')),
    'email_subject' => '[[simplenews-category:name]] [node:title]',
    'from_address' => variable_get('simplenews_from_address', variable_get('site_mail', ini_get('sendmail_from'))),
    'hyperlinks' => 1,
  );

  $form['#category'] = (object) $edit;
  // Check whether we need a deletion confirmation form.
  if (isset($form_state['confirm_delete']) && isset($form_state['values']['tid'])) {
    $category = simplenews_category_load($form_state['values']['tid']);
    return simplenews_admin_category_delete($form, $form_state, $category);
  }
  $form['tid'] = array(
    '#type' => 'value',
    '#value' => $edit['tid'],
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
  );
  $form['weight'] = array(
    '#type' => 'hidden',
    '#value' => $edit['weight'],
  );
  $form['subscription'] = array(
    '#type' => 'fieldset',
    '#title' => t('Subscription settings'),
    '#collapsible' => FALSE,
  );

  // Subscribe at account registration time.
  $options = array(
    'none' => t('None'),
    'on' => t('Default on'),
    'off' => t('Default off'),
    'silent' => t('Silent'),
  );
  $form['subscription']['new_account'] = array(
    '#type' => 'select',
    '#title' => t('Subscribe new account'),
    '#options' => $options,
    '#default_value' => $edit['new_account'],
    '#description' => t('None: This newsletter is not listed on the user registration page.<br />Default on: This newsletter is listed on the user registion page and is selected by default.<br />Default off: This newsletter is listed on the user registion page and is not selected by default.<br />Silent: A new user is automatically subscribed to this newsletter. The newsletter is not listed on the user registration page.'),
  );

  // Type of (un)subsribe confirmation
  $options = array(
    SIMPLENEWS_OPT_INOUT_HIDDEN => t('Hidden'),
    SIMPLENEWS_OPT_INOUT_SINGLE => t('Single'),
    SIMPLENEWS_OPT_INOUT_DOUBLE => t('Double'),
  );
  $form['subscription']['opt_inout'] = array(
    '#type' => 'select',
    '#title' => t('Opt-in/out method'),
    '#options' => $options,
    '#default_value' => $edit['opt_inout'],
    '#description' => t('Hidden: This newsletter does not appear on subscription forms. No unsubscription footer in newsletter.<br /> Single: Users are (un)subscribed immediately, no confirmation email is sent.<br />Double: When (un)subscribing at a subscription form, anonymous users receive an (un)subscription confirmation email. Authenticated users are (un)subscribed immediately.'),
  );

  // Provide subscription block for this category.
  $form['subscription']['block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Subscription block'),
    '#default_value' => $edit['block'],
    '#description' => t('A subscription block will be provided for this newsletter category. Anonymous and authenticated users can subscribe and unsubscribe using this block.'),
  );

  $form['email'] = array(
    '#type' => 'fieldset',
    '#title' => t('Email settings'),
    '#collapsible' => FALSE,
  );
  // Hide format selection if there is nothing to choose.
  // The default format is plain text.
  $format_options = simplenews_format_options();
  if (count($format_options) > 1) {
    $form['email']['format'] = array(
      '#type' => 'radios',
      '#title' => t('Email format'),
      '#default_value' => $edit['format'],
      '#options' => $format_options,
    );
  }
  else {
    $form['email']['format'] = array(
      '#type' => 'hidden',
      '#value' => key($format_options),
    );
    $form['email']['format_text'] = array(
      '#markup' => t('Newsletter emails will be sent in %format format.', array('%format' => $edit['format'])),
    );
  }

  $form['email']['priority'] = array(
    '#type' => 'select',
    '#title' => t('Email priority'),
    '#default_value' => $edit['priority'],
    '#options' => simplenews_get_priority(),
  );
  $form['email']['receipt'] = array(
    '#type' => 'checkbox',
    '#title' => t('Request receipt'),
    '#return_value' => 1,
    '#default_value' => $edit['receipt'],
  );

  // Email sender name
  $form['simplenews_sender_information'] = array(
    '#type' => 'fieldset',
    '#title' => t('Sender information'),
    '#collapsible' => FALSE,
  );
  $form['simplenews_sender_information']['from_name'] = array(
    '#type' => 'textfield',
    '#title' => t('From name'),
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => $edit['from_name'],
  );

  // Email subject
  $form['simplenews_subject'] = array(
    '#type' => 'fieldset',
    '#title' => t('Newsletter subject'),
    '#collapsible' => FALSE,
  );
  $form['simplenews_subject']['token_help'] = array(
    '#title' => t('Replacement patterns'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['simplenews_subject']['token_help']['help'] = array(
    '#markup' => _simplenews_get_token_info(array('simplenews-category', 'node')),
  );

  $form['simplenews_subject']['email_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Email subject'),
    '#size' => 60,
    '#maxlength' => 128,
    '#required' => TRUE,
    '#default_value' => $edit['email_subject'],
  );

  // Email from address
  $form['simplenews_sender_information']['from_address'] = array(
    '#type' => 'textfield',
    '#title' => t('From email address'),
    '#size' => 60,
    '#maxlength' => 128,
    '#required' => TRUE,
    '#default_value' => $edit['from_address'],
  );

  // Type of hyperlinks
  $form['simplenews_hyperlinks'] = array(
    '#type' => 'fieldset',
    '#title' => t('HTML to text conversion'),
    '#collapsible' => FALSE,
    '#description' => t('When your newsletter is sent as plain text, these options will determine how the conversion to text is performed.'),
  );
  $form['simplenews_hyperlinks']['hyperlinks'] = array(
    '#type' => 'radios',
    '#title' => t('Hyperlink conversion'),
    '#options' => array(t('Append hyperlinks as a numbered reference list'), t('Display hyperlinks inline with the text')),
    '#default_value' => $edit['hyperlinks'],
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 50,
  );

  if ($edit['tid']) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 55,
    );
  }
  return $form;
}

function simplenews_admin_category_form_validate($form, &$form_state) {
  if ($form_state['clicked_button']['#value'] != t('Delete')) {

    // Check for valid email address.
    if (!valid_email_address($form_state['values']['from_address'])) {
      form_set_error('from_address', t("The sender's email address you supplied is not valid."));
    }
  }
}

function simplenews_admin_category_form_submit($form, &$form_state) {
  //dpm($form_state);
  $op = isset($form_state['values']['op']) ? $form_state['values']['op'] : '';
  if ($op == t('Delete')) {
    $form_state['redirect'] = 'admin/structure/simplenews/' . $form_state['values']['tid'] . '/delete';
    return;
  }

  $category = (object)$form_state['values'];

  // Create or update taxonomy term.
  $term = new stdClass();
  $term->tid          = $form_state['values']['tid'];
  $term->vid          = variable_get('simplenews_vid', 0);
  $term->name         = $form_state['values']['name'];
  $term->description  = $form_state['values']['description'];
  $term->weight       = $form_state['values']['weight'];
  taxonomy_term_save($term);
  $category->tid = $term->tid;

  switch (simplenews_category_save($category)) {
    case SAVED_NEW:
      drupal_set_message(t('Created new newsletter category %name.', array('%name' => $category->name)));
      watchdog('simplenews', 'Created new newsletter category %name.', array('%name' => $category->name), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/simplenews/' . $category->tid . '/edit'));
      break;

    case SAVED_UPDATED:
      drupal_set_message(t('Updated newsletter category %name.', array('%name' => $category->name)));
      watchdog('simplenews', 'Updated newsletter category %name.', array('%name' => $category->name), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/simplenews/' . $category->tid . '/edit'));
      break;
  }

  $form_state['values']['tid'] = $category->tid;
  $form_state['tid'] = $category->tid;
  $form_state['redirect'] = 'admin/structure/simplenews';
}

/**
 * Menu callback: Delete newsletter category.
 *
 * @see simplenews_admin_category_delete_submit()
 */
function simplenews_admin_category_delete($form, &$form_state, $category) {
  // Store some category values for submit handling.
  $form = array();
  $form['tid'] = array('#type' => 'value', '#value' => $category->tid);
  $form['name'] = array('#type' => 'value', '#value' => $category->name);

  $form['notice'] = array(
    '#markup' => '<p><strong>' . t('Note: All subscriptions associated with this newsletter will be lost.') . '</strong></p>',
  );

  return confirm_form($form,
    t('Are you sure you want to delete category %name?', array('%name' => $category->name)),
    'admin/structure/simplenews',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

function simplenews_admin_category_delete_submit($form, &$form_state) {
  $tid = $form_state['values']['tid'];
  $name = $form_state['values']['name'];

  // Delete newsletter category and associated taxonomy term.
  // Subscriptions are deleted by simplenews_simplenews_category_delete()
  simplenews_category_delete($tid);
  taxonomy_term_delete($tid);
  drupal_set_message(t('Newsletter category %name has been deleted.', array('%name' => $name)));

  $form_state['redirect'] = 'admin/structure/simplenews';
  return;
}

/**
 * Menu callback: Delete multiple subscriptions.
 *
 * @see simplenews_subscription_multiple_delete_confirm_submit()
 * @todo: rework based on node_multiple_delete_confirm().
 * @todo Bug? confirm_form() does not wait for confirmation.
 */
function simplenews_subscription_multiple_delete_confirm($form, &$form_state) {
  $snids = $form_state['input']['subscribers'];
  $form['snids'] = array(
    '#prefix' => '<ul>',
    '#suffix' => '</ul>',
    '#tree' => TRUE,
  );
  // array_filter returns only elements with TRUE values
  foreach (simplenews_subscribers_load_multiple($snids) as $data) {
    $form['snids'][$data->snid] = array(
      '#type' => 'hidden',
      '#value' => $data->snid,
      '#prefix' => '<li>',
      '#suffix' => check_plain($data->mail) . "</li>\n",
    );
  }

  $form['operation'] = array(
    '#type' => 'hidden',
    '#value' => 'delete',
  );
  $form['#submit'][] = 'simplenews_subscription_multiple_delete_confirm_submit';
  $confirm_question = format_plural(count($snids),
                                  'Are you sure you want to delete this subscription?',
                                  'Are you sure you want to delete these subscriptions?');
  return confirm_form($form,
                      $confirm_question,
                      'admin/people/simplenews',
                      t('This action cannot be undone.'),
                      t('Delete'), t('Cancel'));
}

function simplenews_subscription_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    // TODO delete subscriber + delete all subscription of these subscribers
    //simplenews_subscription_delete(array_keys($form_state['values']['snids']));
    //simplenews_subscriber_delete(array_keys($form_state['values']['snids']));
    drupal_set_message(t('The subscriptions have been deleted.'));
  }
  return '';
}

/**
 * Menu callback: Mass subscribe to newsletters.
 *
 * @see simplenews_subscription_list_add_submit()
 * @todo Add 32char description field as subsription source
 */
function simplenews_subscription_list_add($form, &$form_state) {
  global $language;

  $form['emails'] = array(
    '#type' => 'textarea',
    '#title' => t('Email addresses'),
    '#cols' => 60,
    '#rows' => 5,
    '#description' => t('Email addresses must be separated by comma, space or newline.'),
  );
 
  $form['lists'] = array(
    '#type' => 'fieldset',
    '#description' => t('Subscribe to'),
   );
   
  foreach (simplenews_get_mailing_lists(TRUE) as $list) {
    $form['lists'][$list->tid] = array(
      '#type' => 'checkbox',
      '#title' => check_plain($list->name),
      '#description'  => $list->description,
      '#default_value' => FALSE,
    );
  }

  // Include language selection when the site is multilingual.
  // Default value is the empty string which will result in receiving emails
  // in the site's default language.
  if (variable_get('language_count', 1) > 1) {
    $options[''] = t('Site default language');
    $languages = language_list('enabled');
    foreach ($languages[1] as $langcode => $item) {
      $name = t($item->name);
      $options[$langcode] = $name . ($item->native != $name ? ' (' . $item->native . ')' : '');
    }
    $form['language'] = array(
      '#type' => 'radios',
      '#title' => t('Anonymous user preferred language'),
      '#default_value' => '',
      '#options' => $options,
      '#description' => t('New subscriptions will be subscribed with the selected preferred language. The language of existing subscribers is unchanged.'),
    );
  }
  else {
    $form['language'] = array(
      '#type' => 'value',
      '#value' => '',
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Subscribe'),
  );
  return $form;
}

function simplenews_subscription_list_add_submit($form, &$form_state) {
  $added = array();
  $invalid = array();
  // TODO redo filter
  $checked_lists = array_keys(array_filter($form_state['values'],'_simplenews_checked'));
  $langcode = $form_state['values']['language'];

  $emails = preg_split("/[\s,]+/", $form_state['values']['emails']);
  foreach ($emails as $email) {
    $email = trim($email);
    if ($email == '') {
      continue;
    }
    if (valid_email_address($email)) {
      foreach ($checked_lists as $tid) {
        $category = simplenews_category_load($tid);
        simplenews_subscribe_user($email, $category->tid, FALSE, 'mass subscribe', $langcode);
        $added[] = $email;
      }
    }
    else {
      $invalid[] = $email;
    }
  }
  if ($added) {
    $added = implode(", ", $added);
    drupal_set_message(t('The following addresses were added or updated: %added.', array('%added' => $added)));

    $list_names = array();
    foreach ($checked_lists as $tid) {
      $category = simplenews_category_load($tid);
      $list_names[] = $category->name;
    }
    drupal_set_message(t('The addresses were subscribed to the following newsletters: %newsletters.', array('%newsletters' => implode(', ', $list_names))));
  }
  else {
    drupal_set_message(t('No addresses were added.'));
  }
  if ($invalid) {
    $invalid = implode(", ", $invalid);
    drupal_set_message(t('The following addresses were invalid: %invalid.', array('%invalid' => $invalid)), 'error');
  }
}

/**
 * Menu callback: Export email address of subscriptions.
 *
 * @see simplenews_admin_export_after_build()
 * @todo Add checkboxes for subscribed and unsubscribed selection.
 */
function simplenews_subscription_list_export($form, &$form_state) {
  //TODO: Add (un)subscribed as filter to form.
  $form['states'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Status'),
    '#options' => array(
      'active' => t('Active users'),
      'inactive' => t('Inactive users'),
    ),
    '#description' => t('Subscriptions matching the selected states will be exported.'),
    '#required' => TRUE,
  );
  $lists = array();
  foreach (simplenews_get_mailing_lists(TRUE) as $list) {
    $lists[$list->tid] = check_plain($list->name);
  }
  $form['lists'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Subscribed to'),
    '#options' => $lists,
    '#description' => t('Subscriptions matching the selected newsletters will be exported.'),
    '#required' => TRUE,
  );

  // Emails item is initially empty. It serves as place holder. Data is added by
  // simplenews_admin_export_after_build().
  $form['emails'] = array();
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export'),
  );
  $form['#after_build'] = array('simplenews_admin_export_after_build');
  $form['#redirect'] = FALSE;
  return $form;
}

/**
 * Form alter_build callback.
 *
 * Adds export results to the subscriptions export form after submission.
 * @todo Something wrong. Email form element gets reset upon drupal_redirect_form().
 */
function simplenews_admin_export_after_build($form, &$form_state) {
  //TODO: Add (un)subscribed as filter to form.
  if (isset($form_state['values']['op']) && $form_state['values']['op'] == t('Export')) {
    $states = array_filter($form_state['values']['states']);
    $lists = array_filter($form_state['values']['lists']);

    // Build where clause for active/inactive state and newsletter selection.
    if (isset($states['active'])) {
      $active_states[] = 1;
    }
    if (isset($states['inactive'])) {
      $active_states[] = 0;
    }
    $tids = array_keys(array_filter($lists));

    // Get subscription data
    $query = db_select('simplenews_subscriber', 'ss');
    $query->innerJoin('simplenews_subscription', 'si', 'si.snid = ss.snid');
    $query->fields('ss', array('mail'))
      ->condition('ss.activated', $active_states)
      ->condition('si.tid', $tids)
      ->distinct();
    foreach ($query->execute() as $mail) {
      $mails[] = $mail->mail;
    }

    // Build form field containing exported emails.
    // The field will be included in the form where at the ['emails'] place holder.
    if (isset($mails)) {
      $exported_mails = implode(", ", $mails);
    }
    else {
      $exported_mails = t('No addresses were found.');
    }
    $form['emails'] = array(
      '#type' => 'textarea',
      '#title' => t('Export results'),
      '#cols' => 60,
      '#rows' => 5,
      '#value' => $exported_mails,
    );
  }
  return $form;
}

/**
 * Menu callback: Mass subscribe to newsletters.
 *
 * @see simplenews_subscription_list_remove_submit()
 * @todo Add 32char description field as unsubsription source
 */
function simplenews_subscription_list_remove($form, &$form_state) {
  $form['emails'] = array(
    '#type' => 'textarea',
    '#title' => t('Email addresses'),
    '#cols' => 60,
    '#rows' => 5,
    '#description' => t('Email addresses must be separated by comma, space or newline.'),
  );

  $form['lists'] = array(
    '#type' => 'fieldset',
    '#description' => t('Unsubscribe from'),
  );
  
  foreach (simplenews_get_mailing_lists(TRUE) as $list) {
    $form['lists'][$list->tid] = array(
      '#type' => 'checkbox',
      '#title' => check_plain($list->name),
      '#description'  => $list->description,
      '#default_value' => FALSE,
    );
  }
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Unsubscribe'),
  );
  return $form;
}

function simplenews_subscription_list_remove_submit($form, &$form_state) {
  $removed = array();
  $invalid = array();
  // TODO redo filter
  $checked_lists = array_keys(array_filter($form_state['values'],'_simplenews_checked'));

  $emails = preg_split("/[\s,]+/", $form_state['values']['emails']);
  foreach ($emails as $email) {
    $email = trim($email);
    if (valid_email_address($email)) {
      foreach ($checked_lists as $tid) {
        simplenews_unsubscribe_user($email, $tid, FALSE, 'mass unsubscribe');
        $removed[] = $email;
      }
    }
    else {
      $invalid[] = $email;
    }
  }
  if ($removed) {
    $removed = implode(", ", $removed);
    drupal_set_message(t('The following addresses were unsubscribed: %removed.', array('%removed' => $removed)));

    $lists = simplenews_get_mailing_lists(TRUE);
    $list_names = array();
    foreach ($checked_lists as $tid) {
      $list_names[] = $lists[$tid]->name;
    }
    drupal_set_message(t('The addresses were unsubscribed from the following newsletters: %newsletters.', array('%newsletters' => implode(', ', $list_names))));
  }
  else {
    drupal_set_message(t('No addresses were removed.'));
  }
  if ($invalid) {
    $invalid = implode(", ", $invalid);
    drupal_set_message(t('The following addresses were invalid: %invalid.', array('%invalid' => $invalid)), 'error');
  }
}

/**
 * Menu callback: subscription administration.
 */
function simplenews_admin_subscription() {
  if (isset($form_state['input']['operation']) && $form_state['input']['operation'] == 'delete') {
    return simplenews_subscription_multiple_delete_confirm($form, $form_state);
  }
  $form['filter'] = simplenews_subscription_filter_form();
  $form['#submit'][] = 'simplenews_subscription_filter_form_submit';
  $form['filter']['#theme'] = 'simplenews_filter_form';
  $form['admin'] = simplenews_subscription_list_form();

  return $form;
}

/**
 * Menu callback: subscription administration.
 *
 * @see simplenews_subscription_list_form_validate()
 * @see simplenews_subscription_list_form_submit()
 * @todo Subscriber maintanance needs overhaul now we have more data availabale.
 */
function simplenews_subscription_list_form() {
  // Build an 'Update options' form.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $options = array();
  foreach (module_invoke_all('simplenews_subscription_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $options = array('activate' => t('Activate'), 'inactivate' => t('Inactivate'), 'delete' => t('Delete'));
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => 'activate',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#submit' => array('simplenews_subscription_list_form_submit'),
    '#validate' => array('simplenews_subscription_list_form_validate'),
  );

  // Table header. Used as tablesort default
  $header = array(
    'mail' => array('data' => t('Email'), 'field' => 'sn.mail', 'sort' => 'asc'),
    'username' => array('data' => t('Username'), 'field' => 'u.name'),
    'status' => array('data' => t('Status'), 'field' => 'sn.activated'),
    'language' => array('data' => t('Language'), 'field' => 'ss.language'),
    'operations' => array('data' => t('Operations')),
  );

  // TODO Make only subscribed user appear on this list. Ignore the unsubscribed records.
  $query = db_select('simplenews_subscriber', 'sn')->extend('PagerDefault')->extend('TableSort');
  simplenews_build_subscription_filter_query($query);
  $query->leftJoin('users', 'u', 'sn.uid = u.uid');
  $query->innerJoin('simplenews_subscription', 'su', 'sn.snid = su.snid');
  $query->addField('u', 'name', 'name');
  $result = $query
    ->fields('sn', array('snid', 'activated', 'mail', 'uid', 'language'))
    ->limit(30)
    ->orderByHeader($header)
    ->execute();

  $options = array();
  $destination = drupal_get_destination();

  foreach ($result as $subscriber) {
    $options[$subscriber->snid] = array(
      'mail' => $subscriber->mail,
      'username' => isset($subscriber->uid) ? l($subscriber->name, 'user/' . $subscriber->uid) : check_plain($subscriber->name),
      'status' => theme('simplenews_status', array('source' => 'activated', 'status' => $subscriber->activated)),
      'language' => check_plain($subscriber->language),
      'operations' => l(t('edit'), 'admin/people/simplenews/users/edit/' . $subscriber->snid, array(), $destination),
    );
  }

  $form['subscribers'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No subscribers available.'),
  );

  $form['pager'] = array('#theme' => 'pager');

  return $form;
}

/**
 * Implements hook_simplenews_subscription_operations().
 */
function simplenews_simplenews_subscription_operations() {
  $operations = array(
    'activate' => array(
      'label' => t('Activate'),
      'callback' => 'simplenews_subscription_activate',
      'callback arguments' => array(SIMPLENEWS_SUBSCRIPTION_ACTIVE),
    ),
    'inactivate' => array(
      'label' => t('Inactivate'),
      'callback' => 'simplenews_subscription_activate',
      'callback arguments' => array(SIMPLENEWS_SUBSCRIPTION_INACTIVE),
    ),
    'delete' => array(
      'label' => t('Delete'),
      'callback' => NULL,
    ),
  );
  return $operations;
}

function simplenews_subscription_list_form_validate($form, &$form_state) {
  if (isset($form_state['values']['operation'])) {
    $snids = array_keys(array_filter($form_state['values']['subscribers']));
    if (empty($snids)) {
      form_set_error('', t('No items selected.'));
    }
  }
}

function simplenews_subscription_list_form_submit($form, &$form_state) {
  // Call operation functions as defined in hook_simplenews_subscription_operations().
  $operations = module_invoke_all('simplenews_subscription_operations');
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked subscribers
  $snids = array_filter($form_state['values']['subscribers']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($snids), $operation['callback arguments']);
    }
    else {
      $args = array($snids);
    }
    call_user_func_array($function, $args);

    drupal_set_message(t('The update has been performed.'));
  }
  else {
    // We need to rebuild the form to go to a second step. For example, to
    // show the confirmation form for the deletion of nodes.
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Callback function to (de-)activate subscriptions.
 *
 * @param $snids
 *   Array of snid's to be activated.
 * @param $status
 *   Status of the subscription (0, 1).
 */
function simplenews_subscription_activate($snids, $status) {
  foreach ($snids as $snid) {
    $subscriber = simplenews_subscriber_load($snid);
    $subscriber->activated = $status;
    simplenews_subscriber_save($subscriber);
  }
}

/**
 * Menu callback: Simplenews admin settings - General.
 */
// TODO Delete this for if there is not more use for it.
function simplenews_admin_settings($form, &$form_state) {
  $vid = variable_get('simplenews_vid', '');
  $form = array();

  $vocabulary_options = array('' => t('- select -'));
  if ($vocabularies = taxonomy_get_vocabularies()) {
    foreach ($vocabularies as $key => $object) {
      $vocabulary_options[$key] = $object->name;
    }
  }
  $form['simplenews_vid'] = array(
    '#type' => 'select',
    '#title' => t('Newsletter vocabulary'),
    '#options' =>  $vocabulary_options,
    '#required' => TRUE,
    '#description' => t('This vocabulary identifies newsletter categories. Note that changing this vocabulary will require existing newsletters to be re-tagged and activated newsletter blocks to be re-activated.'),
    '#default_value' => variable_get('simplenews_vid', ''),
  );

  $field_options = array();
  $field_info = field_info_fields();
  foreach (field_info_instances('node') as $bundle => $instances) {
    if (simplenews_check_node_types(array($bundle))) {
      foreach ($instances as $field_name => $instance) {
        if ($field_info[$field_name]['module'] == 'taxonomy') {
          $field_options[$field_name] = $field_name;
        }
      }
    }
  }
  $form['simplenews_category_field'] = array(
    '#type' => 'select',
    '#title' => t('Newsletter category field'),
    '#options' =>  $field_options,
    '#required' => TRUE,
    '#description' => t('@todo'),
    '#default_value' => variable_get('simplenews_category_field', 'field_simplenews_term'),
  );

  return system_settings_form($form);
}

/**
 * Menu callback: Simplenews admin settings - Newsletter.
 */
function simplenews_admin_settings_newsletter($form, &$form_state) {
  $address_default = variable_get('site_mail', ini_get('sendmail_from'));
  $form = array();

  $form['simplenews_default_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default newsletter options'),
    '#collapsible' => FALSE,
    '#description' => t('These options will be the defaults for new newsletters, but can be overridden in the newsletter editing form.'),
  );
  $description = t('Default newsletter format.');
  if (!module_exists('mimemail')) {
    $description .= ' ' . t('Install <a href="!mime_mail_url">Mime Mail</a> module or <a href="!html_url">HTML Mail</a> module to send newsletters in HTML format.',
                            array('!mime_mail_url' => 'http://drupal.org/project/mimemail', '!html_url' => 'http://drupal.org/project/htmlmail')
                          );
  }
  $form['simplenews_default_options']['simplenews_format'] = array(
    '#type' => 'select',
    '#title' => t('Format'),
    '#options' => simplenews_format_options(),
    '#description' => $description,
    '#default_value' => variable_get('simplenews_format', 'plain'),
  );
  // TODO Do we need these master defaults for 'priority' and 'receipt'?
  $form['simplenews_default_options']['simplenews_priority'] = array(
    '#type' => 'select',
    '#title' => t('Priority'),
    '#options' => simplenews_get_priority(),
    '#description' => t('Note that email priority is ignored by a lot of email programs.'),
    '#default_value' => variable_get('simplenews_priority', SIMPLENEWS_PRIORITY_NONE),
  );
  $form['simplenews_default_options']['simplenews_receipt'] = array(
    '#type' => 'checkbox',
    '#title' => t('Request receipt'),
    '#default_value' => variable_get('simplenews_receipt', 0),
    '#description' => t('Request a Read Receipt from your newsletters. A lot of email programs ignore these so it is not a definitive indication of how many people have read your newsletter.'),
  );
  $form['simplenews_default_options']['simplenews_send'] = array(
    '#type' => 'radios',
    '#title' => t('Default send action'),
    '#options' =>  array(
      SIMPLENEWS_COMMAND_SEND_TEST => t('Send one test newsletter to the test address'),
      SIMPLENEWS_COMMAND_SEND_NOW => t('Send newsletter'),
     ),
    '#default_value' => variable_get('simplenews_send', 0),
  );
  $form['simplenews_test_address'] = array(
    '#type' => 'fieldset',
    '#title' => t('Test addresses'),
    '#collapsible' => FALSE,
    '#description' => t('Supply a comma-separated list of email addresses to be used as test addresses. The override function allows to override these addresses in the newsletter editing form.'),
  );
  $form['simplenews_test_address']['simplenews_test_address'] = array(
    '#type' => 'textfield',
    '#title' => t('Email address'),
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => variable_get('simplenews_test_address', $address_default),
  );
  $form['simplenews_test_address']['simplenews_test_address_override'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow test address override'),
    '#default_value' => variable_get('simplenews_test_address_override', 0),
  );
  $form['simplenews_sender_info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Sender information'),
    '#collapsible' => FALSE,
    '#description' => t('Default sender address that will only be used for confirmation emails. You can specify sender information for each newsletter separately on the newsletter\'s settings page.'),
  );
  $form['simplenews_sender_info']['simplenews_from_name'] = array(
    '#type' => 'textfield',
    '#title' => t('From name'),
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => variable_get('simplenews_from_name', variable_get('site_name', 'Drupal')),
  );
  $form['simplenews_sender_info']['simplenews_from_address'] = array(
    '#type' => 'textfield',
    '#title' => t('From email address'),
    '#size' => 60,
    '#maxlength' => 128,
    '#required' => TRUE,
    '#default_value' => variable_get('simplenews_from_address', $address_default),
  );

  return system_settings_form($form);
}

function simplenews_admin_settings_newsletter_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['simplenews_from_address'])) {
    form_set_error($field_name, t("The sender's email address you supplied is not valid."));
  }
}

/**
 * Menu callback: Simplenews admin settings - Email.
 */
function simplenews_admin_settings_mail($form, &$form_state) {
  $address_default = variable_get('site_mail', ini_get('sendmail_from'));
  $form = array();

  $form['simplenews_mail_backend']['simplenews_use_cron'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use cron to send newsletters'),
    '#default_value' => variable_get('simplenews_use_cron', TRUE),
    '#description' => t('When checked cron will be used to send newsletters (recommended). Test newsletters and confirmation emails will be sent immediately. Leave unchecked for testing purposes.'),
  );
  $throttle = drupal_map_assoc(array(1, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000));
  $throttle[999999] = t('Unlimited');
  if (function_exists('getrusage')) {
    $description_extra = '<br />' . t('Cron execution must not exceed the PHP maximum execution time of %max seconds. You find the time spend to send emails in the <a href="/admin/reports/dblog">Recent log entries</a>.', array('%max' => ini_get('max_execution_time')));
  }
  else {
    $description_extra = '<br />' . t('Cron execution must not exceed the PHP maximum execution time of %max seconds.', array('%max' => ini_get('max_execution_time')));
  }
  $form['simplenews_mail_backend']['simplenews_throttle'] = array(
    '#type' => 'select',
    '#title' => t('Cron throttle'),
    '#options' => $throttle,
    '#default_value' => variable_get('simplenews_throttle', 20),
    '#description' => t('Sets the numbers of newsletters sent per cron run. Failure to send will also be counted.') . $description_extra,
  );
  $form['simplenews_mail_backend']['simplenews_spool_expire'] = array(
    '#type' => 'select',
    '#title' => t('Mail spool expiration'),
    '#options' => array(
      0 => t('Immediate'),
      1 => t('1 day'),
      7 => t('1 week'),
      14 => t('2 weeks'),
    ),
    '#default_value' => variable_get('simplenews_spool_expire', 0),
    '#description' => t('Newsletter mails are spooled. How long must messages be retained in the spool after successful sending. Keeping the message in the spool allows mail statistics (which is not yet implemented). If cron is not used, immediate expiration is advised.'),
  );
  $form['simplenews_mail_backend']['simplenews_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Log emails'),
    '#default_value' => variable_get('simplenews_debug', FALSE),
    '#description' => t('When checked all outgoing simplenews emails are logged in the system log. A logged email does not guarantee that it is send or will be delivered. It only indicates that a message is sent to the PHP mail() function. No status information is available of delivery by the PHP mail() function.'),
  );

  return system_settings_form($form);
}

/**
 * Menu callback: Simplenews admin settings - Subscription.
 */
function simplenews_admin_settings_subscription($form, &$form_state) {
  $address_default = variable_get('site_mail', ini_get('sendmail_from'));
  $form = array();

  $form['simplenews_subscription']['account'] = array(
    '#type' => 'fieldset',
    '#title' => t('User account'),
    '#collapsible' => FALSE,
  );
  $form['simplenews_subscription']['account']['simplenews_sync_account'] = array(
    '#type' => 'checkbox',
    '#title' => t('Synchronize with account'),
    '#default_value' => variable_get('simplenews_sync_account', TRUE),
    '#description' => t('When checked subscriptions will be synchronized with site accounts. When accounts are deleted, subscriptions with the same email address will be removed. When site accounts are blocked/unblocked, subscriptions will be deactivated/activated. When not checked subscriptions will be unchanged when associated accounts are deleted or blocked.'),
  );

  // These email tokens are shared for all settings, so just define
  // the list once to help ensure they stay in sync.

  $form['simplenews_subscription']['subscription_mail'] = array(
    '#type' => 'fieldset',
    '#title' => t('Confirmation emails'),
    '#collapsible' => FALSE,
  );

  $form['simplenews_subscription']['subscription_mail']['token_help'] = array(
    '#title' => t('Replacement patterns'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['simplenews_subscription']['subscription_mail']['token_help']['help'] = array(
    '#markup' => _simplenews_get_token_info(array('simplenews-subscriber', 'simplenews-list', 'site')),
  );

  $form['simplenews_subscription']['subscription_mail']['simplenews_confirm_subscribe_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => simplenews_subscription_confirmation_text('subscribe_subject'),
    '#maxlength' => 180,
  );
  $form['simplenews_subscription']['subscription_mail']['simplenews_confirm_subscribe_unsubscribed'] = array(
    '#type' => 'textarea',
    '#title' => t('Body text of subscribe email'),
    '#default_value' => simplenews_subscription_confirmation_text('subscribe_unsubscribed'),
    '#rows' => 5,
  );
  $form['simplenews_subscription']['subscription_mail']['simplenews_confirm_subscribe_subscribed'] = array(
    '#type' => 'textarea',
    '#title' => t('Body text for already subscribed visitor'),
    '#default_value' => simplenews_subscription_confirmation_text('subscribe_subscribed'),
    '#rows' => 5,
  );
  $form['simplenews_subscription']['subscription_mail']['simplenews_confirm_unsubscribe_subscribed'] = array(
    '#type' => 'textarea',
    '#title' => t('Body text of unsubscribe email'),
    '#default_value' => simplenews_subscription_confirmation_text('unsubscribe_subscribed'),
    '#rows' => 5,
  );
  $form['simplenews_subscription']['subscription_mail']['simplenews_confirm_unsubscribe_unsubscribed'] = array(
    '#type' => 'textarea',
    '#title' => t('Body text for not yet subscribed visitor'),
    '#default_value' => simplenews_subscription_confirmation_text('unsubscribe_unsubscribed'),
    '#rows' => 5,
  );

  $form['simplenews_subscription']['confirm_pages'] = array(
    '#type' => 'fieldset',
    '#title' => t('Confirmation pages'),
    '#collapsible' => FALSE,
  );
  $form['simplenews_subscription']['confirm_pages']['simplenews_confirm_subscribe_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Subscribe confirmation'),
    '#description' => t('Drupal path or URL of the destination page where after the subscription is confirmed (e.g. node/123). Leave empty to go to the front page.'),
    '#default_value' => variable_get('simplenews_confirm_subscribe_page', ''),
  );
  $form['simplenews_subscription']['confirm_pages']['simplenews_confirm_unsubscribe_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Unsubscribe confirmation'),
    '#description' => t('Drupal path or URL of the destination page when the subscription removal is confirmed (e.g. node/123). Leave empty to go to the front page.'),
    '#default_value' => variable_get('simplenews_confirm_unsubscribe_page', ''),
  );

  return system_settings_form($form);
}

/**
 * Generate subscription filters
 */
function simplenews_subscription_filters() {
  // Newsletter filter
  $filters['list'] = array(
    'title' => t('Subscribed to'),
    'options' => array(
      'all'   => t('All newsletters'),
    ),
  );
  foreach (simplenews_get_mailing_lists(TRUE) as $list) {
    $filters['list']['options']['tid-' . $list->tid] = $list->name;
  }

  // Email filter
  $filters['email'] = array(
    'title' => t('Email address'),
  );

  return $filters;
}

/**
 * Return form for subscription filters.
 *
 * @see simplenews_subscription_filter_form_submit()
 */
function simplenews_subscription_filter_form() {
  // Current filter selections in $session var; stored at form submission
  // Example: array('list' => 'all', 'email' => 'hotmail')
  $session = isset($_SESSION['simplenews_subscriptions_filter']) ? $_SESSION['simplenews_subscriptions_filter'] : '';
  $session = is_array($session) ? $session : _simplenews_subscription_filter_default();
  $filters = simplenews_subscription_filters();

  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only subscription which'),
    '#collapsible' => FALSE,
  );

  // Filter values are default
  $form['filters']['list'] = array(
    '#type' => 'select',
    '#title' => $filters['list']['title'],
    '#options' => $filters['list']['options'],
    '#default_value' => $session['list'],
  );
  $form['filters']['email'] = array(
    '#type' => 'textfield',
    '#title' => $filters['email']['title'],
    '#default_value' => $session['email'],
  );
  $form['filters']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#prefix' => '<span class="spacer" />',
  );
  // Add Reset button if filter is in use
  if ($session != _simplenews_subscription_filter_default()) {
    $form['filters']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
  }

  return $form;
}

/**
 * Helper function: returns subscription filter default settings
 */
function _simplenews_subscription_filter_default() {
  return array(
    'list' => 'all',
    'email' => '',
  );
}

function simplenews_subscription_filter_form_submit($form, &$form_state) {
  switch ($form_state['values']['op']) {
    case t('Filter'):
      $_SESSION['simplenews_subscriptions_filter'] = array(
        'list' => $form_state['values']['list'],
        'email' => $form_state['values']['email'],
      );
      break;
    case t('Reset'):
      $_SESSION['simplenews_subscriptions_filter'] = _simplenews_subscription_filter_default();
      break;
  }
}

/**
 * Apply filters for subscription filters based on session.
 *
 * @param $query
 *   A SelectQuery to which the filters should be applied.
 */
function simplenews_build_subscription_filter_query(SelectQueryInterface $query) {
  if (isset($_SESSION['simplenews_subscriptions_filter'])) {
    foreach ($_SESSION['simplenews_subscriptions_filter'] as $key => $value) {
      switch ($key) {
        case 'list':
          if ($value != 'all') {
            list($key, $value) = explode('-', $value, 2);
            $query->condition('su.' . $key, $value);
          }
          break;
        case 'email':
          if (!empty($value)) {
            $query->condition('sn.mail', '%' . $value . '%', 'LIKE');
          }
          break;
      }
    }
  }
}

/**
 * Count number of subscribers per newsletter list.
 *
 * @return number of subscribers.
 */
function simplenews_count_subscriptions($tid) {
  static $subscription_count;

  if (isset($subscription_count[$tid])) {
    return $subscription_count[$tid];
  }
  $subscription_count[$tid] = db_query('SELECT snid FROM {simplenews_subscription} WHERE tid = :tid AND status = :status', array(':tid' => $tid, ':status' => SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED))->rowCount();

  return $subscription_count[$tid];
}

/**
 * Theme subscription administration overview.
 * @todo Rework the use of drupal_render: http://drupal.org/node/224333#menu_callback_array
 * @todo Perhaps use drupal_render_children(): http://drupal.org/node/224333#drupal_render_children
 */
function theme_simplenews_subscription_list($form) {
  // Subscription table header
  $header = array(
    theme('table_select_header_cell'),
    array(
      'data' => t('Email'),
      'field' => 'ss.mail',
      'sort' => 'asc',
    ),
    array(
      'data' => t('Username'),
      'field' => 'u.name',
    ),
    array(
      'data' => t('Status'),
      'field' => 'ss.activated',
    ),
    array(
      'data' => t('Language'),
      'field' => 'ss.language'
    ),
    t('Operations'));

  // Subscription table
  $output = drupal_render($form['options']);
  if (isset($form['mail']) && is_array($form['mail'])) {
    foreach (element_children($form['mail']) as $key) {
      $row = array();
      $row[] = drupal_render($form['snids'][$key]);
      $row[] = drupal_render($form['mail'][$key]);
      $row[] = drupal_render($form['name'][$key]);
      $row[] = drupal_render($form['status'][$key]);
      $row[] = drupal_render($form['operations'][$key]);
      $rows[] = $row;
    }
  }
  else {
    $rows[] = array(array(
        'data' => t('No subscriptions available.'),
        'colspan' => '4',
      ));
  }

  // Render table header, pager and form
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  if ($form['pager']['#value']) {
    $output .= drupal_render($form['pager']);
  }
  $output .= drupal_render($form);

  return $output;
}

/**
 * Return a status image.
 *
 * @param $variables An associative array containing:
 *   source: Source which status will be displayed ('published', 'activated', 'sent')
 *   status: Status of the source (0 or 1)
 *
 * @return string
 *   HTML string containing an image tag.
 */
function theme_simplenews_status($variables) {
  $source = $variables['source'];
  $status = $variables['status'];
  switch ($source) {
    case 'published':
      $images = array(
        0 => 'sn-saved.png',
        1 => 'sn-sent.png',
      );
      $title = array(
        0 => t('Not published'),
        1 => t('Published'),
      );
      break;
    case 'activated':
      $images = array(
        0 => 'sn-saved.png',
        1 => 'sn-sent.png',
      );
      $title = array(
        0 => t('Inactive: no newsletters will be sent'),
        1 => t('Active: user will receive newsletters'),
      );
      break;
    case 'sent':
      $images = array(
        SIMPLENEWS_STATUS_SEND_PENDING => 'sn-cron.png',
        SIMPLENEWS_STATUS_SEND_READY => 'sn-sent.png',
      );
      $title = array(
        SIMPLENEWS_STATUS_SEND_NOT => '-',
        SIMPLENEWS_STATUS_SEND_PENDING => t('Currently sending by cron'),
        SIMPLENEWS_STATUS_SEND_READY => t('Sent'),
      );
      break;
  }

  // Build the output
  if (isset($images[$status])) {
    $img_vars = array(
      'path' => drupal_get_path('module', 'simplenews') . '/' . $images[$status],
      'alt' => $title[$status],
      'title' => $title[$status],
      'getsize' => TRUE,
    );
    $output = theme('image', $img_vars);
  }
  else {
    $output = check_plain($title[$status]);
  }
  return $output;
}

/**
 * Theme simplenews issues and subscriptions filter form.
 *
 * @ingroup themeable
 */
function theme_simplenews_filter_form($variables) {
  $form = $variables['form'];

  $output  = '<div id="simplenews-admin-filter">';
  $output .= drupal_render($form['filters']);
  $output .= '</div>';
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme simplenews subscriptions administration filter form.
 *
 * @ingroup themeable
 */
function theme_simplenews_subscription_filter_form($variables) {
  $form = $variables['form'];

  $output  = '<div id="simplenews-subscription-filter">';
  $output .= drupal_render($form['filters']);
  $output .= '</div>';
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Function to provide the various simplenews mail priorities for newsletter categories
 */
function simplenews_get_priority() {
  return array(
    SIMPLENEWS_PRIORITY_NONE => t('none'),
    SIMPLENEWS_PRIORITY_HIGHEST => t('highest'),
    SIMPLENEWS_PRIORITY_HIGH => t('high'),
    SIMPLENEWS_PRIORITY_NORMAL => t('normal'),
    SIMPLENEWS_PRIORITY_LOW => t('low'),
    SIMPLENEWS_PRIORITY_LOWEST => t('lowest'),
  );
}
