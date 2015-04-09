<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\userViewsFieldAccessTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\user;
use Drupal\views\Tests\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the user entity.
 *
 * @group user
 */
class UserViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'entity_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
  }

  public function testUserFields() {
    ConfigurableLanguage::create([
      'id' => 'es',
      'name' => 'Spanish',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'fr',
      'name' => 'French',
    ])->save();

    $user = User::create([
      'name' => 'test user',
      'mail' => 'druplicon@drop.org',
      'status' => 1,
      'preferred_langcode' => 'es',
      'preferred_admin_langcode' => 'fr',
      'timezone' => 'ut1',
      'created' => 123456,
    ]);

    $user->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    // $this->assertFieldAccess('user', 'uid', $user->id());
    $this->assertFieldAccess('user', 'uuid', $user->uuid());
    $this->assertFieldAccess('user', 'langcode', $user->language()->getName());
    $this->assertFieldAccess('user', 'preferred_langcode', 'Spanish');
    $this->assertFieldAccess('user', 'preferred_admin_langcode', 'French');
    // $this->assertFieldAccess('user', 'name', 'test user');
    // $this->assertFieldAccess('user', 'mail', 'druplicon@drop.org');
    $this->assertFieldAccess('user', 'timezone', 'ut1');
    $this->assertFieldAccess('user', 'status', 'On');
    // $this->assertFieldAccess('user', 'created', \Drupal::service('date.formatter')->format(123456));
    // $this->assertFieldAccess('user', 'changed', \Drupal::service('date.formatter')->format(REQUEST_TIME));
  }

}
