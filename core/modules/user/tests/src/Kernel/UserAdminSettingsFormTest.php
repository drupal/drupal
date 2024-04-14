<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\ConfigFormTestBase;
use Drupal\user\AccountSettingsForm;

/**
 * Configuration object user.mail and user.settings save test.
 *
 * @group user
 */
class UserAdminSettingsFormTest extends ConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);

    $this->form = AccountSettingsForm::create($this->container);
    $this->values = [
      'anonymous' => [
        '#value' => $this->randomString(10),
        '#config_name' => 'user.settings',
        '#config_key' => 'anonymous',
      ],
      'user_mail_cancel_confirm_body' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'cancel_confirm.body',
      ],
      'user_mail_cancel_confirm_subject' => [
        '#value' => $this->randomString(20),
        '#config_name' => 'user.mail',
        '#config_key' => 'cancel_confirm.subject',
      ],
      'register_pending_approval_admin_body' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'register_pending_approval_admin.body',
      ],
      'register_pending_approval_admin_subject' => [
        '#value' => $this->randomString(20),
        '#config_name' => 'user.mail',
        '#config_key' => 'register_pending_approval_admin.subject',
      ],
      'user_mail_password_reset_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'password_reset.subject',
      ],
      'user_mail_register_admin_created_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'register_admin_created.subject',
      ],
      'user_mail_register_no_approval_required_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'register_no_approval_required.subject',
      ],
      'user_mail_register_pending_approval_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'register_pending_approval.subject',
      ],
      'user_mail_status_activated_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'status_activated.subject',
      ],
      'user_mail_status_blocked_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'status_blocked.subject',
      ],
      'user_mail_status_canceled_subject' => [
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'status_canceled.subject',
      ],
    ];
  }

}
