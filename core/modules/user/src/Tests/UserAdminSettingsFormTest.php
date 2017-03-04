<?php

namespace Drupal\user\Tests;

use Drupal\system\Tests\System\SystemConfigFormTestBase;
use Drupal\user\AccountSettingsForm;

/**
 * Configuration object user.mail and user.settings save test.
 *
 * @group user
 */
class UserAdminSettingsFormTest extends SystemConfigFormTestBase {

  protected function setUp() {
    parent::setUp();

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
    ];
  }

}
