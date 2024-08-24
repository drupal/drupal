<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'TestAttributes' block.
 */
#[Block(
  id: "layout_builder_test_test_attributes",
  admin_label: new TranslatableMarkup("Test Attributes"),
  category: new TranslatableMarkup("Test")
)]
class TestAttributesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#attributes' => [
        'class' => ['attribute-test-class'],
        'custom-attribute' => 'test',
      ],
      '#markup' => $this->t('Example block providing its own attributes.'),
      '#contextual_links' => [
        'layout_builder_test' => ['route_parameters' => []],
      ],
    ];
    return $build;
  }

}
