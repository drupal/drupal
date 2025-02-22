<?php

declare(strict_types=1);

namespace Drupal\navigation\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\navigation\NavigationRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Navigation settings for this site.
 *
 * @internal
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a Navigation SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected FileSystemInterface $fileSystem,
    protected RendererInterface $renderer,
    protected ImageFactory $imageFactory,
    protected ThemeManagerInterface $themeManager,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('file_system'),
      $container->get('renderer'),
      $container->get('image.factory'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'navigation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['navigation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('navigation.settings');
    $form['#attached']['library'][] = 'core/drupal.states';
    $form['logo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Logo options'),
    ];
    $form['logo']['logo_provider'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose logo handling'),
      '#title_display' => 'invisible',
      '#options' => [
        NavigationRenderer::LOGO_PROVIDER_DEFAULT => $this->t('Default logo'),
        NavigationRenderer::LOGO_PROVIDER_HIDE => $this->t('Hide logo'),
        NavigationRenderer::LOGO_PROVIDER_CUSTOM => $this->t('Custom logo'),
      ],
      '#config_target' => 'navigation.settings:logo.provider',
    ];

    $form['logo']['custom'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="logo_provider"]' => ['value' => NavigationRenderer::LOGO_PROVIDER_CUSTOM],
        ],
      ],
    ];

    // If path is a public:// URI, display the path relative to the files
    // directory; stream wrappers are not end-user friendly.
    $original_path = $config->get('logo.path') ?? '';
    $friendly_path = NULL;
    $default_path = $original_path;
    $default = 'logo.png';

    if (StreamWrapperManager::getScheme($original_path) === 'public') {
      $friendly_path = StreamWrapperManager::getTarget($original_path);
      $default_path = $friendly_path;
    }

    // Prepare local file path for description.
    if ($original_path && isset($friendly_path)) {
      $local_file = strtr($original_path, ['public:/' => PublicStream::basePath()]);
    }
    else {
      $local_file = $this->themeManager->getActiveTheme()->getPath() . '/' . $default;
    }

    $description = $this->t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', [
      '@implicit-public-file' => $friendly_path ?? $default,
      '@explicit-file' => StreamWrapperManager::getScheme($original_path) !== FALSE ? $original_path : 'public://' . $default,
      '@local-file' => $local_file,
    ]);

    $allowed = 'png jpg jpeg';
    $max_navigation_allowed = $config->get('logo.max.filesize');
    $max_system_allowed = Environment::getUploadMaxSize();
    $max_allowed = $max_navigation_allowed < $max_system_allowed ? $max_navigation_allowed : $max_system_allowed;
    $upload_validators = [
      'FileExtension' => ['extensions' => $allowed],
      'FileSizeLimit' => ['fileLimit' => $max_allowed],
    ];
    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#description' => $this->t("If you don't have direct file access to the server, use this field to upload your logo. Recommended image dimension %width x %height pixels.", [
        '%width' => $config->get('logo.max.width'),
        '%height' => $config->get('logo.max.height'),
      ]),
      '#upload_validators' => $upload_validators,
      '#cardinality' => 1,
    ];
    $form['logo']['custom']['logo_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to custom logo'),
      '#default_value' => $default_path,
      '#description' => $description,
      '#config_target' => 'navigation.settings:logo.path',
    ];
    $form['logo']['custom']['logo_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload logo image'),
      '#description' => $this->renderer->renderInIsolation($file_upload_help),
      '#upload_validators' => $upload_validators,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // If the upload element is not empty, try to adjust the image dimensions
    // if needed.
    if ($form_state->getValue('logo_path')) {
      $path = $this->validateLogoPath($form_state->getValue('logo_path'));
      if (!$path) {
        $form_state->setErrorByName('logo_path', $this->t('The custom logo path is invalid.'));
      }
    }

    if ($form_state->getValue('logo_provider') !== NavigationRenderer::LOGO_PROVIDER_CUSTOM) {
      $form_state->setValue('logo_upload', '');
      $form_state->setValue('logo_path', '');
    }
    else {
      $file = _file_save_upload_from_form($form['logo']['custom']['logo_upload'], $form_state, 0);
      if ($file) {
        $logo_dimensions = $this->adjustLogoDimensions($file);
        if (!$logo_dimensions) {
          $config = $this->config('navigation.settings');
          $width = $config->get('logo.width');
          $height = $config->get('logo.height');
          $form_state->setErrorByName('logo_upload', $this->t('Image dimensions are bigger than the expected %widthx%height pixels and cannot be used as the navigation logo.',
            [
              '%width' => $width,
              '%height' => $height,
            ]));
        }
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('logo_upload', $file);
        $form_state->setValue('logo_path', $file->getFileUri());
        $form_state->setValue('logo_dimensions', $logo_dimensions);
      }

      if (empty($form_state->getValue('logo_path'))) {
        $form_state->setErrorByName('logo_path', 'An image file is required with the current logo handling option.');
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // If the user uploaded a new logo, save it to a permanent location
    // and use it in place of the default navigation-provided file.
    $default_scheme = $this->config('system.file')->get('default_scheme');
    $values = $form_state->getValues();
    try {
      if (!empty($values['logo_upload'])) {
        $filename = $this->fileSystem->copy($values['logo_upload']->getFileUri(), $default_scheme . '://');
        $values['logo_path'] = $filename;
        if ($values['logo_dimensions']['resize']) {
          $config = $this->config('navigation.settings');
          $this->messenger()->addStatus($this->t('The image was resized to fit within the navigation logo expected dimensions of %widthx%height pixels. The new dimensions of the resized image are %new_widthx%new_height pixels.',
            [
              '%width' => $config->get('logo.max.width'),
              '%height' => $config->get('logo.max.height'),
              '%new_width' => $values['logo_dimensions']['width'],
              '%new_height' => $values['logo_dimensions']['height'],
            ]));
        }

      }
    }
    catch (FileException) {
      $this->messenger()->addError($this->t('The file %file could not be copied to the permanent destination. Contact the site administrator if the problem persists.', ['%file' => $values['logo_upload']->getFilename()]));
      return;
    }

    // If the user entered a path relative to the system files directory for
    // the logo, store a public:// URI so the theme system can handle it.
    if (!empty($values['logo_path'])) {
      $form_state->setValue('logo_path', $this->validateLogoPath($values['logo_path']));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Adjusts the custom logo dimensions according to navigation settings.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity that contains the image.
   *
   * @return array|null
   *   Array containing the logo dimensions properly adjusted. NULL if fails.
   */
  protected function adjustLogoDimensions(File $file): ?array {
    $config = $this->config('navigation.settings');
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      return NULL;
    }

    $width = $config->get('logo.max.width');
    $height = $config->get('logo.max.height');

    if ($image->getWidth() <= $width && $image->getHeight() <= $height) {
      return [
        'width' => $width,
        'height' => $width,
        'resize' => FALSE,
      ];
    }

    if ($image->scale($width, $height) && $image->save()) {
      return [
        'width' => $image->getWidth(),
        'height' => $image->getHeight(),
        'resize' => TRUE,
      ];
    }

    return NULL;
  }

  /**
   * Helper function for the navigation settings form.
   *
   * Attempts to validate normal system paths, paths relative to the public
   * files directory, or stream wrapper URIs. If the given path is any of the
   * above, returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return string|null
   *   A valid path that can be displayed through the theme system, or NULL if
   *   the path could not be validated.
   */
  protected function validateLogoPath(string $path): ?string {
    // Absolute local file paths are invalid.
    if ($this->fileSystem->realpath($path) == $path) {
      return NULL;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (StreamWrapperManager::getScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return NULL;
  }

}
