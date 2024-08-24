<?php

declare(strict_types=1);

namespace Drupal\editor_test\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Attribute\Editor;
use Drupal\editor\Entity\Editor as EditorEntity;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Tyrannosaurus-Rex powered text editor for testing purposes.
 */
#[Editor(
  id: 'trex',
  label: new TranslatableMarkup('TRex Editor'),
  supports_content_filtering: TRUE,
  supports_inline_editing: TRUE,
  is_xss_safe: FALSE,
  supported_element_types: [
    'textarea',
  ]
)]
class TRexEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return ['stumpy_arms' => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['stumpy_arms'] = [
      '#title' => $this->t('Stumpy arms'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(EditorEntity $editor) {
    $js_settings = [];
    $settings = $editor->getSettings();
    if ($settings['stumpy_arms']) {
      $js_settings['doMyArmsLookStumpy'] = TRUE;
    }
    return $js_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(EditorEntity $editor) {
    return [
      'editor_test/trex',
    ];
  }

}
