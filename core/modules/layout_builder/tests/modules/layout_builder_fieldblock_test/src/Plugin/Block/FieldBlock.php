<?php

declare(strict_types=1);

namespace Drupal\layout_builder_fieldblock_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\layout_builder\Plugin\Block\FieldBlock as LayoutBuilderFieldBlock;
use Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver;

/**
 * Provides test field block to test with Block UI.
 *
 * \Drupal\Tests\layout_builder\FunctionalJavascript\FieldBlockTest provides
 * test coverage of complex AJAX interactions within certain field blocks.
 * layout_builder_plugin_filter_block__block_ui_alter() removes certain blocks
 * with 'layout_builder' as the provider. To make these blocks available during
 * testing, this plugin uses the same deriver but each derivative will have a
 * different provider.
 *
 * @see \Drupal\Tests\layout_builder\FunctionalJavascript\FieldBlockTest
 * @see layout_builder_plugin_filter_block__block_ui_alter()
 */
#[Block(
  id: "field_block_test",
  deriver: FieldBlockDeriver::class
)]
class FieldBlock extends LayoutBuilderFieldBlock {

}
