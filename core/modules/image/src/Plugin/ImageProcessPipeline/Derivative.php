<?php

namespace Drupal\image\Plugin\ImageProcessPipeline;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Url;
use Drupal\image\Event\ImageDerivativePipelineEvents;
use Drupal\image\ImageProcessException;
use Drupal\image\ImageProcessPipelineInterface;
use Drupal\image\ImageStyleInterface;

/**
 * ImageProcessPipeline to produce image derivatives through image styles.
 *
 * @ImageProcessPipeline(
 *   id = "derivative",
 *   description = @Translation("Processes source images through image style configuration to produce image derivatives."),
 * )
 */
class Derivative extends ImageProcessPipelineBase {

  /**
   * Sets the 'imageToolkitId' variable.
   *
   * @param string $toolkit_id
   *   The id of the image toolkit to use to produce the derivative image.
   *
   * @return $this
   */
  public function setImageToolkitId(string $toolkit_id): self {
    $this->setVariable('imageToolkitId', $toolkit_id);
    return $this;
  }

  /**
   * Sets the 'sourceImageUri' variable.
   *
   * @param string $uri
   *   The URI of the source image file.
   *
   * @return $this
   */
  public function setSourceImageUri(string $uri): self {
    $this->setVariable('sourceImageUri', $uri);
    return $this;
  }

  /**
   * Sets the 'sourceImageFileExtension' variable.
   *
   * Normally this method should not be called, the pipeline will determine the
   * image file extension based on the source URI. This method will override
   * that.
   *
   * @param string $extension
   *   An image file extension.
   *
   * @return $this
   */
  public function setSourceImageFileExtension(string $extension): self {
    $this->setVariable('sourceImageFileExtension', $extension);
    return $this;
  }

  /**
   * Sets the 'imageStyle' variable.
   *
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The ImageStyle config entity to use for derivative creation.
   *
   * @return $this
   */
  public function setImageStyle(ImageStyleInterface $image_style): self {
    $this->setVariable('imageStyle', $image_style);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setImage(ImageInterface $image): ImageProcessPipelineInterface {
    if (!$this->hasVariable('sourceImageUri')) {
      $this->setVariable('sourceImageUri', NULL);
    }
    return parent::setImage($image);
  }

  /**
   * Sets the 'sourceImageWidth' and 'sourceImageHeight' variables.
   *
   * @param int|null $width
   *   (Optional) Integer with the starting image width.
   * @param int|null $height
   *   (Optional) Integer with the starting image height.
   *
   * @return $this
   */
  public function setSourceImageDimensions(?int $width, ?int $height): self {
    $this->setVariable('sourceImageWidth', $width);
    $this->setVariable('sourceImageHeight', $height);
    return $this;
  }

  /**
   * Sets the 'derivativeImageUri' variable.
   *
   * Normally this method should not be called, the pipeline will determine the
   * derivative URI based on the source URI. This method will override that.
   *
   * @param string $uri
   *   Derivative image file URI.
   *
   * @return $this
   */
  public function setDerivativeImageUri(string $uri): self {
    $this->setVariable('derivativeImageUri', $uri);
    return $this;
  }

  /**
   * Sets the 'setCleanUrl' variable.
   *
   * @param bool|null $clean_url
   *   (Optional) Whether clean URLs are in use.
   *
   * @return $this
   */
  public function setCleanUrl(?bool $clean_url): self {
    $this->setVariable('setCleanUrl', $clean_url);
    return $this;
  }

  /**
   * Determines if the source image at URI can be derived.
   *
   * Takes the source URI and the image style to determine if the image file
   * can be loaded, transformed and saved as a derivative image.
   *
   * @return bool
   *   TRUE if the image is supported, FALSE otherwise.
   */
  public function isSourceImageProcessable(): bool {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_SOURCE_IMAGE_PROCESSABLE);
    return $this->getVariable('isSourceImageProcessable');
  }

  /**
   * Determines the extension of the derivative image.
   *
   * @return string
   *   The extension the derivative image will have, given the extension of the
   *   original.
   */
  public function getDerivativeImageFileExtension(): string {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_FORMAT);
    return $this->getVariable('derivativeImageFileExtension');
  }

  /**
   * Determines the width of the derivative image.
   *
   * @return int|null
   *   The width of the derivative image, or NULL if it cannot be calculated.
   */
  public function getDerivativeImageWidth(): ?int {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_DIMENSIONS);
    return $this->getVariable('derivativeImageWidth');
  }

  /**
   * Determines the height of the derivative image.
   *
   * @return int|null
   *   The height of the derivative image, or NULL if it cannot be calculated.
   */
  public function getDerivativeImageHeight(): ?int {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_DIMENSIONS);
    return $this->getVariable('derivativeImageHeight');
  }

  /**
   * Returns the URI of the derivative image file.
   *
   * Takes the source URI and the image style to determine the derivative URI.
   * The path returned by this function may not exist. The default generation
   * method only creates images when they are requested by a user's browser.
   * Plugins may implement this method to decide where to place derivatives.
   *
   * @return string
   *   The URI to the image derivative for this style.
   */
  public function getDerivativeImageUri(): string {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_URI);
    return $this->getVariable('derivativeImageUri');
  }

  /**
   * Returns the URL of the derivative image file.
   *
   * Takes the source URI and the image style to determine the derivative URL.
   *
   * @return \Drupal\Core\Url
   *   The absolute URL where a style image can be downloaded, suitable for use
   *   in an <img> tag.
   *
   * @see \Drupal\image\Controller\ImageStyleDownloadController::deliver()
   * @see file_url_transform_relative()
   */
  public function getDerivativeImageUrl(): Url {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_URL);
    return $this->getVariable('derivativeImageUrl');
  }

  /**
   * Returns a token to protect an image style derivative.
   *
   * This prevents unauthorized generation of an image style derivative,
   * which can be costly both in CPU time and disk space.
   *
   * @return string
   *   An eight-character token which can be used to protect image style
   *   derivatives against denial-of-service attacks.
   */
  public function getDerivativeImageUrlSecurityToken(): ?string {
    $this->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_URL_PROTECTION);
    return $this->hasVariable('derivativeImageUrlProtection') ? $this->getVariable('derivativeImageUrlProtection')[IMAGE_DERIVATIVE_TOKEN] : NULL;
  }

  /**
   * Transform an image based on the image style settings.
   *
   * Generates an image derivative applying all image effects. Takes the source
   * URI or an ImageInterface object and the image style to process the image.
   *
   * @return bool
   *   TRUE if the image was transformed, or FALSE in case of failure.
   */
  public function buildDerivativeImage(): bool {
    try {
      $this->dispatch(ImageDerivativePipelineEvents::BUILD_DERIVATIVE_IMAGE);
      return TRUE;
    }
    catch (ImageProcessException $e) {
      return FALSE;
    }
  }

}
