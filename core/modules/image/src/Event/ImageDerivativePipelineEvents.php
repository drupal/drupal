<?php

namespace Drupal\image\Event;

/**
 * Defines events for the image derivative pipeline.
 */
final class ImageDerivativePipelineEvents {

  /**
   * Name of the event fired to determine the format of the source image.
   *
   * @Event
   *
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveSourceImageFormat()
   *
   * @var string
   */
  const RESOLVE_SOURCE_IMAGE_FORMAT = 'image.pipeline.derivative.resolve_source_image_format';

  /**
   * Name of the event fired to determine if a source image can be derived.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::isSourceImageProcessable()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveSourceImageProcessable()
   *
   * @var string
   */
  const RESOLVE_SOURCE_IMAGE_PROCESSABLE = 'image.pipeline.derivative.resolve_source_image_processable';

  /**
   * Name of the event fired to determine the format of the derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageFileExtension()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveDerivativeImageFormat()
   *
   * @var string
   */
  const RESOLVE_DERIVATIVE_IMAGE_FORMAT = 'image.pipeline.derivative.resolve_derivative_image_format';

  /**
   * Name of the event fired to determine the format of the derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageWidth()
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageHeight()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveDerivativeImageDimensions()
   *
   * @var string
   */
  const RESOLVE_DERIVATIVE_IMAGE_DIMENSIONS = 'image.pipeline.derivative.resolve_derivative_image_dimensions';

  /**
   * Name of the event fired to determine the URI of the derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageUri()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveDerivativeImageUri()
   *
   * @var string
   */
  const RESOLVE_DERIVATIVE_IMAGE_URI = 'image.pipeline.derivative.resolve_derivative_image_uri';

  /**
   * Name of the event fired to produce a protection for the derivative URL.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageUrlSecurityToken()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveDerivativeImageUrlProtection()
   *
   * @var string
   */
  const RESOLVE_DERIVATIVE_IMAGE_URL_PROTECTION = 'image.pipeline.derivative.resolve_derivative_image_url_protection';

  /**
   * Name of the event fired to determine the URL of the derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::getDerivativeImageUrl()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::resolveDerivativeImageUrl()
   *
   * @var string
   */
  const RESOLVE_DERIVATIVE_IMAGE_URL = 'image.pipeline.derivative.resolve_derivative_image_url';

  /**
   * Name of the event fired to load an Image for processing into a derivative.
   *
   * @Event
   *
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::loadSourceImage()
   *
   * @var string
   */
  const LOAD_SOURCE_IMAGE = 'image.pipeline.derivative.load_source_image';

  /**
   * Name of the event fired to build a derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Plugin\ImageProcessPipeline\Derivative::buildDerivativeImage()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::buildDerivativeImage()
   *
   * @var string
   */
  const BUILD_DERIVATIVE_IMAGE = 'image.pipeline.derivative.build_derivative_image';

  /**
   * Name of the event fired to apply an image style to the image object.
   *
   * @Event
   *
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::applyImageStyle()
   *
   * @var string
   */
  const APPLY_IMAGE_STYLE = 'image.pipeline.derivative.apply_image_style';

  /**
   * Name of the event fired to apply a single image effect to the image object.
   *
   * @Event
   *
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::applyImageEffect()
   *
   * @var string
   */
  const APPLY_IMAGE_EFFECT = 'image.pipeline.derivative.apply_image_effect';

  /**
   * Name of the event fired to stores the image at the derivative URI.
   *
   * @Event
   *
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::saveDerivativeImage()
   *
   * @var string
   */
  const SAVE_DERIVATIVE_IMAGE = 'image.pipeline.derivative.save_derivative_image';

  /**
   * Name of the event fired to remove a derivative image.
   *
   * @Event
   *
   * @see \Drupal\image\Entity\ImageStyle::flush()
   * @see \Drupal\image\EventSubscriber\ImageDerivativeSubscriber::removeDerivativeImage()
   *
   * @var string
   */
  const REMOVE_DERIVATIVE_IMAGE = 'image.pipeline.derivative.remove_derivative_image';

}
