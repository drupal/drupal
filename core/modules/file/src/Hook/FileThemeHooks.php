<?php

declare(strict_types=1);

namespace Drupal\file\Hook;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\file\IconMimeTypes;

/**
 * Theme hooks for the file module.
 */
class FileThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected RendererInterface $renderer,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      // From file.module.
      'file_link' => [
        'variables' => [
          'file' => NULL,
          'description' => NULL,
          'attributes' => [],
          'with_size' => TRUE,
        ],
        'initial preprocess' => static::class . ':preprocessFileLink',
      ],
      'file_managed_file' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessManagedFile',
      ],
      'file_audio' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
      'file_video' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
      'file_widget_multiple' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessFileWidgetMultiple',
      ],
      'file_upload_help' => [
        'variables' => [
          'description' => NULL,
          'upload_validators' => NULL,
          'cardinality' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessFileUploadHelp',
      ],
    ];
  }

  /**
   * Prepares variables for file link templates.
   *
   * Default template: file-link.html.twig.
   *
   * @param array<string,mixed> $variables
   *   An associative array containing:
   *   - file: A File entity to which the link will be created.
   *   - icon_directory: (optional) A path to a directory of icons to be used
   *     for files. Defaults to the value of the "icon.directory" variable.
   *   - description: A description to be displayed instead of the filename.
   *   - attributes: An associative array of attributes to be placed in the a
   *     tag.
   *   - with_size: A Boolean indicating if the file size should be displayed.
   */
  public function preprocessFileLink(array &$variables): void {
    $file = $variables['file'];
    $options = [];

    $url = $this->fileUrlGenerator->generate($file->getFileUri());

    $mime_type = $file->getMimeType();
    $options['attributes']['type'] = $mime_type;

    // Use the description as the link text if available.
    if (empty($variables['description'])) {
      $link_text = $file->getFilename();
    }
    else {
      $link_text = $variables['description'];
      $options['attributes']['title'] = $file->getFilename();
    }

    // Classes to add to the file field for icons.
    $classes = [
      'file',
      // Add a specific class for each and every mime type.
      'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
      // Add a more general class for groups of well known MIME types.
      'file--' . IconMimeTypes::getIconClass($mime_type),
    ];

    // Set file classes to the options array.
    $variables['attributes'] = new Attribute($variables['attributes']);
    $variables['attributes']->addClass($classes);
    $variables['file_size'] = ($variables['with_size'] ?? TRUE) && $file->getSize() !== NULL ? ByteSizeMarkup::create($file->getSize()) : '';

    $variables['link'] = (new Link($link_text, $url->mergeOptions($options)))->toRenderable();
  }

  /**
   * Prepares variables for file upload help text templates.
   *
   * Default template: file-upload-help.html.twig.
   *
   * @param array<string,mixed> $variables
   *   An associative array containing:
   *   - description: The normal description for this field, specified by the
   *     user.
   *   - upload_validators: An array of upload validators as used in
   *     $element['#upload_validators'].
   */
  public function preprocessFileUploadHelp(array &$variables): void {
    $description = $variables['description'];
    $upload_validators = $variables['upload_validators'];
    $cardinality = $variables['cardinality'];

    $descriptions = [];

    if (!empty($description)) {
      $descriptions[] = FieldFilteredMarkup::create($description);
    }
    if (isset($cardinality)) {
      if ($cardinality == -1) {
        $descriptions[] = $this->t('Unlimited number of files can be uploaded to this field.');
      }
      else {
        $descriptions[] = $this->formatPlural($cardinality, 'One file only.', 'Maximum @count files.');
      }
    }

    if (isset($upload_validators['FileSizeLimit']) && $upload_validators['FileSizeLimit']['fileLimit'] > 0) {
      $descriptions[] = $this->t('@size limit.', ['@size' => ByteSizeMarkup::create($upload_validators['FileSizeLimit']['fileLimit'])]);
    }

    if (isset($upload_validators['FileExtension'])) {
      $descriptions[] = $this->t('Allowed types: @extensions.', ['@extensions' => $upload_validators['FileExtension']['extensions']]);
    }

    if (isset($upload_validators['FileImageDimensions'])) {
      $max = $upload_validators['FileImageDimensions']['maxDimensions'];
      $min = $upload_validators['FileImageDimensions']['minDimensions'];
      if ($min && $max && $min == $max) {
        $descriptions[] = $this->t('Images must be exactly <strong>@size</strong> pixels.', ['@size' => $max]);
      }
      elseif ($min && $max) {
        $descriptions[] = $this->t('Images must be larger than <strong>@min</strong> pixels. Images larger than <strong>@max</strong> pixels will be resized.', [
          '@min' => $min,
          '@max' => $max,
        ]);
      }
      elseif ($min) {
        $descriptions[] = $this->t('Images must be larger than <strong>@min</strong> pixels.', ['@min' => $min]);
      }
      elseif ($max) {
        $descriptions[] = $this->t('Images larger than <strong>@max</strong> pixels will be resized.', ['@max' => $max]);
      }
    }

    $variables['descriptions'] = $descriptions;
  }

  /**
   * Prepares variables for file form widget templates.
   *
   * Default template: file-managed-file.html.twig.
   *
   * @param array<string,array> $variables
   *   An associative array containing:
   *   - element: A render element representing the file.
   */
  public function preprocessManagedFile(array &$variables): void {
    $element = $variables['element'];

    $variables['attributes'] = [];
    if (isset($element['#id'])) {
      $variables['attributes']['id'] = $element['#id'];
    }
    if (!empty($element['#attributes']['class'])) {
      $variables['attributes']['class'] = (array) $element['#attributes']['class'];
    }
  }

  /**
   * Prepares variables for multi file form widget templates.
   *
   * Default template: file-widget-multiple.html.twig.
   *
   * @param array<string,mixed> $variables
   *   An associative array containing:
   *   - element: A render element representing the widgets.
   */
  public function preprocessFileWidgetMultiple(array &$variables): void {
    $element = $variables['element'];
    // Special ID and classes for draggable tables.
    $weight_class = $element['#id'] . '-weight';
    $table_id = $element['#id'] . '-table';

    // Build up a table of applicable fields.
    $headers = [];
    $headers[] = $this->t('File information');
    if ($element['#display_field']) {
      $headers[] = [
        'data' => $this->t('Display'),
        'class' => ['checkbox'],
      ];
    }
    $headers[] = $this->t('Weight');
    $headers[] = $this->t('Operations');

    // Get our list of widgets in order (needed when the form comes back after
    // preview or failed validation).
    $widgets = [];
    foreach (Element::children($element) as $key) {
      $widgets[] = &$element[$key];
    }
    usort($widgets, function ($a, $b) {
      // Sorts using ['_weight']['#value'].
      $a_weight = (is_array($a) && isset($a['_weight']['#value']) ? $a['_weight']['#value'] : 0);
      $b_weight = (is_array($b) && isset($b['_weight']['#value']) ? $b['_weight']['#value'] : 0);
      return $a_weight - $b_weight;
    });

    $rows = [];
    foreach ($widgets as &$widget) {
      // Save the uploading row for last.
      if (empty($widget['#files'])) {
        $widget['#title'] = $element['#file_upload_title'];
        $widget['#description'] = $this->renderer->renderInIsolation($element['#file_upload_description']);
        continue;
      }

      // Delay rendering of the buttons, so that they can be rendered later in
      // the "operations" column.
      $operations_elements = [];
      foreach (Element::children($widget) as $key) {
        if (isset($widget[$key]['#type']) && $widget[$key]['#type'] == 'submit') {
          hide($widget[$key]);
          $operations_elements[] = &$widget[$key];
        }
      }

      // Delay rendering of the "Display" option and the weight selector, so
      // that each can be rendered later in its own column.
      if ($element['#display_field']) {
        hide($widget['display']);
      }
      hide($widget['_weight']);
      $widget['_weight']['#attributes']['class'] = [$weight_class];

      // Render everything else together in a column, without the normal
      // wrappers.
      $row = [];
      $widget['#theme_wrappers'] = [];
      $row[] = $this->renderer->render($widget);

      // Arrange the row with the rest of the rendered columns.
      if ($element['#display_field']) {
        unset($widget['display']['#title']);
        $row[] = [
          'data' => $widget['display'],
          'class' => ['checkbox'],
        ];
      }
      $row[] = [
        'data' => $widget['_weight'],
      ];

      // Show the buttons that had previously been marked as hidden in this
      // preprocess function. We use show() to undo the earlier hide().
      foreach (Element::children($operations_elements) as $key) {
        show($operations_elements[$key]);
      }
      $row[] = [
        'data' => $operations_elements,
      ];
      $rows[] = [
        'data' => $row,
        'class' => isset($widget['#attributes']['class']) ? array_merge($widget['#attributes']['class'], ['draggable']) : ['draggable'],
      ];
    }

    $variables['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'id' => $table_id,
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $weight_class,
        ],
      ],
      '#access' => !empty($rows),
    ];

    $variables['element'] = $element;
  }

}
