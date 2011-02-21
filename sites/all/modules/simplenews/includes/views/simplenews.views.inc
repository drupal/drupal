<?php
// $Id: simplenews.views.inc,v 1.3 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views interface for simplenews.
 */

/**
 * Implements hook_views_data().
 */
function simplenews_views_data() {

/* ------------ Definitions for Simplenews mailspool ----------------------*/

  $data['simplenews_mail_spool']['table'] = array(
    'base' => array(
      'field' => 'msid',
      'title' => t('Simplenews mailspool'),
      'help' => t('Spool for temporary storage of newsletter emails.'),
      'weight' => 10,
      'database' => 'default',
    ),
    'group' => t('Simplenews spool'),
  );
  
  $data['simplenews_mail_spool']['msid'] = array(
    'title' => t('Ms ID'),
    'help' => t('The primary identifier for a mail spool record.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_mail_spool']['mail'] = array(
    'title' => t('Subscriber'),
    'help' => t('The formatted email address of mail message receipient.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_mail_spool']['nid'] = array(
    'title' => t('Node ID'),
    'help' => t('The {node}.nid of this newsletter.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('Node'),
    ),
  );
    
  $data['simplenews_mail_spool']['vid'] = array(
    'title' => t('Vid'),
    'help' => t('The {node}.vid of this newsletter.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_mail_spool']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The {term_data}.tid this newsletter issue belongs to.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'label' => t('Issue'),
    ),
  );
    
  $data['simplenews_mail_spool']['snid'] = array(
    'title' => t('Subscriber ID'),
    'help' => t('The {simplenews_subscriber}.snid foreign key for this spool'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'simplenews_subscriber',
      'base field' => 'snid',
      'label' => t('Subscriber'),
    ),
  );
    
  $data['simplenews_mail_spool']['status'] = array(
    'title' => t('Sent status'),
    'help' => t('The sent status of the email (0 = hold, 1 = pending, 2 = done).'),
    'field' => array(
      'handler' => 'simplenews_handler_field_newsletter_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_newsletter_status',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_mail_spool']['error'] = array(
    'title' => t('Error'),
    'help' => t('A boolean indicating whether an error occured while sending the email.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_mail_spool']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The time status was set or changed.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
  );
    
  $data['simplenews_mail_spool']['data'] = array(
    'title' => t('Data'),
    'help' => t('A serialized array of name value pairs that are related to the email address.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

/* ------------ Definitions for Simplenews issue ----------------------*/

  $data['simplenews_newsletter']['table'] = array(
    // Define the base group of this table. Fields that don't
    // have a group defined will go into this field by default.
    'group' => t('Simplenews issue'),
  );
  
  // Joins
  $data['simplenews_newsletter']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
    'taxonomy_term_data' => array(
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  $data['simplenews_newsletter']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The {term_data}.tid (= newsletter series) this issue belongs to.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'label' => t('Newsletter'),
    ),
  );

  $data['simplenews_newsletter']['status'] = array(
    'title' => t('Sent status'), // The item it appears as on the UI,
    'help' => t('Newsletter sent status: 0: Not sent, 1: Pending (being sent or waiting for cron to run), 2: Sent.'), // The help that appears on the UI,
    'field' => array(
      'handler' => 'simplenews_handler_field_newsletter_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_newsletter_status',
    ),
    'argument' => array(
        'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_newsletter']['sent_subscriber_count'] = array(
    'title' => t('Subscriber count'),
    'help' => t('The count of subscribers of the newsletter at the time it was sent.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

/* ------------ Definitions for Simplenews subscriber ----------------------*/

  $data['simplenews_subscriber']['table'] = array(
    'base' => array(
      'field' => 'snid',
      'title' => t('Simplenews subscriber'),
      'help' => t('Contains subscribers of Simplenews Newsletters.'),
      'weight' => 10,
      'database' => 'default',
    ),
    'group' => t('Simplenews subscriber'),
  );
  
  // Joins
  $data['simplenews_subscriber']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
    
  $data['simplenews_subscriber']['snid'] = array(
    'title' => t('Subscriber ID'),
    'help' => t('Primary key: Unique subsciber ID.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_subscriber']['activated'] = array(
    'title' => t('Activated'),
    'help' => t('Boolean indicating the status of the subscription.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_subscriber']['mail'] = array(
    'title' => t('Subscriber'),
    'help' => t('The subscription email address.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_subscriber']['uid'] = array(
    'title' => t('User'),
    'help' => t('The {users}.uid that has the same email address.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('user'),
    ),
  );
    
  $data['simplenews_subscriber']['language'] = array(
    'title' => t('Language'),
    'help' => t('Anonymous subscriber preferred language. Empty for authenticated users.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_subscriber']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('UNIX timestamp of when the user first subscribed to a newsletter.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
  );

/* ------------ Definitions for Simplenews subscription ----------------------*/
    
  $data['simplenews_subscription']['table'] = array(
    'base' => array(
       'field' => 'snid',
       'title' => t('Simplenews subscription'),
       'help' => t('Contains all Subscriptions of every Simplenews Newsletters.'),
       'weight' => 10,
       'database' => 'default',
     ),
     'group' => t('Simplenews subscription'),
  );

  $data['simplenews_subscription']['table']['join'] = array(
    'taxonomy_term_data' => array(
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'simplenews_subscriber' => array(
      'left_field' => 'snid',
      'field' => 'snid',
    ),
  );
    
  $data['simplenews_subscription']['snid'] = array(
    'title' => t('Subscriber ID'),
    'help' => t('The {simplenews_subscriptions}.snid who is subscribed.'),
    'field' => array(
      'label' => 'TEST',
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'simplenews_subscriber',
      'base field' => 'snid',
      'label' => t('Subscriber'),
    ),
  );
    
  $data['simplenews_subscription']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The newsletter series ({term_data}.tid) the subscriber is subscribed to.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'label' => t('Term (Newsletter series)'),
    ),
  );
    
  $data['simplenews_subscription']['status'] = array(
    'title' => t('Status'),
    'help' => t('A flag indicating whether the user is subscribed (1) or unsubscribed (0).'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_subscription']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('UNIX timestamp of when the user is (un)subscribed.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
  );
    
  $data['simplenews_subscription']['source'] = array(
    'title' => t('Source'),
    'help' => t('The source via which the user is (un)subscribed.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
/* ------------ Definitions for Simplenews category ----------------------*/

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['simplenews_category']['table'] = array(
    'group' => t('Simplenews category'),
  );

  // Joins
  $data['simplenews_category']['table']['join'] = array(
    // Category links directly to taxonomy via tid.
    'taxonomy_term_data' => array(
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  // Fields
  $data['simplenews_category']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The newsletter series ({term_data}.tid) the subscriber is subscribed to.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'label' => t('Term (Newsletter series)'),
    ),
  );
   
  $data['simplenews_category']['format'] = array(
    'title' => t('Format'),
    'help' => t('Format of the newsletter (plain or html).'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_category']['priority'] = array(
    'title' => t('Priority'),
    'help' => t('Email priority according to RFC 2156 and RFC 5231 (0 = none; 1 = highest; 2 = high; 3 = normal; 4 = low; 5 = lowest).'),
    'field' => array(
      'handler' => 'simplenews_handler_field_newsletter_priority',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_newsletter_priority',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['simplenews_newsletter']['receipt'] = array(
    'title' => t('Receipt'),
    'help' => t('Boolean indicating request for email receipt confirmation according to RFC 2822.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_category']['from_name'] = array(
    'title' => t('From name'),
    'help' => t('Sender name for newsletter emails.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['simplenews_category']['email_subject'] = array(
    'title' => t('Email Subject'),
    'help' => t('Subject of newsletter email. May contain tokens.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_category']['from_address'] = array(
    'title' => t('From address'),
    'help' => t('Sender address for newsletter emails'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click_sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_srting',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['simplenews_category']['hyperlinks'] = array(
    'title' => t('Hyperlinks displaymode'),
    'help' => t('Flag indicating type of hyperlink conversion (1 = hyperlinks are in-line; 0 = hyperlinks are placed at email bottom).'),
    'field' => array(
      'handler' => 'simplenews_handler_field_category_hyperlinks',
      'click_sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_category_hyperlinks'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['simplenews_category']['new_account'] = array(
    'title' => t('New account'),
    'help' => t('How to treat subscription at account creation (none = None; on = Default on; off = Default off; silent = Invisible subscription).'),
    'field' => array(
      'handler' => 'simplenews_handler_field_category_new_account',
      'click_sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_category_new_account'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['simplenews_category']['opt_inout'] = array(
    'title' => t('Confirmation displaymode'),
    'help' => t('How to treat subscription confirmation (hidden = Newsletter is hidden from the user; single = Single opt-in; double = Double opt-in).'),
    'field' => array(
      'handler' => 'simplenews_handler_field_category_opt_inout',
      'click_sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'simplenews_handler_filter_category_opt_inout'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['simplenews_category']['block'] = array(
    'title' => t('Block'),
    'help' => t('Indicates wether a subscription block is available for this category'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click_sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  return $data;
}