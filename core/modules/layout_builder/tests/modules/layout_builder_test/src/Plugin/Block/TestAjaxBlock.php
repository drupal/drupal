<?php

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'TestAjax' block.
 */
#[Block(
  id: "layout_builder_test_ajax",
  admin_label: new TranslatableMarkup("TestAjax"),
  category: new TranslatableMarkup("Test")
)]
class TestAjaxBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['ajax_test'] = [
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('Ajax test option 1'),
        2 => $this->t('Ajax test option 2'),
      ],
      '#prefix' => '<div id="test-ajax-wrapper">',
      '#suffix' => '</div>',
      '#title' => $this->t('Time in this ajax test is @time', [
        '@time' => time(),
      ]),
      '#ajax' => [
        'wrapper' => 'test-ajax-wrapper',
        'callback' => [$this, 'ajaxCallback'],
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback.
   */
  public function ajaxCallback($form, $form_state) {
    return $form['settings']['ajax_test'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('Every word is like an unnecessary stain on silence and nothingness.'),
    ];
    return $build;
  }

}
