<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'file_video' formatter.
 */
#[FieldFormatter(
  id: 'file_video',
  label: new TranslatableMarkup('Video'),
  description: new TranslatableMarkup('Display the file using an HTML5 video tag.'),
  field_types: [
    'file',
  ],
)]
class FileVideoFormatter extends FileMediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'muted' => FALSE,
      'width' => 640,
      'height' => 480,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'muted' => [
        '#title' => $this->t('Muted'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('muted'),
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        // A width of zero pixels would make this video invisible.
        '#min' => 1,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        // A height of zero pixels would make this video invisible.
        '#min' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Muted: %muted', ['%muted' => $this->getSetting('muted') ? $this->t('yes') : $this->t('no')]);

    if ($width = $this->getSetting('width')) {
      $summary[] = $this->t('Width: %width pixels', [
        '%width' => $width,
      ]);
    }

    if ($height = $this->getSetting('height')) {
      $summary[] = $this->t('Height: %height pixels', [
        '%height' => $height,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    $attributes = parent::prepareAttributes(['muted']);
    if (($width = $this->getSetting('width'))) {
      $attributes->setAttribute('width', $width);
    }
    if (($height = $this->getSetting('height'))) {
      $attributes->setAttribute('height', $height);
    }
    return $attributes;
  }

}
