<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldEntityLinkTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 *
 * @group views
 */
class FieldEntityLinkTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_test_link');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'entity_test');

  /**
   * An admin user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    parent::setUpFixtures();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    // Create some test entities.
    for ($i = 0; $i < 5; $i++) {
      EntityTest::create(array(
        'name' => $this->randomString(),
      ))->save();
    }

    // Create an admin role.
    $role = Role::create(['id' => $this->randomMachineName()]);
    $role->setIsAdmin(TRUE);
    $role->save();

    // Create and admin user.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
  }

  /**
   * Tests entity link fields.
   */
  public function testEntityLink() {
    // Anonymous users cannot see edit/delete links.
    $expected_results = ['canonical' => TRUE, 'edit-form' => FALSE, 'delete-form' => FALSE];
    $this->doTestEntityLink(\Drupal::currentUser(), $expected_results);

    // Admin users cannot see all links.
    $expected_results = ['canonical' => TRUE, 'edit-form' => TRUE, 'delete-form' => TRUE];
    $this->doTestEntityLink($this->adminUser, $expected_results);
  }

  /**
   * Tests whether entity links behave as expected.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to be used to run the test;
   * @param bool[] $expected_results
   *   An associative array of expected results keyed by link template name.
   */
  protected function doTestEntityLink(AccountInterface $account, $expected_results) {
    \Drupal::currentUser()->setAccount($account);

    $view = Views::getView('test_entity_test_link');
    $view->preview();

    $info = [
      'canonical' => [
        'label' => 'View entity test',
        'field_id' => 'view_entity_test',
        'destination' => FALSE,
      ],
      'edit-form' => [
        'label' => 'Edit entity test',
        'field_id' => 'edit_entity_test',
        'destination' => TRUE,
      ],
      'delete-form' => [
        'label' => 'Delete entity test',
        'field_id' => 'delete_entity_test',
        'destination' => TRUE,
      ],
    ];

    $index = 0;
    foreach (EntityTest::loadMultiple() as $entity) {
      foreach ($expected_results as $template => $expected_result) {
        $expected_link = '';
        if ($expected_result) {
          $path = $entity->url($template);
          $destination = $info[$template]['destination'] ? '?destination=/' : '';
          $expected_link = '<a href="' . $path . $destination . '" hreflang="en">' . $info[$template]['label'] . '</a>';
        }
        $link = $view->style_plugin->getField($index, $info[$template]['field_id']);
        $this->assertEqual($link, $expected_link);
      }
      $index++;
    }
  }

}
