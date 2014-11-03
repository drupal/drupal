<?php

/**
 * @file
 * Contains \Drupal\entity_reference/EntityReferenceController.
 */

namespace Drupal\entity_reference;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines route controller for entity reference.
 */
class EntityReferenceController extends ControllerBase {

  /**
   * The autocomplete helper for entity references.
   *
   * @var \Drupal\entity_reference\EntityReferenceAutocomplete
   */
  protected $entityReferenceAutocomplete;

  /**
   * Constructs a EntityReferenceController object.
   *
   * @param \Drupal\entity_reference\EntityReferenceAutocomplete $entity_reference_autocompletion
   *   The autocompletion helper for entity references.
   */
  public function __construct(EntityReferenceAutocomplete $entity_reference_autocompletion) {
    $this->entityReferenceAutocomplete = $entity_reference_autocompletion;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_reference.autocomplete'),
      $container->get('entity.manager')
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
   *   Throws access denied when either the field storage or field does not
   *   exists or the user does not have access to edit the field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched labels as json.
   */
  public function handleAutocomplete(Request $request, $type, $field_name, $entity_type, $bundle_name, $entity_id) {
    $definitions = $this->entityManager()->getFieldDefinitions($entity_type, $bundle_name);

    if (!isset($definitions[$field_name])) {
      throw new AccessDeniedHttpException();
    }

    $field_definition = $definitions[$field_name];
    $access_control_handler = $this->entityManager()->getAccessControlHandler($entity_type);
    if ($field_definition->getType() != 'entity_reference' || !$access_control_handler->fieldAccess('edit', $field_definition)) {
      throw new AccessDeniedHttpException();
    }

    // Get the typed string, if exists from the URL.
    $items_typed = $request->query->get('q');
    $items_typed = Tags::explode($items_typed);
    $last_item = Unicode::strtolower(array_pop($items_typed));

    $prefix = '';
    // The user entered a comma-separated list of entity labels, so we generate
    // a prefix.
    if ($type == 'tags' && !empty($last_item)) {
      $prefix = count($items_typed) ? Tags::implode($items_typed) . ', ' : '';
    }

    $matches = $this->entityReferenceAutocomplete->getMatches($field_definition, $entity_type, $bundle_name, $entity_id, $prefix, $last_item);

    return new JsonResponse($matches);
  }
}
