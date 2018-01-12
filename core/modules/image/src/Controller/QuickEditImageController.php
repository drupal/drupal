<?php

namespace Drupal\image\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Render\RendererInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for our image routes.
 */
class QuickEditImageController extends ControllerBase {

  /**
   * Stores The Quick Edit tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Constructs a new QuickEditImageController.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(RendererInterface $renderer, ImageFactory $image_factory, PrivateTempStoreFactory $temp_store_factory) {
    $this->renderer = $renderer;
    $this->imageFactory = $image_factory;
    $this->tempStore = $temp_store_factory->get('quickedit');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('image.factory'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Returns JSON representing the new file upload, or validation errors.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which an image field is being rendered.
   * @param string $field_name
   *   The name of the (image) field that is being rendered
   * @param string $langcode
   *   The language code of the field that is being rendered.
   * @param string $view_mode_id
   *   The view mode of the field that is being rendered.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function upload(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $field = $this->getField($entity, $field_name, $langcode);
    $field_validators = $field->getUploadValidators();
    $field_settings = $field->getFieldDefinition()->getSettings();
    $destination = $field->getUploadLocation();

    // Add upload resolution validation.
    if ($field_settings['max_resolution'] || $field_settings['min_resolution']) {
      $field_validators['file_validate_image_resolution'] = [$field_settings['max_resolution'], $field_settings['min_resolution']];
    }

    // Create the destination directory if it does not already exist.
    if (isset($destination) && !file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      return new JsonResponse(['main_error' => $this->t('The destination directory could not be created.'), 'errors' => '']);
    }

    // Attempt to save the image given the field's constraints.
    $result = file_save_upload('image', $field_validators, $destination);
    if (is_array($result) && $result[0]) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $result[0];
      $image = $this->imageFactory->get($file->getFileUri());

      // Set the value in the Entity to the new file.
      /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field_list */
      $value = $entity->$field_name->getValue();
      $value[0]['target_id'] = $file->id();
      $value[0]['width'] = $image->getWidth();
      $value[0]['height'] = $image->getHeight();
      $entity->$field_name->setValue($value);

      // Render the new image using the correct formatter settings.
      $entity_view_mode_ids = array_keys($this->entityManager()->getViewModes($entity->getEntityTypeId()));
      if (in_array($view_mode_id, $entity_view_mode_ids, TRUE)) {
        $output = $entity->$field_name->view($view_mode_id);
      }
      else {
        // Each part of a custom (non-Entity Display) view mode ID is separated
        // by a dash; the first part must be the module name.
        $mode_id_parts = explode('-', $view_mode_id, 2);
        $module = reset($mode_id_parts);
        $args = [$entity, $field_name, $view_mode_id, $langcode];
        $output = $this->moduleHandler()->invoke($module, 'quickedit_render_field', $args);
      }

      // Save the Entity to tempstore.
      $this->tempStore->set($entity->uuid(), $entity);

      $data = [
        'fid' => $file->id(),
        'html' => $this->renderer->renderRoot($output),
      ];
      return new JsonResponse($data);
    }
    else {
      // Return a JSON object containing the errors from Drupal and our
      // "main_error", which is displayed inside the dropzone area.
      $messages = StatusMessages::renderMessages('error');
      return new JsonResponse(['errors' => $this->renderer->render($messages), 'main_error' => $this->t('The image failed validation.')]);
    }
  }

  /**
   * Returns JSON representing an image field's metadata.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which an image field is being rendered.
   * @param string $field_name
   *   The name of the (image) field that is being rendered
   * @param string $langcode
   *   The language code of the field that is being rendered.
   * @param string $view_mode_id
   *   The view mode of the field that is being rendered.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The JSON response.
   */
  public function getInfo(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $field = $this->getField($entity, $field_name, $langcode);
    $settings = $field->getFieldDefinition()->getSettings();
    $info = [
      'alt' => $field->alt,
      'title' => $field->title,
      'alt_field' => $settings['alt_field'],
      'title_field' => $settings['title_field'],
      'alt_field_required' => $settings['alt_field_required'],
      'title_field_required' => $settings['title_field_required'],
    ];
    $response = new CacheableJsonResponse($info);
    $response->addCacheableDependency($entity);
    return $response;
  }

  /**
   * Returns JSON representing the current state of the field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which an image field is being rendered.
   * @param string $field_name
   *   The name of the (image) field that is being rendered
   * @param string $langcode
   *   The language code of the field that is being rendered.
   *
   * @return \Drupal\image\Plugin\Field\FieldType\ImageItem
   *   The field for this request.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Throws an exception if the request is invalid.
   */
  protected function getField(EntityInterface $entity, $field_name, $langcode) {
    // Ensure that this is a valid Entity.
    if (!($entity instanceof ContentEntityInterface)) {
      throw new BadRequestHttpException('Requested Entity is not a Content Entity.');
    }

    // Check that this field exists.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_list */
    $field_list = $entity->getTranslation($langcode)->get($field_name);
    if (!$field_list) {
      throw new BadRequestHttpException('Requested Field does not exist.');
    }

    // If the list is empty, append an empty item to use.
    if ($field_list->isEmpty()) {
      $field = $field_list->appendItem();
    }
    // Otherwise, use the first item.
    else {
      $field = $entity->getTranslation($langcode)->get($field_name)->first();
    }

    // Ensure that the field is the type we expect.
    if (!($field instanceof ImageItem)) {
      throw new BadRequestHttpException('Requested Field is not of type "image".');
    }

    return $field;
  }

}
