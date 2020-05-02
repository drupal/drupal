<?php

namespace Drupal\Tests\tour\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\tour\Entity\Tour;

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
  public static $modules = [
    'block',
    'tour',
    'locale',
    'language',
    'tour_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The permissions required for a logged in user to test tour tips.
   *
   * @var array
   *   A list of permissions.
   */
  protected $permissions = ['access tour', 'administer languages'];

  /**
   * Tour tip attributes to be tested. Keyed by the path.
   *
   * @var array
   *   An array of tip attributes, keyed by path.
   */
  protected $tips = [
    'tour-test-1' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block', [
      'theme' => 'seven',
      'region' => 'content',
    ]);
  }

  /**
   * Test tour functionality.
   */
  public function testTourFunctionality() {
    // Navigate to tour-test-1 and verify the tour_test_1 tip is found with appropriate classes.
    $this->drupalGet('tour-test-1');

    // Test the TourTestBase class assertTourTips() method.
    $tips = [];
    $tips[] = ['data-id' => 'tour-test-1'];
    $tips[] = ['data-class' => 'tour-test-5'];
    $this->assertTourTips($tips);
    $this->assertTourTips();

    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./p//a[@href=:href and contains(., :text)]]', [
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':href' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      ':text' => 'Drupal',
    ]);
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
    $tour = Tour::create([
      'id' => 'tour-entity-create-test-en',
      'label' => 'Tour test english',
      'langcode' => 'en',
      'module' => 'system',
      'routes' => [
        ['route_name' => 'tour_test.1'],
      ],
      'tips' => [
        'tour-test-1' => [
          'id' => 'tour-code-test-1',
          'plugin' => 'text',
          'label' => 'The rain in spain',
          'body' => 'Falls mostly on the plain.',
          'weight' => '100',
          'attributes' => [
            'data-id' => 'tour-code-test-1',
          ],
        ],
        'tour-code-test-2' => [
          'id' => 'tour-code-test-2',
          'plugin' => 'image',
          'label' => 'The awesome image',
          'url' => 'http://local/image.png',
          'weight' => 1,
          'attributes' => [
            'data-id' => 'tour-code-test-2',
          ],
        ],
      ],
    ]);
    $tour->save();

    // Ensure that a tour entity has the expected dependencies based on plugin
    // providers and the module named in the configuration entity.
    $dependencies = $tour->calculateDependencies()->getDependencies();
    $this->assertEqual($dependencies['module'], ['system', 'tour_test']);

    $this->drupalGet('tour-test-1');

    // Load it back from the database and verify storage worked.
    $entity_save_tip = Tour::load('tour-entity-create-test-en');
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
    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./h2[contains(., :text)]]', [
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':text' => 'The first tip',
    ]);
    $this->assertEqual(count($elements), 1, 'Found English variant of tip 1.');

    // Navigate to tour-test-3 and verify the tour_test_1 tip is not found with
    // appropriate classes.
    $this->drupalGet('tour-test-3/bar');
    $elements = $this->xpath('//li[@data-id=:data_id and @class=:classes and ./h2[contains(., :text)]]', [
      ':classes' => 'tip-module-tour-test tip-type-text tip-tour-test-1',
      ':data_id' => 'tour-test-1',
      ':text' => 'The first tip',
    ]);
    $this->assertEqual(count($elements), 0, 'Did not find English variant of tip 1.');
  }

}
