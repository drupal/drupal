<?php

declare(strict_types=1);

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Attribute\ImageEffect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts an image resource to AVIF, with fallback.
 */
#[ImageEffect(
  id: "image_convert_avif",
  label: new TranslatableMarkup("Convert to AVIF"),
  description: new TranslatableMarkup("Converts an image to AVIF, with a fallback if AVIF is not supported."),
)]
class AvifImageEffect extends ConvertImageEffect {

  /**
   * The image toolkit manager.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitManager
   */
  protected ImageToolkitManager $imageToolkitManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->imageToolkitManager = $container->get(ImageToolkitManager::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // If avif is not supported fallback to the parent.
    if (!$this->isAvifSupported()) {
      return parent::applyEffect($image);
    }

    if (!$image->convert('avif')) {
      $this->logger->error('Image convert failed using the %toolkit toolkit on %path (%mimetype)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType()]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    return $this->isAvifSupported() ? 'avif' : $this->configuration['extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['extension']['#options']['avif']);
    $form['extension']['#title'] = $this->t('Fallback format');
    $form['extension']['#description'] = $this->t('Format to use if AVIF is not available.');
    return $form;
  }

  /**
   * Is AVIF supported by the image toolkit.
   */
  protected function isAvifSupported(): bool {
    return in_array('avif', $this->imageToolkitManager->getDefaultToolkit()->getSupportedExtensions());
  }

}
