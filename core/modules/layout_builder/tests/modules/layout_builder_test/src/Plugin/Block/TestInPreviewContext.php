<?php

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that varies based on whether it is being previewed.
 *
 * @Block(
 *   id = "layout_builder_test_in_preview",
 *   admin_label = @Translation("In Preview"),
 *   category = @Translation("Test"),
 *   context_definitions = {
 *     "in_preview" = @ContextDefinition("boolean",
 *       default_value = FALSE,
 *       required = TRUE,
 *     ),
 *   },
 * )
 */
class TestInPreviewContext extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $markup = 'This is not during preview';
    if ($this->getContextValue('in_preview') === TRUE) {
      $markup = 'This is during preview';
    }

    return ['#markup' => $markup];
  }

}
