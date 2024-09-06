<?php

declare(strict_types=1);

namespace Drupal\navigation\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   The File Usage service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected FileSystemInterface $fileSystem,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected FileUsageInterface $fileUsage,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ImageFactory $imageFactory,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('file_system'),
      $container->get('file_url_generator'),
      $container->get('file.usage'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('image.factory')
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
      '#config_target' => 'navigation.settings:logo_provider',
    ];
    $form['logo']['image'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="logo_provider"]' => ['value' => NavigationRenderer::LOGO_PROVIDER_CUSTOM],
        ],
      ],
    ];
    $allowed = 'png jpg jpeg';
    $current_logo_managed_fid = $config->get('logo_managed') ? [$config->get('logo_managed')] : NULL;
    $max_navigation_allowed = $config->get('logo_max_filesize');
    $max_system_allowed = Environment::getUploadMaxSize();
    $max_allowed = $max_navigation_allowed < $max_system_allowed ? $max_navigation_allowed : $max_system_allowed;
    $upload_validators = [
      'FileExtension' => ['extensions' => $allowed],
      'FileSizeLimit' => ['fileLimit' => $max_allowed],
    ];
    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#description' => $this->t('Recommended image dimension 40 x 40 pixels.'),
      '#upload_validators' => $upload_validators,
      '#cardinality' => 1,
    ];
    $form['logo']['image']['logo_managed'] = [
      '#type' => 'managed_file',
      '#title' => t('Choose custom logo'),
      '#upload_validators' => $upload_validators,
      '#upload_location' => 'public://navigation-logo',
      '#description' => $this->renderer->renderInIsolation($file_upload_help),
      '#default_value' => $current_logo_managed_fid,
      '#multiple' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $logo_managed = $form_state->getValue('logo_managed');
    if ($form_state->getValue('logo_provider') === NavigationRenderer::LOGO_PROVIDER_CUSTOM && empty($logo_managed)) {
      $form_state->setErrorByName('logo_managed', 'An image file is required with the current logo handling option.');
    }

    // If the upload element is not empty and the image is new, try to adjust
    // the image dimensions.
    $this->validateLogoManaged($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('navigation.settings');

    // Get the previous config settings.
    $previous_logo_provider = $config->get('logo_provider');
    $previous_logo_fid = $config->get('logo_managed');

    // Get new values from the form.
    $new_logo_provider = $form_state->getValue('logo_provider');
    $logo = $form_state->getValue('logo_managed');
    $new_logo_fid = !empty($logo) ? reset($logo) : NULL;

    // Pre-load files if any for FileUsageInterface.
    $previous_logo_managed = $previous_logo_fid ? File::load($previous_logo_fid) : NULL;
    $new_logo_managed = $new_logo_fid ? File::load($new_logo_fid) : NULL;

    // Decrement if previous logo_provider was 'custom' and has changed to a
    // different fid and there's a change in the logo fid.
    if ($previous_logo_provider === NavigationRenderer::LOGO_PROVIDER_CUSTOM
      && ($new_logo_provider !== NavigationRenderer::LOGO_PROVIDER_CUSTOM || $previous_logo_fid !== $new_logo_fid)
      && $previous_logo_managed
    ) {
      $this->fileUsage->delete($previous_logo_managed, 'navigation', 'logo', 1);
    }

    // Increment usage if different from the previous one.
    if ($new_logo_managed && $new_logo_fid !== $previous_logo_fid) {
      $new_logo_managed->setPermanent();
      $new_logo_managed->save();
      $this->fileUsage->add($new_logo_managed, 'navigation', 'logo', 1);
    }

    $config
      ->set('logo_managed', $new_logo_fid)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Validate the Logo Managed image element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateLogoManaged(array $form, FormStateInterface $form_state): void {
    $logo_managed = $form_state->getValue('logo_managed');
    $config = $this->config('navigation.settings');
    if (empty($logo_managed)) {
      return;
    }

    $width = $config->get('logo_width');
    $height = $config->get('logo_height');

    // Skip if the fid has not been modified.
    $fid = reset($logo_managed);
    if ($fid == $config->get('logo_managed')) {
      return;
    }

    $file = $this->entityTypeManager->getStorage('file')
      ->load($fid);
    if ($fid && !$this->adjustLogoDimensions($file)) {
      $form_state->setErrorByName('logo_managed', $this->t('Image dimensions are bigger than the expected %widthx%height pixels and cannot be used as the navigation logo.',
        [
          '%width' => $width,
          '%height' => $height,
        ]));
    }
  }

  /**
   * Adjusts the custom logo dimensions according to navigation settings.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity that contains the image.
   *
   * @return bool
   *   TRUE if the logo image dimensions are properly adjusted. FALSE otherwise.
   */
  protected function adjustLogoDimensions(File $file): bool {
    $config = $this->config('navigation.settings');
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      return FALSE;
    }

    $width = $config->get('logo_width');
    $height = $config->get('logo_height');

    if ($image->getWidth() <= $width && $image->getHeight() <= $height) {
      return TRUE;
    }

    if ($image->scale($width, $height) && $image->save()) {
      $this->messenger()->addStatus($this->t('The image was resized to fit within the navigation logo expected dimensions of %widthx%height pixels. The new dimensions of the resized image are %new_widthx%new_height pixels.',
        [
          '%width' => $width,
          '%height' => $height,
          '%new_width' => $image->getWidth(),
          '%new_height' => $image->getHeight(),
        ]));

      return TRUE;
    }

    return FALSE;
  }

}
