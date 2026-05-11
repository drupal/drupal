<?php

namespace Drupal\responsive_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Plugin for responsive image formatter.
 */
#[FieldFormatter(
  id: 'responsive_image',
  label: new TranslatableMarkup('Responsive image'),
  field_types: [
    'image',
  ],
)]
class ResponsiveImageFormatter extends ImageFormatterBase {

  /**
   * The file url generator.
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityStorageInterface $responsiveImageStyleStorage,
    protected EntityStorageInterface $imageStyleStorage,
    protected LinkGeneratorInterface $linkGenerator,
    protected AccountInterface $currentUser,
    ?FileUrlGeneratorInterface $fileUrlGenerator = NULL,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    if (!$fileUrlGenerator) {
      @trigger_error('Calling ResponsiveImageFormatter::__construct() without the $fileUrlGenerator argument is deprecated in drupal:11.4.0 and the $fileUrlGenerator argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3291487', E_USER_DEPRECATED);
      $fileUrlGenerator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $fileUrlGenerator;
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
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_image_style' => '',
      'image_link' => '',
      'image_loading' => [
        'attribute' => 'lazy',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    uasort($responsive_image_styles, '\Drupal\responsive_image\Entity\ResponsiveImageStyle::sort');
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style'] = [
      '#title' => $this->t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style') ?: NULL,
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];

    $image_loading = $this->getSetting('image_loading');
    $elements['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Image loading'),
      '#weight' => 10,
      '#description' => $this->t('Lazy render images with native image loading attribute (<em>loading="lazy"</em>). This improves performance by allowing browsers to lazily load images. See <a href="@url">Lazy loading</a>.', [
        '@url' => 'https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading#images_and_iframes',
      ]),
    ];
    $loading_attribute_options = [
      'lazy' => $this->t('Lazy'),
      'eager' => $this->t('Eager'),
    ];
    $elements['image_loading']['attribute'] = [
      '#title' => $this->t('Lazy loading attribute'),
      '#type' => 'select',
      '#default_value' => $image_loading['attribute'],
      '#options' => $loading_attribute_options,
      '#description' => $this->t('Select the lazy loading attribute for images. <a href=":link">Learn more.</a>', [
        ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
      ]),
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $elements['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = $this->t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);

      $link_types = [
        'content' => $this->t('Linked to content'),
        'file' => $this->t('Linked to file'),
      ];
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('image_link')])) {
        $summary[] = $link_types[$this->getSetting('image_link')];
      }
    }
    else {
      $summary[] = $this->t('Select a responsive image style.');
    }

    $image_loading = $this->getSetting('image_loading');
    $summary[] = $this->t('Loading attribute: @attribute', [
      '@attribute' => $image_loading['attribute'],
    ]);

    return array_merge($summary, parent::settingsSummary());
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    // Check if the formatter involves a link.
    if ($this->getSetting('image_link') == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($this->getSetting('image_link') == 'file') {
      $link_file = TRUE;
    }

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles_to_load = [];
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    foreach ($files as $delta => $file) {
      assert($file instanceof FileInterface);
      // Link the <picture> element to the original file.
      if (isset($link_file)) {
        $url = $this->fileUrlGenerator->generate($file->getFileUri());
      }
      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $attributes = $item->_attributes;
      unset($item->_attributes);

      $image_loading_settings = $this->getSetting('image_loading');
      $attributes['loading'] = $image_loading_settings['attribute'];

      $elements[$delta] = [
        '#theme' => 'responsive_image_formatter',
        '#item' => $item,
        '#attributes' => $attributes,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('responsive_image_style');
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
    if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
      // Add the responsive image style as dependency.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

}
