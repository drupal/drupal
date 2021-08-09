<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the listing of configuration entities.
 *
 * @group config
 */
class ConfigEntityListTest extends BrowserTestBase {

  use RedirectDestinationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Delete the override config_test entity since it is not required by this
    // test.
    \Drupal::entityTypeManager()->getStorage('config_test')->load('override')->delete();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests entity list builder methods.
   */
  public function testList() {
    $controller = \Drupal::entityTypeManager()->getListBuilder('config_test');

    // Test getStorage() method.
    $this->assertInstanceOf(EntityStorageInterface::class, $controller->getStorage());

    // Get a list of ConfigTest entities and confirm that it contains the
    // ConfigTest entity provided by the config_test module.
    // @see config_test.dynamic.dotted.default.yml
    $list = $controller->load();
    $this->assertCount(1, $list, '1 ConfigTest entity found.');
    $entity = $list['dotted.default'];
    $this->assertInstanceOf(ConfigTest::class, $entity);

    // Test getOperations() method.
    $expected_operations = [
      'edit' => [
        'title' => t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl()->setOption('query', $this->getRedirectDestination()->getAsArray()),
      ],
      'disable' => [
        'title' => t('Disable'),
        'weight' => 40,
        'url' => $entity->toUrl('disable')->setOption('query', $this->getRedirectDestination()->getAsArray()),
      ],
      'delete' => [
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form')->setOption('query', $this->getRedirectDestination()->getAsArray()),
      ],
    ];

    $actual_operations = $controller->getOperations($entity);
    // Sort the operations to normalize link order.
    uasort($actual_operations, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $this->assertEquals($expected_operations, $actual_operations, 'The operations are identical.');

    // Test buildHeader() method.
    $expected_items = [
      'label' => 'Label',
      'id' => 'Machine name',
      'operations' => 'Operations',
    ];
    $actual_items = $controller->buildHeader();
    $this->assertEquals($expected_items, $actual_items, 'Return value from buildHeader matches expected.');

    // Test buildRow() method.
    $build_operations = $controller->buildOperations($entity);
    $expected_items = [
      'label' => 'Default',
      'id' => 'dotted.default',
      'operations' => [
        'data' => $build_operations,
      ],
    ];
    $actual_items = $controller->buildRow($entity);
    $this->assertEquals($expected_items, $actual_items, 'Return value from buildRow matches expected.');
    // Test sorting.
    $storage = $controller->getStorage();
    $entity = $storage->create([
      'id' => 'alpha',
      'label' => 'Alpha',
      'weight' => 1,
    ]);
    $entity->save();
    $entity = $storage->create([
      'id' => 'omega',
      'label' => 'Omega',
      'weight' => 1,
    ]);
    $entity->save();
    $entity = $storage->create([
      'id' => 'beta',
      'label' => 'Beta',
      'weight' => 0,
    ]);
    $entity->save();
    $list = $controller->load();
    $this->assertSame(['beta', 'dotted.default', 'alpha', 'omega'], array_keys($list));

    // Test that config entities that do not support status, do not have
    // enable/disable operations.
    $controller = $this->container->get('entity_type.manager')
      ->getListBuilder('config_test_no_status');

    $list = $controller->load();
    $entity = $list['default'];

    // Test getOperations() method.
    $expected_operations = [
      'edit' => [
        'title' => t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl()->setOption('query', $this->getRedirectDestination()->getAsArray()),
      ],
      'delete' => [
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form')->setOption('query', $this->getRedirectDestination()->getAsArray()),
      ],
    ];

    $actual_operations = $controller->getOperations($entity);
    // Sort the operations to normalize link order.
    uasort($actual_operations, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $this->assertEquals($expected_operations, $actual_operations, 'The operations are identical.');
  }

  /**
   * Tests the listing UI.
   */
  public function testListUI() {
    // Log in as an administrative user to access the full menu trail.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
    ]));

