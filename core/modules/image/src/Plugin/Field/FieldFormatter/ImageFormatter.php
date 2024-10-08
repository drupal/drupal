<?php

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'image' formatter.
 */
#[FieldFormatter(
  id: 'image',
  label: new TranslatableMarkup('Image'),
  field_types: [
    'image',
  ],
)]
class ImageFormatter extends ImageFormatterBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
    $this->fileUrlGenerator = $file_url_generator;
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
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
    $element = parent::settingsForm($form, $form_state);

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    $image_loading = $this->getSetting('image_loading');
    $element['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Image loading'),
      '#weight' => 10,
      '#description' => $this->t('Lazy render images with native image loading attribute (<em>loading="lazy"</em>). This improves performance by allowing browsers to lazily load images.'),
    ];
    $loading_attribute_options = [
      'lazy' => $this->t('Lazy (<em>loading="lazy"</em>)'),
      'eager' => $this->t('Eager (<em>loading="eager"</em>)'),
    ];
    $element['image_loading']['attribute'] = [
      '#title' => $this->t('Image loading attribute'),
      '#type' => 'radios',
      '#default_value' => $image_loading['attribute'],
      '#options' => $loading_attribute_options,
      '#description' => $this->t('Select the loading attribute for images. <a href=":link">Learn more about the loading attribute for images.</a>', [
        ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
      ]),
    ];
    $element['image_loading']['attribute']['lazy']['#description'] = $this->t('Delays loading the image until that section of the page is visible in the browser. When in doubt, lazy loading is recommended.');
    $element['image_loading']['attribute']['eager']['#description'] = $this->t('Force browsers to download an image as soon as possible. This is the browser default for legacy reasons. Only use this option when the image is always expected to render.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    $link_types = [
      'content' => $this->t('Linked to content'),
      'file' => $this->t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    $image_loading = $this->getSetting('image_loading');
    $summary[] = $this->t('Image loading: @attribute', [
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
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = $this->fileUrlGenerator->generate($image_uri);
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $image_loading_settings = $this->getSetting('image_loading');
      $item_attributes['loading'] = $image_loading_settings['attribute'];

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
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
    $style_id = $this->getSetting('image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      // If this formatter uses a valid image style to display the image, add
      // the image style configuration entity as dependency of this formatter.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_id = $this->getSetting('image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
        // If a valid replacement has been provided in the storage, replace the
        // image style with the replacement and signal that the formatter plugin
        // settings were updated.
        if ($replacement_id && ImageStyle::load($replacement_id)) {
          $this->setSetting('image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

}
