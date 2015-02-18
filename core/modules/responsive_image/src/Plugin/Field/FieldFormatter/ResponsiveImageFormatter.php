<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Plugin\field\formatter\ResponsiveImageFormatter.
 */

namespace Drupal\responsive_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_image",
 *   label = @Translation("Responsive image"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ResponsiveImageFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * Constructs a ResponsiveImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $responsive_image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('responsive_image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'responsive_image_style' => '',
      'fallback_image_style' => '',
      'image_link' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_options = array();
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style'] = array(
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
    );

    $image_styles = image_style_options(FALSE);
    $elements['fallback_image_style'] = array(
      '#title' => t('Fallback image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('fallback_image_style'),
      '#empty_option' => t('Automatic'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $elements['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', array('@responsive_image_style' => $responsive_image_style->label()));

      $image_styles = image_style_options(FALSE);
      unset($image_styles['']);
      if (isset($image_styles[$this->getSetting('fallback_image_style')])) {
        $summary[] = t('Fallback Image style: @style', array('@style' => $image_styles[$this->getSetting('fallback_image_style')]));
      }
      else {
        $summary[] = t('Automatic fallback');
      }

      $link_types = array(
        'content' => t('Linked to content'),
        'file' => t('Linked to file'),
      );
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('image_link')])) {
        $summary[] = $link_types[$this->getSetting('image_link')];
      }
    }
    else {
      $summary[] = t('Select a responsive image style.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $url = NULL;
    // Check if the formatter involves a link.
    if ($this->getSetting('image_link') == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($this->getSetting('image_link') == 'file') {
      $link_file = TRUE;
    }

    $fallback_image_style = '';

    // Check if the user defined a custom fallback image style.
    if ($this->getSetting('fallback_image_style')) {
      $fallback_image_style = $this->getSetting('fallback_image_style');
    }

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles_to_load = array();
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    // If there is a fallback image style, add it to the image styles to load.
    if ($fallback_image_style) {
      $image_styles_to_load[] = $fallback_image_style;
    }
    else {
      // The <picture> element uses the first matching breakpoint (see
      // http://www.w3.org/html/wg/drafts/html/master/embedded-content.html#update-the-source-set
      // points 2 and 3). Meaning the breakpoints are sorted from large to
      // small. With mobile-first in mind, the fallback image should be the one
      // selected for the smallest screen.
      $fallback_image_style = end($image_styles_to_load);
    }
    $image_styles = ImageStyle::loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    foreach ($items as $delta => $item) {
      // Link the <picture> element to the original file.
      if (isset($link_file)) {
        $url = Url::fromUri(file_create_url($item->entity->getFileUri()));
      }
      $elements[$delta] = array(
        '#theme' => 'responsive_image_formatter',
        '#attached' => array(
          'library' => array(
            'core/picturefill',
          ),
        ),
        '#item' => $item,
        '#image_style' => $fallback_image_style,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#url' => $url,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }

    return $elements;
  }
}
