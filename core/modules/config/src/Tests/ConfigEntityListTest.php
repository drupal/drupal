<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigEntityListTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Tests the listing of configuration entities.
 */
class ConfigEntityListTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entity list',
      'description' => 'Tests the listing of configuration entities.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests entity list builder methods.
   */
  function testList() {
    $controller = \Drupal::entityManager()->getListBuilder('config_test');

    // Test getStorage() method.
    $this->assertTrue($controller->getStorage() instanceof EntityStorageInterface, 'EntityStorage instance in storage.');

    // Get a list of ConfigTest entities and confirm that it contains the
    // ConfigTest entity provided by the config_test module.
    // @see config_test.dynamic.dotted.default.yml
    $list = $controller->load();
    $this->assertEqual(count($list), 1, '1 ConfigTest entity found.');
    $entity = $list['dotted.default'];
    $this->assertTrue(!empty($entity), '"Default" ConfigTest entity ID found.');
    $this->assertTrue($entity instanceof ConfigTest, '"Default" ConfigTest entity is an instance of ConfigTest.');

    // Test getOperations() method.
    $expected_operations = array(
      'edit' => array (
        'title' => t('Edit'),
        'weight' => 10,
      ) + $entity->urlInfo()->toArray(),
      'disable' => array(
        'title' => t('Disable'),
        'weight' => 40,
      ) + $entity->urlInfo('disable')->toArray(),
      'delete' => array (
        'title' => t('Delete'),
        'weight' => 100,
      ) + $entity->urlInfo('delete-form')->toArray(),
    );

    $actual_operations = $controller->getOperations($entity);
    // Sort the operations to normalize link order.
    uasort($actual_operations, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    $this->assertIdentical($expected_operations, $actual_operations, 'The operations are identical.');

    // Test buildHeader() method.
    $expected_items = array(
      'label' => 'Label',
      'id' => 'Machine name',
      'operations' => 'Operations',
    );
    $actual_items = $controller->buildHeader();
    $this->assertIdentical($expected_items, $actual_items, 'Return value from buildHeader matches expected.');

    // Test buildRow() method.
    $build_operations = $controller->buildOperations($entity);
    $expected_items = array(
      'label' => 'Default',
      'id' => 'dotted.default',
      'operations' => array(
        'data' => $build_operations,
      ),
    );
    $actual_items = $controller->buildRow($entity);
    $this->assertIdentical($expected_items, $actual_items, 'Return value from buildRow matches expected.');
    // Test sorting.
    $storage = $controller->getStorage();
    $entity = $storage->create(array(
      'id' => 'alpha',
      'label' => 'Alpha',
      'weight' => 1,
    ));
    $entity->save();
    $entity = $storage->create(array(
      'id' => 'omega',
      'label' => 'Omega',
      'weight' => 1,
    ));
    $entity->save();
    $entity = $storage->create(array(
      'id' => 'beta',
      'label' => 'Beta',
      'weight' => 0,
    ));
    $entity->save();
    $list = $controller->load();
    $this->assertIdentical(array_keys($list), array('beta', 'dotted.default', 'alpha', 'omega'));

    // Test that config entities that do not support status, do not have
    // enable/disable operations.
    $controller = $this->container->get('entity.manager')
      ->getListBuilder('config_test_no_status');

    $list = $controller->load();
    $entity = $list['default'];

    // Test getOperations() method.
    $expected_operations = array(
      'edit' => array(
        'title' => t('Edit'),
        'weight' => 10,
      ) + $entity->urlInfo()->toArray(),
      'delete' => array(
        'title' => t('Delete'),
        'weight' => 100,
      ) + $entity->urlInfo('delete-form')->toArray(),
    );

    $actual_operations = $controller->getOperations($entity);
    // Sort the operations to normalize link order.
    uasort($actual_operations, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    $this->assertIdentical($expected_operations, $actual_operations, 'The operations are identical.');
  }

  /**
   * Tests the listing UI.
   */
  function testListUI() {
    // Log in as an administrative user to access the full menu trail.
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages')));

    // Get the list callback page.
    $this->drupalGet('admin/structure/config_test');

    // Test for the page title.
    $this->assertTitle('Test configuration | Drupal');

    // Test for the table.
    $element = $this->xpath('//div[@class="l-content"]//table');
    $this->assertTrue($element, 'Configuration entity list table found.');

    // Test the table header.
    $elements = $this->xpath('//div[@class="l-content"]//table/thead/tr/th');
    $this->assertEqual(count($elements), 3, 'Correct number of table header cells found.');

    // Test the contents of each th cell.
    $expected_items = array('Label', 'Machine name', 'Operations');
    foreach ($elements as $key => $element) {
      $this->assertIdentical((string) $element[0], $expected_items[$key]);
    }

    // Check the number of table row cells.
    $elements = $this->xpath('//div[@class="l-content"]//table/tbody/tr[@class="odd"]/td');
    $this->assertEqual(count($elements), 3, 'Correct number of table row cells found.');

    // Check the contents of each row cell. The first cell contains the label,
    // the second contains the machine name, and the third contains the
    // operations list.
    $this->assertIdentical((string) $elements[0], 'Default');
    $this->assertIdentical((string) $elements[1], 'dotted.default');
    $this->assertTrue($elements[2]->children()->xpath('//ul'), 'Operations list found.');

    // Add a new entity using the operations link.
    $this->assertLink('Add test configuration');
    $this->clickLink('Add test configuration');
    $this->assertResponse(200);
    $edit = array(
      'label' => 'Antelope',
      'id' => 'antelope',
      'weight' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Ensure that the entity's sort method was called.
    $this->assertTrue(\Drupal::state()->get('config_entity_sort'), 'ConfigTest::sort() was called.');

    // Confirm that the user is returned to the listing, and verify that the
    // text of the label and machine name appears in the list (versus elsewhere
    // on the page).
    $this->assertFieldByXpath('//td', 'Antelope', "Label found for added 'Antelope' entity.");
    $this->assertFieldByXpath('//td', 'antelope', "Machine name found for added 'Antelope' entity.");

    // Edit the entity using the operations link.
    $this->assertLinkByHref('admin/structure/config_test/manage/antelope');
    $this->clickLink('Edit', 1);
    $this->assertResponse(200);
    $this->assertTitle('Edit Antelope | Drupal');
    $edit = array('label' => 'Albatross', 'id' => 'albatross');
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Confirm that the user is returned to the listing, and verify that the
    // text of the label and machine name appears in the list (versus elsewhere
    // on the page).
    $this->assertFieldByXpath('//td', 'Albatross', "Label found for updated 'Albatross' entity.");
    $this->assertFieldByXpath('//td', 'albatross', "Machine name found for updated 'Albatross' entity.");

    // Delete the added entity using the operations link.
    $this->assertLinkByHref('admin/structure/config_test/manage/albatross/delete');
    $this->clickLink('Delete', 1);
    $this->assertResponse(200);
    $this->assertTitle('Are you sure you want to delete Albatross | Drupal');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertNoFieldByXpath('//td', 'Albatross', "No label found for deleted 'Albatross' entity.");
    $this->assertNoFieldByXpath('//td', 'albatross', "No machine name found for deleted 'Albatross' entity.");

    // Delete the original entity using the operations link.
    $this->clickLink('Delete');
    $this->assertResponse(200);
    $this->assertTitle('Are you sure you want to delete Default | Drupal');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertNoFieldByXpath('//td', 'Default', "No label found for deleted 'Default' entity.");
    $this->assertNoFieldByXpath('//td', 'dotted.default', "No machine name found for deleted 'Default' entity.");

    // Confirm that the empty text is displayed.
    $this->assertText('There is no Test configuration yet.');
  }

}
