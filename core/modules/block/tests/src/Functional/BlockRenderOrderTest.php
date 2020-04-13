<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests blocks are being rendered in order by weight.
 *
 * @group block
 */
class BlockRenderOrderTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    // Create a test user.
    $end_user = $this->drupalCreateUser([
      'access content',
    ]);
    $this->drupalLogin($end_user);
  }

  /**
   * Tests the render order of the blocks.
   */
  public function testBlockRenderOrder() {
    // Enable test blocks and place them in the same region.
    $region = 'header';
    $test_blocks = [
      'stark_powered' => [
        'weight' => '-3',
        'id' => 'stark_powered',
        'label' => 'Test block A',
      ],
      'stark_by' => [
        'weight' => '3',
        'id' => 'stark_by',
        'label' => 'Test block C',
      ],
      'stark_drupal' => [
        'weight' => '3',
        'id' => 'stark_drupal',
        'label' => 'Test block B',
      ],
    ];

    // Place the test blocks.
    foreach ($test_blocks as $test_block) {
      $this->drupalPlaceBlock('system_powered_by_block', [
        'label' => $test_block['label'],
        'region' => $region,
        'weight' => $test_block['weight'],
        'id' => $test_block['id'],
      ]);
    }

    $this->drupalGet('');
    $test_content = $this->getSession()->getPage()->getContent();

    $controller = $this->container->get('entity_type.manager')->getStorage('block');
    foreach ($controller->loadMultiple() as $return_block) {
      $id = $return_block->id();
      if ($return_block_weight = $return_block->getWeight()) {
        $this->assertTrue($test_blocks[$id]['weight'] == $return_block_weight, 'Block weight is set as "' . $return_block_weight . '" for ' . $id . ' block.');
        $position[$id] = strpos($test_content, Html::getClass('block-' . $test_blocks[$id]['id']));
      }
    }
    $this->assertTrue($position['stark_powered'] < $position['stark_by'], 'Blocks with different weight are rendered in the correct order.');
    $this->assertTrue($position['stark_drupal'] < $position['stark_by'], 'Blocks with identical weight are rendered in alphabetical order.');
  }

}
