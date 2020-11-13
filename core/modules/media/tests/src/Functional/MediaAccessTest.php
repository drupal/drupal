<?php

namespace Drupal\Tests\media\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Basic access tests for Media.
 *
 * @group media
 */
class MediaAccessTest extends MediaFunctionalTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // This is needed to provide the user cache context for a below assertion.
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test some access control functionality.
   */
  public function testMediaAccess() {
    $assert_session = $this->assertSession();
    $media_type = $this->createMediaType('test');

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();

    // Create media.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
      'uid' => $this->nonAdminUser->id(),
    ]);
    $user_media->save();

    // We are logged in as admin, so test 'administer media' permission.
    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);

    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    user_role_revoke_permissions($role->id(), ['view media']);

    // Test 'create BUNDLE media' permission.
    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $permissions = ['create ' . $media_type->id() . ' media'];
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    user_role_revoke_permissions($role->id(), $permissions);
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Verify the author can not view the unpublished media item without
    // 'view own unpublished media' permission.
    $this->grantPermissions($role, ['view media']);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertNoCacheContext('user');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $user_media->setUnpublished()->save();
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $access_result = $user_media->access('view', NULL, TRUE);
    $this->assertSame("The user must be the owner and the 'view own unpublished media' permission is required when the media item is unpublished.", $access_result->getReason());
    $this->grantPermissions($role, ['view own unpublished media']);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user');
    $assert_session->statusCodeEquals(200);

    // Test 'create media' permission.
    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $permissions = ['create media'];
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    user_role_revoke_permissions($role->id(), $permissions);
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'edit own BUNDLE media' and 'delete own BUNDLE media' permissions.
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $permissions = [
      'edit own ' . $user_media->bundle() . ' media',
      'delete own ' . $user_media->bundle() . ' media',
    ];
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertCacheContext('user');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertCacheContext('user');
    $assert_session->statusCodeEquals(200);
    user_role_revoke_permissions($role->id(), $permissions);
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'edit any BUNDLE media' and 'delete any BUNDLE media' permissions.
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $media->id() . '/delete');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $permissions = [
      'edit any ' . $media->bundle() . ' media',
      'delete any ' . $media->bundle() . ' media',
    ];
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $media->id() . '/delete');
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);

    // Test the 'access media overview' permission.
    $this->grantPermissions($role, ['access content overview']);
    $this->drupalGet('admin/content');
    $assert_session->linkByHrefNotExists('/admin/content/media');
    $this->assertCacheContext('user');

    // Create a new role, which implicitly checks if the permission exists.
    $mediaOverviewRole = $this->createRole(['access content overview', 'access media overview']);
    $this->nonAdminUser->addRole($mediaOverviewRole);
    $this->nonAdminUser->save();

    $this->drupalGet('admin/content');
    $assert_session->linkByHrefExists('/admin/content/media');
    $this->clickLink('Media');
    $this->assertCacheContext('user');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementExists('css', '.view-media');
    $assert_session->pageTextContains($this->loggedInUser->getDisplayName());
    $assert_session->pageTextContains($this->nonAdminUser->getDisplayName());
    $assert_session->linkByHrefExists('/media/' . $media->id());
    $assert_session->linkByHrefExists('/media/' . $user_media->id());
  }

  /**
   * Test view access control on the canonical page.
   */
  public function testCanonicalMediaAccess() {
    $media_type = $this->createMediaType('test');
    $assert_session = $this->assertSession();

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();

    // Create media.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
      'uid' => $this->nonAdminUser->id(),
    ]);
    $user_media->save();

    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    user_role_revoke_permissions($role->id(), ['view media']);

    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $access_result = $media->access('view', NULL, TRUE);
    $this->assertSame("The 'view media' permission is required when the media item is published.", $access_result->getReason());
    $this->grantPermissions($role, ['view media']);
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests unpublished media access.
   */
  public function testUnpublishedMediaUserAccess() {
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();

    $assert_session = $this->assertSession();
    $media_type = $this->createMediaType('test');
    $permissions = [
      'view media',
      'view own unpublished media',
    ];
    $user_one = $this->drupalCreateUser($permissions);
    $user_two = $this->drupalCreateUser($permissions);

    // Create media as user one.
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
      'uid' => $user_one->id(),
    ]);
    $user_media->setUnpublished()->save();

    // Make sure user two can't access unpublished media.
    $this->drupalLogin($user_two);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(403);
    $this->assertCacheContext('user');
    $this->drupalLogout();

    // Make sure user one can access own unpublished media.
    $this->drupalLogin($user_one);
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);
    $this->assertCacheContext('user');
  }

  /**
   * Tests media access of anonymous user.
   */
  public function testMediaAnonymousUserAccess() {
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();

    $assert_session = $this->assertSession();
    $media_type = $this->createMediaType('test');

    // Create media as anonymous user.
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
      'uid' => 0,
    ]);
    $user_media->save();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, ['view media', 'view own unpublished media']);
    $this->drupalLogout();

    // Make sure anonymous users can access published media.
    $user_media->setPublished()->save();
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);

    // Make sure anonymous users can not access unpublished media
    // even though role has 'view own unpublished media' permission.
    $user_media->setUnpublished()->save();
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(403);
    $this->assertCacheContext('user');
  }

  /**
   * Tests access for embedded medias.
   */
  public function testReferencedRendering() {
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();

    // Create a media type and an entity reference to itself.
    $media_type = $this->createMediaType('test');

    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'media',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'media',
      'bundle' => $media_type->id(),
    ])->save();

    $author = $this->drupalCreateUser([
      'view media',
      'view own unpublished media',
    ]);
    $other_user = $this->drupalCreateUser([
      'view media',
      'view own unpublished media',
    ]);
    $view_user = $this->drupalCreateUser(['view media']);

    $child_title = 'Child media';
    $media_child = Media::create([
      'name' => $child_title,
      'bundle' => $media_type->id(),
      'uid' => $author->id(),
    ]);
    $media_child->setUnpublished()->save();

    $media_parent = Media::create([
      'name' => 'Parent media',
      'bundle' => $media_type->id(),
      'field_reference' => $media_child->id(),
    ]);
    $media_parent->save();

    \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type->id(), 'full')
      ->set('content', [])
      ->setComponent('title', ['type' => 'string'])
      ->setComponent('field_reference', [
        'type' => 'entity_reference_label',
      ])
      ->save();

    $assert_session = $this->assertSession();

    // The author of the child media items should have access to both the parent
    // and child.
    $this->drupalLogin($author);
    $this->drupalGet($media_parent->toUrl());
    $this->assertCacheContext('user');
    $assert_session->pageTextContains($child_title);

    // Other users with the 'view own unpublished media' permission should not
    // be able to see the unpublished child media item. The 'user' cache context
    // should be added in this case.
    $this->drupalLogin($other_user);
    $this->drupalGet($media_parent->toUrl());
    $this->assertCacheContext('user');
    $assert_session->pageTextNotContains($child_title);

    // User with just the 'view media' permission should not be able to see the
    // child media item. The 'user' cache context should not be added in this
    // case.
    $this->drupalLogin($view_user);
    $this->drupalGet($media_parent->toUrl());
    $this->assertNoCacheContext('user');
    $assert_session->pageTextNotContains($child_title);
  }

}
