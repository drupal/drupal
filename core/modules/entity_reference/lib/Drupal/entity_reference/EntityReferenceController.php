<?php

/**
 * @file
 * Contains \Drupal\entity_reference/EntityReferenceController.
 */

namespace Drupal\entity_reference;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Controller\ControllerInterface;

/**
 * Defines route controller for entity reference.
 */
class EntityReferenceController implements ControllerInterface {

  /**
   * The autocomplete helper for entity references.
   *
   * @var \Drupal\entity_reference\EntityReferenceAutocomplete
   */
  protected $entityReferenceAutocomplete;

  /**
   * Constructs a EntityReferenceController object.
   *
   * @param \Drupal\entity_reference\EntityReferenceAutocomplete $entity_reference_autcompletion
   *   The autocompletion helper for entity references
   */
  public function __construct(EntityReferenceAutocomplete $entity_reference_autcompletion) {
    $this->entityReferenceAutocomplete = $entity_reference_autcompletion;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_reference.autocomplete')
    );
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param Request $request
   *   The request object that contains the typed tags.
   * @param string $type
   *   The widget type (i.e. 'single' or 'tags').
   * @param string $field_name
   *   The name of the entity reference field.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle_name
   *   The bundle name.
   * @param string $entity_id
   *   (optional) The entity ID the entity reference field is attached to.
   *   Defaults to ''.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when either the field or field instance does not
   *   exists or the user does not have access to edit the field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched labels as json.
   */
  public function handleAutocomplete(Request $request, $type, $field_name, $entity_type, $bundle_name, $entity_id) {
    if (!$field = field_info_field($field_name)) {
      throw new AccessDeniedHttpException();
    }

    if (!$instance = field_info_instance($entity_type, $field_name, $bundle_name)) {
      throw new AccessDeniedHttpException();
    }

    if ($field['type'] != 'entity_reference' || !field_access('edit', $field, $entity_type)) {
      throw new AccessDeniedHttpException();
    }

    // Get the typed string, if exists from the URL.
    $items_typed = $request->query->get('q');
    $items_typed = drupal_explode_tags($items_typed);
    $last_item = drupal_strtolower(array_pop($items_typed));

    $prefix = '';
    // The user entered a comma-separated list of entity labels, so we generate
    // a prefix.
    if ($type == 'tags' && !empty($last_item)) {
      $prefix = count($items_typed) ? drupal_implode_tags($items_typed) . ', ' : '';
    }

    $matches = $this->entityReferenceAutocomplete->getMatches($field, $instance, $entity_type, $entity_id, $prefix, $last_item);

    return new JsonResponse($matches);
  }
}
