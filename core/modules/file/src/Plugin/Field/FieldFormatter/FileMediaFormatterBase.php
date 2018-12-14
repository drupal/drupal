<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Base class for media file formatter.
 */
abstract class FileMediaFormatterBase extends FileFormatterBase implements FileMediaFormatterInterface {

  /**
   * Gets the HTML tag for the formatter.
   *
   * @return string
   *   The HTML tag of this formatter.
   */
  protected function getHtmlTag() {
    return static::getMediaType();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'controls' => TRUE,
      'autoplay' => FALSE,
      'loop' => FALSE,
      'multiple_file_display_type' => 'tags',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'controls' => [
        '#title' => $this->t('Show playback controls'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('controls'),
      ],
      'autoplay' => [
        '#title' => $this->t('Autoplay'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('autoplay'),
      ],
      'loop' => [
        '#title' => $this->t('Loop'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('loop'),
      ],
      'multiple_file_display_type' => [
        '#title' => $this->t('Display of multiple files'),
        '#type' => 'radios',
        '#options' => [
          'tags' => $this->t('Use multiple @tag tags, each with a single source.', ['@tag' => '<' . $this->getHtmlTag() . '>']),
          'sources' => $this->t('Use multiple sources within a single @tag tag.', ['@tag' => '<' . $this->getHtmlTag() . '>']),
        ],
        '#default_value' => $this->getSetting('multiple_file_display_type'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if (!parent::isApplicable($field_definition)) {
      return FALSE;
    }
    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $extension_mime_type_guesser */
    $extension_mime_type_guesser = \Drupal::service('file.mime_type.guesser.extension');
    $extension_list = array_filter(preg_split('/\s+/', $field_definition->getSetting('file_extensions')));

    foreach ($extension_list as $extension) {
      $mime_type = $extension_mime_type_guesser->guess('fakedFile.' . $extension);

      if (static::mimeTypeApplies($mime_type)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Playback controls: %controls', ['%controls' => $this->getSetting('controls') ? $this->t('visible') : $this->t('hidden')]);
    $summary[] = $this->t('Autoplay: %autoplay', ['%autoplay' => $this->getSetting('autoplay') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Loop: %loop', ['%loop' => $this->getSetting('loop') ? $this->t('yes') : $this->t('no')]);
    switch ($this->getSetting('multiple_file_display_type')) {
      case 'tags':
        $summary[] = $this->t('Multiple file display: Multiple HTML tags');
        break;

      case 'sources':
        $summary[] = $this->t('Multiple file display: One HTML tag with multiple sources');
        break;
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $source_files = $this->getSourceFiles($items, $langcode);
    if (empty($source_files)) {
      return $elements;
    }

    $attributes = $this->prepareAttributes();
    foreach ($source_files as $delta => $files) {
      $elements[$delta] = [
        '#theme' => $this->getPluginId(),
        '#attributes' => $attributes,
        '#files' => $files,
        '#cache' => ['tags' => []],
      ];

      $cache_tags = [];
      foreach ($files as $file) {
        $cache_tags = Cache::mergeTags($cache_tags, $file['file']->getCacheTags());
      }
      $elements[$delta]['#cache']['tags'] = $cache_tags;
    }

    return $elements;
  }

  /**
   * Prepare the attributes according to the settings.
   *
   * @param string[] $additional_attributes
   *   Additional attributes to be applied to the HTML element. Attribute names
   *   will be used as key and value in the HTML element.
   *
   * @return \Drupal\Core\Template\Attribute
   *   Container with all the attributes for the HTML tag.
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    $attributes = new Attribute();
    foreach (['controls', 'autoplay', 'loop'] + $additional_attributes as $attribute) {
      if ($this->getSetting($attribute)) {
        $attributes->setAttribute($attribute, $attribute);
      }
    }
    return $attributes;
  }

  /**
   * Check if given MIME type applies to the media type of the formatter.
   *
   * @param string $mime_type
   *   The complete MIME type.
   *
   * @return bool
   *   TRUE if the MIME type applies, FALSE otherwise.
   */
  protected static function mimeTypeApplies($mime_type) {
    list($type) = explode('/', $mime_type, 2);
    return $type === static::getMediaType();
  }

  /**
   * Gets source files with attributes.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return array
   *   Numerically indexed array, which again contains an associative array with
   *   the following key/values:
   *     - file => \Drupal\file\Entity\File
   *     - source_attributes => \Drupal\Core\Template\Attribute
   */
  protected function getSourceFiles(EntityReferenceFieldItemListInterface $items, $langcode) {
    $source_files = [];
    // Because we can have the files grouped in a single media tag, we do a
    // grouping in case the multiple file behavior is not 'tags'.
    /** @var \Drupal\file\Entity\File $file */
    foreach ($this->getEntitiesToView($items, $langcode) as $file) {
      if (static::mimeTypeApplies($file->getMimeType())) {
        $source_attributes = new Attribute();
        $source_attributes
          ->setAttribute('src', $file->createFileUrl())
          ->setAttribute('type', $file->getMimeType());
        if ($this->getSetting('multiple_file_display_type') === 'tags') {
          $source_files[] = [
            [
              'file' => $file,
              'source_attributes' => $source_attributes,
            ],
          ];
        }
        else {
          $source_files[0][] = [
            'file' => $file,
            'source_attributes' => $source_attributes,
          ];
        }
      }
    }
    return $source_files;
  }

}
