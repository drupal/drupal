<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 *
 * @group views
 */
class FieldEntityLinkTest extends ViewsKernelTestBase {

  use UserCreationTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_test_link'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'entity_test'];

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

    // Create the anonymous user account and set it as current user.
    $this->setUpCurrentUser(['uid' => 0]);

    $this->installEntitySchema('entity_test');
    $this->installConfig(['user']);

    // Create some test entities.
    for ($i = 0; $i < 5; $i++) {
      EntityTest::create(['name' => $this->randomString()])->save();
    }

    // Create and admin user.
    $this->adminUser = $this->createUser(['view test entity'], FALSE, TRUE);

    Role::load(AccountInterface::ANONYMOUS_ROLE)
      ->grantPermission('view test entity')
      ->save();
  }

  /**
   * Tests entity link fields.
   */
  public function testEntityLink() {
    // Anonymous users cannot see edit/delete links.
    $expected_results = ['canonical' => TRUE, 'edit-form' => FALSE, 'delete-form' => FALSE, 'canonical_raw' => TRUE, 'canonical_raw_absolute' => TRUE];
    $this->doTestEntityLink(\Drupal::currentUser(), $expected_results);

    // Admin users cannot see all links.
    $expected_results = ['canonical' => TRUE, 'edit-form' => TRUE, 'delete-form' => TRUE, 'canonical_raw' => TRUE, 'canonical_raw_absolute' => TRUE];
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
        'link' => TRUE,
        'options' => [],
        'relationship' => 'canonical',
      ],
      'edit-form' => [
        'label' => 'Edit entity test',
        'field_id' => 'edit_entity_test',
        'destination' => TRUE,
        'link' => TRUE,
        'options' => [],
        'relationship' => 'edit-form',
      ],
      'delete-form' => [
        'label' => 'Delete entity test',
        'field_id' => 'delete_entity_test',
        'destination' => TRUE,
        'link' => TRUE,
        'options' => [],
        'relationship' => 'delete-form',
      ],
      'canonical_raw' => [
        'field_id' => 'canonical_entity_test',
        'destination' => FALSE,
        'link' => FALSE,
        'options' => [],
        'relationship' => 'canonical',
      ],
      'canonical_raw_absolute' => [
        'field_id' => 'absolute_entity_test',
        'destination' => FALSE,
        'link' => FALSE,
        'options' => ['absolute' => TRUE],
        'relationship' => 'canonical',
      ],
    ];

    $index = 0;
    foreach (EntityTest::loadMultiple() as $entity) {
      foreach ($expected_results as $template => $expected_result) {
        $expected_link = '';
        if ($expected_result) {
          $path = $entity->toUrl($info[$template]['relationship'], $info[$template]['options'])->toString();
          $destination = $info[$template]['destination'] ? '?destination=/' : '';
          if ($info[$template]['link']) {
            $expected_link = '<a href="' . $path . $destination . '" hreflang="en">' . $info[$template]['label'] . '</a>';
          }
          else {
            $expected_link = (string) $path;
          }
        }
        $link = $view->style_plugin->getField($index, $info[$template]['field_id']);
        $this->assertSame($expected_link, (string) $link);
      }
      $index++;
    }
  }

}
