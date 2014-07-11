<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserCacheTagsTest.
 */

namespace Drupal\user\Tests;

use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the User entity's cache tags.
 *
 * @group user
 */
class UserCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('user');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Give anonymous users permission to view user profiles, so that we can
    // verify the cache tags of cached versions of user profile pages.
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access user profiles');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" user.
    $user = entity_create('user', array(
      'name' => 'Llama',
      'status' => TRUE,
    ));
    $user->save();

    return $user;
  }

}
