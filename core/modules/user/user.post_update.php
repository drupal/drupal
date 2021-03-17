<?php

/**
 * @file
 * Post update functions for User module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates() {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
  ];
}

/**
 * Update config for change mail notifications.
 */
function user_post_update_mail_change() {
  $config_factory = \Drupal::service('config.factory');

  $config_factory->getEditable('user.settings')
    ->set('notify.mail_change_notification', FALSE)
    ->set('notify.mail_change_verification', FALSE)
    ->set('mail_change_timeout', 86400)
    ->save();

  $mail_change_notification = [
    'body' => "[user:display-name],\n\nA request to change your email address has been made at [site:name]. In order to complete the change you will need to follow the instructions sent to your new email address within one day.",
    'subject' => 'Email change information for [user:display-name] at [site:name]',
  ];
  $mail_change_verification = [
    'body' => "[user:display-name],\n\nA request to change your email address has been made at [site:name]. You need to verify the change by clicking on the link below or copying and pasting it in your browser:\n\n[user:mail-change-url]\n\nThis is a one-time URL, so it can be used only once. It expires after one day. If not used, your email address at [site:name] will not change.",
    'subject' => 'Email change information for [user:display-name] at [site:name]',
  ];

  $config_factory->getEditable('user.mail')
    ->set('mail_change_notification', $mail_change_notification)
    ->set('mail_change_verification', $mail_change_verification)
    ->save();
}
