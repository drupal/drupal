<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\UserAccessControlHandlerTest.
 */

namespace Drupal\Tests\user\Unit;

use Drupal\Component\Utility\String;
use Drupal\Core\Access\AccessResult;
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
  public function setUp() {
    parent::setUp();
    $this->viewer = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->viewer
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(FALSE));
    $this->viewer
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $this->owner = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->owner
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap(array(
        array('administer users', FALSE),
        array('change own username', TRUE),
      )));

    $this->owner
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $this->admin = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->admin
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(TRUE));

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

    $this->accessControlHandler = new UserAccessControlHandler($entity_type);
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue(array()));
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
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($field));

    $this->items
      ->expects($this->any())
      ->method('getEntity')
      ->will($this->returnValue($this->{$target}));

    foreach (array('view' => $view, 'edit' => $edit) as $operation => $result) {
      $message = String::format("User @field field access returns @result with operation '@op' for @account accessing @target", array(
        '@field' => $field,
        '@result' => !isset($result) ? 'null' : ($result ? 'true' : 'false'),
        '@op' => $operation,
        '@account' => $viewer,
        '@target' => $target,
      ));
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
   * Provides test data for estUserNameAccess().
   */
  public function userNameProvider() {
    $name_access = array(
      // The viewer user is allowed to see user names on all accounts.
      array(
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ),
      array(
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ),
      array(
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => FALSE,
      ),
      // The owner user is allowed to change its own user name.
      array(
        'viewer' => 'owner',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ),
      // The users-administrator user has full access.
      array(
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ),
    );
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
    $access_info = array();

    $fields = array(
      'preferred_langcode',
      'preferred_admin_langcode',
      'signature',
      'signature_format',
      'timezone',
      'mail',
    );

    foreach ($fields as $field) {
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      );
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        // Anyone with edit access to the user can also edit these fields. In
        // reality edit access will already be checked on entity level and the
        // user without view access will typically not be able to edit.
        'edit' => TRUE,
      );
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'owner',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      );
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      );
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
    $access_info = array();

    $fields = array(
      'roles',
      'status',
      'access',
      'login',
      'init',
    );

    foreach ($fields as $field) {
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => FALSE,
      );
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        'edit' => FALSE,
      );
      $access_info[] = array(
        'field' => $field,
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      );
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
    $pass_access = array(
      array(
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => TRUE,
      ),
      array(
        'viewer' => 'viewer',
        'target' => 'owner',
        'view' => FALSE,
        // Anyone with edit access to the user can also edit these fields. In
        // reality edit access will already be checked on entity level and the
        // user without view access will typically not be able to edit.
        'edit' => TRUE,
      ),
      array(
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => FALSE,
        'edit' => TRUE,
      ),
      array(
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ),
    );
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
    $created_access = array(
      array(
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ),
      array(
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => FALSE,
      ),
      array(
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ),
    );
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
    $created_access = array(
      array(
        'viewer' => 'viewer',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      ),
      array(
        'viewer' => 'owner',
        'target' => 'viewer',
        'view' => TRUE,
        'edit' => TRUE,
      ),
      array(
        'viewer' => 'admin',
        'target' => 'owner',
        'view' => TRUE,
        'edit' => TRUE,
      ),
    );
    return $created_access;
  }

}
