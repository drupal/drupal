<?php

namespace Drupal\demo_umami\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Umami disclaimer' block.
 *
 * @Block(
 *   id = "umami_disclaimer",
 *   admin_label = @Translation("Umami disclaimer")
 * )
 *
 * @internal
 *   This code is only for use by the Umami demo profile.
 */
class UmamiDisclaimer extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'umami_disclaimer' => [
        'value' => '',
        'format' => '',
      ],
      'umami_copyright' => [
        'value' => '',
        'format' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $disclaimer_markup = check_markup($this->configuration['umami_disclaimer']['value'], $this->configuration['umami_disclaimer']['format']);
    $copyright_markup = check_markup($this->configuration['umami_copyright']['value'], $this->configuration['umami_copyright']['format']);

    return [
      '#type' => 'inline_template',
      '#template' => '<span class="umami-disclaimer">{{ disclaimer }}</span><span class="umami-copyright">{{ copyright }}</span>',
      '#context' => [
        'disclaimer' => $disclaimer_markup,
        'copyright' => $copyright_markup,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['umami_disclaimer'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Umami Disclaimer'),
      '#default_value' => $this->configuration['umami_disclaimer']['value'],
      '#format' => $this->configuration['umami_disclaimer']['format'],
    ];

    $form['umami_copyright'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Umami Copyright Text'),
      '#default_value' => $this->configuration['umami_copyright']['value'],
      '#format' => $this->configuration['umami_copyright']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['umami_disclaimer'] = $form_state->getValue('umami_disclaimer');
    $this->configuration['umami_copyright'] = $form_state->getValue('umami_copyright');
  }

}
