<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests the big_pipe_theme_suggestions_big_pipe_interface_preview() function.
 *
 * @group big_pipe
 */
class BigPipeInterfacePreviewThemeSuggestionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'big_pipe', 'system'];

  /**
   * The block being tested.
   *
   * @var \Drupal\block\Entity\BlockInterface
   */
  protected $block;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $controller;

  /**
   * The block view builder.
   *
   * @var \Drupal\block\BlockViewBuilder
   */
  protected $blockViewBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controller = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');

    $this->blockViewBuilder = $this->container
      ->get('entity_type.manager')
      ->getViewBuilder('block');

    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Tests template suggestions from big_pipe_theme_suggestions_big_pipe_interface_preview().
   */
  public function testBigPipeThemeHookSuggestions(): void {
    $entity = $this->controller->create([
      'id' => 'test_block1',
      'theme' => 'stark',
      'plugin' => 'system_powered_by_block',
    ]);
    $entity->save();

    // Test the rendering of a block.
    $block = Block::load('test_block1');
    // Using the BlockViewBuilder we will be able to get a lovely
    // #lazy_builder callback assigned.
    $build = $this->blockViewBuilder->view($block);

    $variables = [];
    // In turn this is what createBigPipeJsPlaceholder() uses to
    // build the BigPipe JS placeholder render array which is used as input
    // for big_pipe_theme_suggestions_big_pipe_interface_preview().
    $variables['callback'] = $build['#lazy_builder'][0];
    $variables['arguments'] = $build['#lazy_builder'][1];
    $suggestions = big_pipe_theme_suggestions_big_pipe_interface_preview($variables);
    $suggested_id = preg_replace('/[^a-zA-Z0-9]/', '_', $block->id());
    $this->assertSame([
      'big_pipe_interface_preview__block',
      'big_pipe_interface_preview__block__' . $suggested_id,
      'big_pipe_interface_preview__block__full',
    ], $suggestions);
  }

}
