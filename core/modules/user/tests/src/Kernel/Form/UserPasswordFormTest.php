<?php

namespace Drupal\Tests\user\Kernel\Form;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Form\UserPasswordForm;
use Drupal\user\UserStorageInterface;

/**
 * @coversDefaultClass \Drupal\user\Form\UserPasswordForm
 * @group user
 */
class UserPasswordFormTest extends KernelTestBase {

  /**
   * @group legacy
   * @expectedDeprecation Calling Drupal\user\Form\UserPasswordForm::__construct without the $config_factory is deprecated in drupal:8.8.0 and is required before drupal:9.0.0. See https://www.drupal.org/node/1681832
   * @expectedDeprecation Calling Drupal\user\Form\UserPasswordForm::__construct without the $flood parameter is deprecated in drupal:8.8.0 and is required before drupal:9.0.0. See https://www.drupal.org/node/1681832
   */
  public function testConstructorDeprecations() {
    $user_storage = $this->prophesize(UserStorageInterface::class);
    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $form = new UserPasswordForm($user_storage->reveal(), $language_manager->reveal());
    $this->assertNotNull($form);
  }

}
