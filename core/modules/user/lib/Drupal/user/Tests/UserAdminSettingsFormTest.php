<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAdminSettingsFormTest.
 */

namespace Drupal\user\Tests;

use Drupal\system\Tests\System\SystemConfigFormTestBase;
use Drupal\user\AccountSettingsForm;

class UserAdminSettingsFormTest extends SystemConfigFormTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User admin settings',
      'description' => 'Configuration object user.mail and user.settings save test.',
      'group' => 'User',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->form = AccountSettingsForm::create($this->container);
    $this->values = array(
      'anonymous' => array(
        '#value' => $this->randomString(10),
        '#config_name' => 'user.settings',
        '#config_key' => 'anonymous',
      ),
      'user_mail_cancel_confirm_body' => array(
        '#value' => $this->randomString(),
        '#config_name' => 'user.mail',
        '#config_key' => 'cancel_confirm.body',
      ),
      'user_mail_cancel_confirm_subject' => array(
        '#value' => $this->randomString(20),
        '#config_name' => 'user.mail',
        '#config_key' => 'cancel_confirm.subject',
      ),
    );
  }
}
