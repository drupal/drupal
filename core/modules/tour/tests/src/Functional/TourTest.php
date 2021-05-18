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
  protected static $modules = [
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
  protected function setUp(): void {
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

    $tips = $this->getTourTips();

    $href = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $elements = [];
    foreach ($tips as $tip) {
      if ($tip['id'] == 'tour-test-1' && $tip['module'] == 'tour_test' && $tip['type'] == 'text' && strpos($tip['body'], $href) !== FALSE && strpos($tip['body'], 'Drupal') !== FALSE) {
        $elements[] = $tip;
      }
    }
    $this->assertCount(1, $elements, 'Found Token replacement.');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'title' => 'The first tip',
    ]);
    $this->assertCount(1, $elements, 'Found English variant of tip 1.');

    $elements = $this->findTip([
      'id' => 'tour-test-2',
      'title' => 'The quick brown fox',
    ]);
    $this->assertNotCount(1, $elements, 'Did not find English variant of tip 2.');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'title' => 'La pioggia cade in spagna',
    ]);
    $this->assertNotCount(1, $elements, 'Did not find Italian variant of tip 1.');

    // Ensure that plugins work.
    $elements = [];
    foreach ($tips as $tip) {
      if (strpos($tip['body'], 'http://local/image.png') !== FALSE) {
        $elements[] = $tip;
      }
    }
    $this->assertCount(1, $elements, 'Image plugin tip found.');

    // Navigate to tour-test-2/subpath and verify the tour_test_2 tip is found.
    $this->drupalGet('tour-test-2/subpath');

    $elements = $this->findTip([
      'id' => 'tour-test-2',
      'title' => 'The quick brown fox',
    ]);
    $this->assertCount(1, $elements, 'Found English variant of tip 2.');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'title' => 'The first tip',
    ]);
    $this->assertNotCount(1, $elements, 'Did not find English variant of tip 1.');

    // Enable Italian language and navigate to it/tour-test1 and verify italian
    // version of tip is found.
    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->drupalGet('it/tour-test-1');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'title' => 'La pioggia cade in spagna',
    ]);
    $this->assertCount(1, $elements, 'Found Italian variant of tip 1.');

    $elements = $this->findTip([
      'id' => 'tour-test-2',
      'title' => 'The quick brown fox',
    ]);
    $this->assertNotCount(1, $elements, 'Did not find English variant of tip 1.');

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
          'selector' => '#tour-code-test-1',
        ],
        'tour-code-test-2' => [
          'id' => 'tour-code-test-2',
          'plugin' => 'image',
          'label' => 'The awesome image',
          'url' => 'http://local/image.png',
          'weight' => 1,
          'selector' => '#tour-code-test-2',
        ],
      ],
    ]);
    $tour->save();

    // Ensure that a tour entity has the expected dependencies based on plugin
    // providers and the module named in the configuration entity.
    $dependencies = $tour->calculateDependencies()->getDependencies();
    $this->assertEqual(['system', 'tour_test'], $dependencies['module']);

    $this->drupalGet('tour-test-1');

    // Load it back from the database and verify storage worked.
    $entity_save_tip = Tour::load('tour-entity-create-test-en');
    // Verify that hook_ENTITY_TYPE_load() integration worked.
    $this->assertEqual('Load hooks work', $entity_save_tip->loaded);
    // Verify that hook_ENTITY_TYPE_presave() integration worked.
    $this->assertEqual('Tour test english alter', $entity_save_tip->label());

    // Navigate to tour-test-1 and verify the new tip is found.
    $this->drupalGet('tour-test-1');

    $elements = $this->findTip([
      'id' => 'tour-code-test-1',
      'title' => 'The rain in spain',
    ]);
    $this->assertCount(1, $elements, 'Found the required tip markup for tip 4');

    // Verify that the weight sorting works by ensuring the lower weight item
    // (tip 4) has the 'End tour' button.
    $elements = $this->findTip([
      'id' => 'tour-code-test-1',
      'text' => 'End tour',
    ]);
    $this->assertCount(1, $elements, 'Found code tip was weighted last and had "End tour".');

    // Test hook_tour_alter().
    $this->assertText('Altered by hook_tour_tips_alter');

    // Navigate to tour-test-3 and verify the tour_test_1 tip is found with
    // appropriate classes.
    $this->drupalGet('tour-test-3/foo');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'module' => 'tour_test',
      'type' => 'text',
      'title' => 'The first tip',
    ]);
    $this->assertCount(1, $elements, 'Found English variant of tip 1.');

    // Navigate to tour-test-3 and verify the tour_test_1 tip is not found with
    // appropriate classes.
    $this->drupalGet('tour-test-3/bar');

    $elements = $this->findTip([
      'id' => 'tour-test-1',
      'module' => 'tour_test',
      'type' => 'text',
      'title' => 'The first tip',
    ]);
    $this->assertCount(0, $elements, 'Did not find English variant of tip 1.');
  }

  /**
   * Gets tour tips from the JavaScript drupalSettings variable.
   *
   * @return array
   *   A list of tips and their data.
   */
  protected function getTourTips() {
    $tips = [];
    $drupalSettings = $this->getDrupalSettings();
    if (isset($drupalSettings['_tour_internal'])) {
      foreach ($drupalSettings['_tour_internal'] as $tip) {
        $tips[] = $tip;
      }
    }

    return $tips;
  }

  /**
   * Find specific tips by their parameters in the list of tips.
   *
   * @param array $params
   *   The list of search parameters and their values.
   *
   * @return array
   *   A list of tips which match the parameters.
   */
  protected function findTip(array $params) {
    $tips = $this->getTourTips();
    $elements = [];
    foreach ($tips as $tip) {
      foreach ($params as $param => $value) {
        if (isset($tip[$param]) && $tip[$param] != $value) {
          continue 2;
        }
      }
      $elements[] = $tip;
    }

    return $elements;
  }

  /**
   * Test that warnings and deprecations are triggered.
   *
   * @group legacy
   */
  public function testDeprecatedMethodWarningsErrors() {
    \Drupal::service('module_installer')->install(['tour_legacy_test']);
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_WARNING) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    $tip = Tour::load('tour-test')->getTips()[0];

    // These are E_USER_WARNING severity errors that supplement existing
    // deprecation errors. These warnings are triggered when methods are called
    // that are designed to be backwards compatible, but aren't able to 100%
    // promise this due to the many ways that tip plugins can be extended.
    try {
      $tip->getOutput();
      $this->fail('No getOutput() warning triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('Drupal\tourTipPluginInterface::getOutput is deprecated. Use getBody() instead. See https://www.drupal.org/node/3204096', $e->getMessage());
    }

    try {
      $tip->getAttributes();
      $this->fail('No getAttributes() warning triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('Drupal\tour\TipPluginInterface::getAttributes is deprecated. Tour tip plugins should implement Drupal\tour\TourTipPluginInterface and Tour configs should use the \'selector\' property instead of \'attributes\' to target an element.', $e->getMessage());
    }

    $this->expectDeprecation('Implementing Drupal\tour\TipPluginInterface without also implementing Drupal\tour\TourTipPluginInterface is deprecated in drupal:9.2.0. See https://www.drupal.org/node/3204096');
    $this->expectDeprecation("The tour.tip 'attributes' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead of 'data-class' and 'data-id' attributes, use 'selector' to specify the element a tip attaches to. See https://www.drupal.org/node/3204093");
    $this->expectDeprecation("The tour.tip 'location' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead use 'position'. The value must be a valid placement accepted by PopperJS. See https://www.drupal.org/node/3204093");

    try {
      $deprecated_tour =
        \Drupal::entityTypeManager()
          ->getViewBuilder('tour')
          ->viewMultiple([Tour::load('tour-test-legacy')], 'full');
      $this->fail('No deprecated interface warning triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('The tour tips only support data-class and data-id attributes and they will have to be upgraded manually. See https://www.drupal.org/node/3204093', $e->getMessage());
    }

    restore_error_handler();
  }

}
