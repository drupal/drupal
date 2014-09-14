<?php

/**
 * @file
 * Contains \Drupal\tour\Tests\TourTest.
 */

namespace Drupal\tour\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the functionality of tour tips.
 *
 * @group tour
 */
class TourTest extends TourTestBasic {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tour', 'locale', 'language', 'tour_test');

  /**
   * The permissions required for a logged in user to test tour tips.
   *
   * @var array
   *   A list of permissions.
   */
  protected $permissions = array('access tour', 'administer languages');

  /**
   * Tour tip attributes to be tested. Keyed by the path.
   *
   * @var array
   *   An array of tip attributes, keyed by path.
   */
  protected $tips = array(
    'tour-test-1' => array(),
  );

  /**
   * Test tour functionality.
   */
  public function testTourFunctionality() {
    // Navigate to tour-test-1 and verify the tour_test_1 tip is found with appropriate classes.
    $this->drupalGet('tour-test-1');

    // Test the TourTestBase class assertTourTips() method.
    $tips = array();
    $tips[] = array('data-id' => 'tour-test-1');
    $tips[] = array('data-class' => 'tour-test-5');
    $this->assertTourTips($tips);
    $this->assertTourTips();

    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./p//a[@href=:href and contains(., :text)]]', array(
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':href' =>  url('<front>', array('absolute' => TRUE)),
      ':text' => 'Drupal',
    ));
    $this->assertEqual(count($elements), 1, 'Found Token replacement.');

    $elements = $this->cssSelect("li[data-id=tour-test-1] h2:contains('The first tip')");
    $this->assertEqual(count($elements), 1, 'Found English variant of tip 1.');

    $elements = $this->cssSelect("li[data-id=tour-test-2] h2:contains('The quick brown fox')");
    $this->assertNotEqual(count($elements), 1, 'Did not find English variant of tip 2.');

    $elements = $this->cssSelect("li[data-id=tour-test-1] h2:contains('La pioggia cade in spagna')");
    $this->assertNotEqual(count($elements), 1, 'Did not find Italian variant of tip 1.');

    // Ensure that plugins work.
    $elements = $this->xpath('//img[@src="http://local/image.png"]');
    $this->assertEqual(count($elements), 1, 'Image plugin tip found.');

    // Navigate to tour-test-2/subpath and verify the tour_test_2 tip is found.
    $this->drupalGet('tour-test-2/subpath');
    $elements = $this->cssSelect("li[data-id=tour-test-2] h2:contains('The quick brown fox')");
    $this->assertEqual(count($elements), 1, 'Found English variant of tip 2.');

    $elements = $this->cssSelect("li[data-id=tour-test-1] h2:contains('The first tip')");
    $this->assertNotEqual(count($elements), 1, 'Did not find English variant of tip 1.');

    // Enable Italian language and navigate to it/tour-test1 and verify italian
    // version of tip is found.
    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->drupalGet('it/tour-test-1');

    $elements = $this->cssSelect("li[data-id=tour-test-1] h2:contains('La pioggia cade in spagna')");
    $this->assertEqual(count($elements), 1, 'Found Italian variant of tip 1.');

    $elements = $this->cssSelect("li[data-id=tour-test-2] h2:contains('The quick brown fox')");
    $this->assertNotEqual(count($elements), 1, 'Did not find English variant of tip 1.');

    // Programmatically create a tour for use through the remainder of the test.
    $tour = entity_create('tour', array(
      'id' => 'tour-entity-create-test-en',
      'label' => 'Tour test english',
      'langcode' => 'en',
      'module' => 'system',
      'routes' => array(
        array('route_name' => 'tour_test.1'),
      ),
      'tips' => array(
        'tour-test-1' => array(
          'id' => 'tour-code-test-1',
          'plugin' => 'text',
          'label' => 'The rain in spain',
          'body' => 'Falls mostly on the plain.',
          'weight' => '100',
          'attributes' => array(
            'data-id' => 'tour-code-test-1',
          ),
        ),
        'tour-code-test-2' => array(
          'id' => 'tour-code-test-2',
          'plugin' => 'image',
          'label' => 'The awesome image',
          'url' => 'http://local/image.png',
          'weight' => 1,
          'attributes' => array(
            'data-id' => 'tour-code-test-2'
          ),
        ),
      ),
    ));
    $tour->save();

    // Ensure that a tour entity has the expected dependencies based on plugin
    // providers and the module named in the configuration entity.
    $dependencies = $tour->calculateDependencies();
    $this->assertEqual($dependencies['module'], array('system', 'tour_test'));

    $this->drupalGet('tour-test-1');

    // Load it back from the database and verify storage worked.
    $entity_save_tip = entity_load('tour', 'tour-entity-create-test-en');
    // Verify that hook_ENTITY_TYPE_load() integration worked.
    $this->assertEqual($entity_save_tip->loaded, 'Load hooks work');
    // Verify that hook_ENTITY_TYPE_presave() integration worked.
    $this->assertEqual($entity_save_tip->label(), 'Tour test english alter');

    // Navigate to tour-test-1 and verify the new tip is found.
    $this->drupalGet('tour-test-1');
    $elements = $this->cssSelect("li[data-id=tour-code-test-1] h2:contains('The rain in spain')");
    $this->assertEqual(count($elements), 1, 'Found the required tip markup for tip 4');

    // Verify that the weight sorting works by ensuring the lower weight item
    // (tip 4) has the 'End tour' button.
    $elements = $this->cssSelect("li[data-id=tour-code-test-1][data-text='End tour']");
    $this->assertEqual(count($elements), 1, 'Found code tip was weighted last and had "End tour".');

    // Test hook_tour_alter().
    $this->assertText('Altered by hook_tour_tips_alter');

    // Navigate to tour-test-3 and verify the tour_test_1 tip is found with
    // appropriate classes.
    $this->drupalGet('tour-test-3/foo');
    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./h2[contains(., :text)]]', array(
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':text' => 'The first tip',
    ));
    $this->assertEqual(count($elements), 1, 'Found English variant of tip 1.');

    // Navigate to tour-test-3 and verify the tour_test_1 tip is not found with
    // appropriate classes.
    $this->drupalGet('tour-test-3/bar');
    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./h2[contains(., :text)]]', array(
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':text' => 'The first tip',
    ));
    $this->assertEqual(count($elements), 0, 'Did not find English variant of tip 1.');
  }
}
