<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UserMail.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing user.mail.yml migration.
 */
class Drupal6UserMail extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'user_mail_status_activated_subject',
      'value' => 's:49:"Account details for !username at !site (approved)";',
    ))
    ->values(array(
      'name' => 'user_mail_status_activated_body',
      'value' => "s:419:\"!username,\n\nYour account at !site has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\nOnce you have set your own password, you will be able to log in to !login_uri in the future using:\n\nusername: !username\n\";",
    ))
    ->values(array(
      'name' => 'user_mail_password_reset_subject',
      'value' => 's:52:"Replacement login information for !username at !site";',
    ))
    ->values(array(
      'name' => 'user_mail_password_reset_body',
      'value' => "s:409:\"!username,\n\nA request to reset the password for your account has been made at !site.\n\nYou may now log in to !uri_brief by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\";",
    ))
    ->values(array(
      'name' => 'user_mail_status_deleted_subject',
      'value' => 's:48:"Account details for !username at !site (deleted)";',
    ))
    ->values(array(
      'name' => 'user_mail_status_deleted_body',
      'value' => "s:51:\"!username,\n\nYour account on !site has been deleted.\";",
    ))
    ->values(array(
      'name' => 'user_mail_register_admin_created_subject',
      'value' => 's:52:"An administrator created an account for you at !site";',
    ))
    ->values(array(
      'name' => 'user_mail_register_admin_created_body',
      'value' => "s:452:\"!username,\n\nA site administrator at !site has created an account for you. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team\";",
    ))
    ->values(array(
      'name' => 'user_mail_register_no_approval_required_subject',
      'value' => 's:38:"Account details for !username at !site";',
    ))
    ->values(array(
      'name' => 'user_mail_register_no_approval_required_body',
      'value' => "s:426:\"!username,\n\nThank you for registering at !site. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team\";",
    ))
    ->values(array(
      'name' => 'user_mail_user_mail_register_pending_approval_subject',
      'value' => 's:63:"Account details for !username at !site (pending admin approval)";',
    ))
    ->values(array(
      'name' => 'user_mail_user_mail_register_pending_approval_body',
      'value' => "s:267:\"!username,\n\nThank you for registering at !site. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n\n--  !site team\";",
    ))
    ->values(array(
      'name' => 'user_mail_status_blocked_subject',
      'value' => 's:48:"Account details for !username at !site (blocked)";',
    ))
    ->values(array(
      'name' => 'user_mail_status_blocked_body',
      'value' => "s:51:\"!username,\n\nYour account on !site has been blocked.\";",
    ))
    ->execute();
  }
}