    // Get the list callback page.
    $this->drupalGet('admin/structure/config_test');

    // Test for the page title.
    $this->assertSession()->titleEquals('Test configuration | Drupal');

    // Test for the table.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table', 1);

    // Test the table header.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/thead/tr/th', 3);

    // Test the contents of each th cell.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[1]', 'Label');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[2]', 'Machine name');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[3]', 'Operations');

    // Check the number of table row cells.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td', 3);

    // Check the contents of each row cell. The first cell contains the label,
    // the second contains the machine name, and the third contains the
    // operations list.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td[1]', 'Default');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td[2]', 'dotted.default');
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td[3]//ul');

    // Add a new entity using the operations link.
    $this->assertSession()->linkExists('Add test configuration');
    $this->clickLink('Add test configuration');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'label' => 'Antelope',
      'id' => 'antelope',
      'weight' => 1,
    ];
    $this->submitForm($edit, 'Save');

    // Ensure that the entity's sort method was called.
    $this->assertTrue(\Drupal::state()->get('config_entity_sort'), 'ConfigTest::sort() was called.');

    // Confirm that the user is returned to the listing, and verify that the
    // text of the label and machine name appears in the list (versus elsewhere
    // on the page).
    $this->assertSession()->elementExists('xpath', '//td[text() = "Antelope"]');
    $this->assertSession()->elementExists('xpath', '//td[text() = "antelope"]');

    // Edit the entity using the operations link.
    $this->assertSession()->linkByHrefExists('admin/structure/config_test/manage/antelope');
    $this->clickLink('Edit', 1);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Edit Antelope | Drupal');
    $edit = ['label' => 'Albatross', 'id' => 'albatross'];
    $this->submitForm($edit, 'Save');

    // Confirm that the user is returned to the listing, and verify that the
    // text of the label and machine name appears in the list (versus elsewhere
    // on the page).
    $this->assertSession()->elementExists('xpath', '//td[text() = "Albatross"]');
    $this->assertSession()->elementExists('xpath', '//td[text() = "albatross"]');

    // Delete the added entity using the operations link.
    $this->assertSession()->linkByHrefExists('admin/structure/config_test/manage/albatross/delete');
    $this->clickLink('Delete', 1);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Are you sure you want to delete the test configuration Albatross? | Drupal');
    $this->submitForm([], 'Delete');

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertSession()->elementNotExists('xpath', '//td[text() = "Albatross"]');
    $this->assertSession()->elementNotExists('xpath', '//td[text() = "albatross"]');

    // Delete the original entity using the operations link.
    $this->clickLink('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Are you sure you want to delete the test configuration Default? | Drupal');
    $this->submitForm([], 'Delete');

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertSession()->elementNotExists('xpath', '//td[text() = "Default"]');
    $this->assertSession()->elementNotExists('xpath', '//td[text() = "dotted.default"]');

    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no test configuration entities yet.');
  }

  /**
   * Tests paging.
   */
  public function testPager() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));

    $storage = \Drupal::service('entity_type.manager')->getListBuilder('config_test')->getStorage();

    // Create 51 test entities.
    for ($i = 1; $i < 52; $i++) {
      $storage->create([
        'id' => str_pad($i, 2, '0', STR_PAD_LEFT),
        'label' => 'Test config entity ' . $i,
        'weight' => $i,
        'protected_property' => $i,
      ])->save();
    }

    // Load the listing page.
    $this->drupalGet('admin/structure/config_test');

    // Item 51 should not be present.
    $this->assertRaw('Test config entity 50');
    $this->assertSession()->responseNotContains('Test config entity 51');

    // Browse to the next page, test config entity 51 is on page 2.
    $this->clickLink('Page 2');
    $this->assertSession()->responseNotContains('Test config entity 50');
    $this->assertRaw('dotted.default');
    $this->assertRaw('Test config entity 51');
  }

}
