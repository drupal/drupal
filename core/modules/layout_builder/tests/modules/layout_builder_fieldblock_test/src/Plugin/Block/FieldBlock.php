<?php

namespace Drupal\layout_builder_fieldblock_test\Plugin\Block;

use Drupal\layout_builder\Plugin\Block\FieldBlock as LayoutBuilderFieldBlock;

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
 * @Block(
 *   id = "field_block_test",
 *   deriver = "\Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver",
 * )
 *
 * @see \Drupal\Tests\layout_builder\FunctionalJavascript\FieldBlockTest
 * @see layout_builder_plugin_filter_block__block_ui_alter()
 */
class FieldBlock extends LayoutBuilderFieldBlock {

}
