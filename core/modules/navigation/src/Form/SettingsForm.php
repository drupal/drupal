<?php

declare(strict_types=1);

namespace Drupal\navigation\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a Navigation SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   The File Usage service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    FileSystemInterface $file_system,
    FileUrlGeneratorInterface $fileUrlGenerator,
    FileUsageInterface $fileUsage,
    RendererInterface $renderer,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->fileUsage = $fileUsage;
    $this->renderer = $renderer;
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
      $container->get('renderer')
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
      '#default_value' => $config->get('logo_provider'),
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
    $current_logo_managed_fid = $config->get('logo_managed');
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
    if ($form_state->getValue('logo_provider') === NavigationRenderer::LOGO_PROVIDER_CUSTOM && empty($logo_managed) === TRUE) {
      $form_state->setErrorByName('logo_managed', 'An image file is required with the current logo handling option.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('navigation.settings');

    // Get the previous config settings.
    $previous_logo_provider = $config->get('logo_provider');
    $logo_managed = $config->get('logo_managed');
    $previous_logo_fid = $logo_managed ? reset($logo_managed) : NULL;

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
      ->set('logo_provider', $form_state->getValue('logo_provider'))
      ->set('logo_managed', $form_state->getValue('logo_managed'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
