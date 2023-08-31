<?php

declare(strict_types=1);

namespace Drupal\field_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for building the field storage form.
 *
 * @internal
 *
 * @todo remove in https://www.drupal.org/project/drupal/issues/3347291.
 */
final class FieldStorageAddController extends ControllerBase {

  /**
   * FieldStorageAddController constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStore $tempStore
   *   The private tempstore.
   */
  public function __construct(protected readonly PrivateTempStore $tempStore) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')->get('field_ui')
    );
  }

  /**
   * Builds the field storage form.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The name of the field to create.
   * @param string $bundle
   *   The bundle where the field is being created.
   *
   * @return array
   *   The field storage form.
   */
  public function storageAddConfigureForm(string $entity_type, string $field_name, string $bundle): array {
    // @see \Drupal\field_ui\Form\FieldStorageAddForm::submitForm
    $temp_storage = $this->tempStore->get($entity_type . ':' . $field_name);
    if (!$temp_storage) {
      throw new NotFoundHttpException();
    }

    return $this->entityFormBuilder()->getForm($temp_storage['field_storage'], 'edit', [
      'entity_type_id' => $entity_type,
      'bundle' => $bundle,
    ]);
  }

}
