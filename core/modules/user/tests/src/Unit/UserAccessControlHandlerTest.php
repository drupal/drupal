<?php

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserAccessControlHandler;

/**
 * Tests the user access controller.
 *
 * @group Drupal
 * @group User
 *
 * @coversDefaultClass \Drupal\user\UserAccessControlHandler
 */
class UserAccessControlHandlerTest extends UnitTestCase {

  /**
   * The user access controller to test.
   *
   * @var \Drupal\user\UserAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * The mock user account with view access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $viewer;

  /**
   * The mock user account that is able to change their own account name.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $owner;

  /**
   * The mock administrative test user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $admin;

  /**
   * The mocked test field items.
   *
   * @var \Drupal\Core\Field\FieldItemList
   */
  protected $items;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $this->viewer = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $this->viewer
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(FALSE));
    $this->viewer
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $this->owner = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $this->owner
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
        ['administer users', FALSE],
        ['change own username', TRUE],
      ]));

    $this->owner
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $this->admin = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $this->admin
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(TRUE));

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');

    $this->accessControlHandler = new UserAccessControlHandler($entity_type);
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue([]));
    $this->accessControlHandler->setModuleHandler($module_handler);

    $this->items = $this->getMockBuilder('Drupal\Core\Field\FieldItemList')
      ->disableOriginalConstructor()
      ->getMock();
    $this->items
      ->expects($this->any())
      ->method('defaultAccess')
      ->will($this->returnValue(AccessResult::allowed()));
  }

  /**
   * Asserts correct field access grants for a field.
   */
  public function assertFieldAccess($field, $viewer, $target, $view, $edit) {
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($field));

    $this->items
      ->expects($this->any())
      ->method('getEntity')
      ->will($this->returnValue($this->{$target}));

    foreach (['view' => $view, 'edit' => $edit] as $operation => $result) {
      $result_text = !isset($result) ? 'null' : ($result ? 'true' : 'false');
      $message = "User '$field' field access returns '$result_text' with operation '$operation' for '$viewer' accessing '$target'";
      $this->assertSame($result, $this->accessControlHandler->fieldAccess($operation, $field_definition, $this->{$viewer}, $this->items), $message);
    }
  }

  /**
   * Ensures user name access is working properly.
   *
   * @dataProvider userNameProvider
   */
  public function testUserNameAccess($viewer, $target, $view, $edit) {
    $this->assertFieldAccess('name', $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for testUserNameAccess().
   */
  public function userNameProvider() {
    $name_access = [
      // The viewer user is allowed to see user names on all accounts.
      [
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ],
      [
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ],
      [
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => FALSE,
      ],
      // The owner user is allowed to change its own user name.
      [
        'viewer' => 'owner',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ],
      // The users-administrator user has full access.
      [
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ],
    ];
    return $name_access;
  }

  /**
   * Tests that private user settings cannot be viewed by other users.
   *
   * @dataProvider hiddenUserSettingsProvider
   */
  public function testHiddenUserSettings($field, $viewer, $target, $view, $edit) {
    $this->assertFieldAccess($field, $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for testHiddenUserSettings().
   */
  public function hiddenUserSettingsProvider() {
    $access_info = [];

    $fields = [
      'preferred_langcode',
      'preferred_admin_langcode',
      'timezone',
      'mail',
    ];

    foreach ($fields as $field) {
      $access_info[] = [
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      ];
      $access_info[] = [
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        // Anyone with edit access to the user can also edit these fields. In
        // reality edit access will already be checked on entity level and the
        // user without view access will typically not be able to edit.
        'edit' => TRUE,
      ];
      $access_info[] = [
        'field' => $field,
        'viewer' => 'owner',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ];
      $access_info[] = [
        'field' => $field,
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ];
    }

    return $access_info;
  }

  /**
   * Tests that private user settings cannot be viewed by other users.
   *
   * @dataProvider adminFieldAccessProvider
   */
  public function testAdminFieldAccess($field, $viewer, $target, $view, $edit) {
    $this->assertFieldAccess($field, $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for testAdminFieldAccess().
   */
  public function adminFieldAccessProvider() {
    $access_info = [];

    $fields = [
      'roles',
      'status',
      'access',
      'login',
      'init',
    ];

    foreach ($fields as $field) {
      $access_info[] = [
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => FALSE,
      ];
      $access_info[] = [
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        'edit' => FALSE,
      ];
      $access_info[] = [
        'field' => $field,
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ];
    }

    return $access_info;
  }

  /**
   * Tests that passwords cannot be viewed, just edited.
   *
   * @dataProvider passwordAccessProvider
   */
  public function testPasswordAccess($viewer, $target, $view, $edit) {
    $this->assertFieldAccess('pass', $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for passwordAccessProvider().
   */
  public function passwordAccessProvider() {
    $pass_access = [
      [
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => TRUE,
      ],
      [
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        // Anyone with edit access to the user can also edit these fields. In
        // reality edit access will already be checked on entity level and the
        // user without view access will typically not be able to edit.
        'edit' => TRUE,
      ],
      [
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => TRUE,
      ],
      [
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => FALSE,
        'edit' => TRUE,
      ],
    ];
    return $pass_access;
  }

  /**
   * Tests the user created field access.
   *
   * @dataProvider createdAccessProvider
   */
  public function testCreatedAccess($viewer, $target, $view, $edit) {
    $this->assertFieldAccess('created', $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for testCreatedAccess().
   */
  public function createdAccessProvider() {
    $created_access = [
      [
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ],
      [
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ],
      [
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ],
    ];
    return $created_access;
  }

  /**
   * Tests access to a non-existing base field.
   *
   * @dataProvider NonExistingFieldAccessProvider
   */
  public function testNonExistingFieldAccess($viewer, $target, $view, $edit) {
    // By default everyone has access to all fields that do not have explicit
    // access control.
    // @see EntityAccessControlHandler::checkFieldAccess()
    $this->assertFieldAccess('some_non_existing_field', $viewer, $target, $view, $edit);
  }

  /**
   * Provides test data for testNonExistingFieldAccess().
   */
  public function NonExistingFieldAccessProvider() {
    $created_access = [
      [
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      ],
      [
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      ],
      [
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ],
    ];
    return $created_access;
  }

}
