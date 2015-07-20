<?php

/**
 * @file
 * Contains \Drupal\quickedit\QuickEditController.
 */

namespace Drupal\quickedit;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\quickedit\Ajax\FieldFormCommand;
use Drupal\quickedit\Ajax\FieldFormSavedCommand;
use Drupal\quickedit\Ajax\FieldFormValidationErrorsCommand;
use Drupal\quickedit\Ajax\EntitySavedCommand;

/**
 * Returns responses for Quick Edit module routes.
 */
class QuickEditController extends ControllerBase {

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The in-place editing metadata generator.
   *
   * @var \Drupal\quickedit\MetadataGeneratorInterface
   */
  protected $metadataGenerator;

  /**
   * The in-place editor selector.
   *
   * @var \Drupal\quickedit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new QuickEditController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The PrivateTempStore factory.
   * @param \Drupal\quickedit\MetadataGeneratorInterface $metadata_generator
   *   The in-place editing metadata generator.
   * @param \Drupal\quickedit\EditorSelectorInterface $editor_selector
   *   The in-place editor selector.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, MetadataGeneratorInterface $metadata_generator, EditorSelectorInterface $editor_selector, RendererInterface $renderer) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->metadataGenerator = $metadata_generator;
    $this->editorSelector = $editor_selector;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('quickedit.metadata.generator'),
      $container->get('quickedit.editor.selector'),
      $container->get('renderer')
    );
  }

  /**
   * Returns the metadata for a set of fields.
   *
   * Given a list of field quick edit IDs as POST parameters, run access checks
   * on the entity and field level to determine whether the current user may
   * edit them. Also retrieves other metadata.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function metadata(Request $request) {
    $fields = $request->request->get('fields');
    if (!isset($fields)) {
      throw new NotFoundHttpException();
    }
    $entities = $request->request->get('entities');

    $metadata = array();
    foreach ($fields as $field) {
      list($entity_type, $entity_id, $field_name, $langcode, $view_mode) = explode('/', $field);

      // Load the entity.
      if (!$entity_type || !$this->entityManager()->getDefinition($entity_type)) {
        throw new NotFoundHttpException();
      }
      $entity = $this->entityManager()->getStorage($entity_type)->load($entity_id);
      if (!$entity) {
        throw new NotFoundHttpException();
      }

      // Validate the field name and language.
      if (!$field_name || !$entity->hasField($field_name)) {
        throw new NotFoundHttpException();
      }
      if (!$langcode || !$entity->hasTranslation($langcode)) {
        throw new NotFoundHttpException();
      }

      $entity = $entity->getTranslation($langcode);

      // If the entity information for this field is requested, include it.
      $entity_id = $entity->getEntityTypeId() . '/' . $entity_id;
      if (is_array($entities) && in_array($entity_id, $entities) && !isset($metadata[$entity_id])) {
        $metadata[$entity_id] = $this->metadataGenerator->generateEntityMetadata($entity);
      }

      $metadata[$field] = $this->metadataGenerator->generateFieldMetadata($entity->get($field_name), $view_mode);
    }

    return new JsonResponse($metadata);
  }

  /**
   * Returns AJAX commands to load in-place editors' attachments.
   *
   * Given a list of in-place editor IDs as POST parameters, render AJAX
   * commands to load those in-place editors.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function attachments(Request $request) {
    $response = new AjaxResponse();
    $editors = $request->request->get('editors');
    if (!isset($editors)) {
      throw new NotFoundHttpException();
    }

    $response->setAttachments($this->editorSelector->getEditorAttachments($editors));

    return $response;
  }

  /**
   * Returns a single field edit form as an Ajax response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param string $field_name
   *   The name of the field that is being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @param string $view_mode_id
   *   The view mode the field should be rerendered in.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function fieldForm(EntityInterface $entity, $field_name, $langcode, $view_mode_id, Request $request) {
    $response = new AjaxResponse();

    // Replace entity with PrivateTempStore copy if available and not resetting,
    // init PrivateTempStore copy otherwise.
    $tempstore_entity = $this->tempStoreFactory->get('quickedit')->get($entity->uuid());
    if ($tempstore_entity && $request->request->get('reset') !== 'true') {
      $entity = $tempstore_entity;
    }
    else {
      $this->tempStoreFactory->get('quickedit')->set($entity->uuid(), $entity);
    }

    $form_state = (new FormState())
      ->set('langcode', $langcode)
      ->disableRedirect()
      ->addBuildInfo('args', [$entity, $field_name]);
    $form = $this->formBuilder()->buildForm('Drupal\quickedit\Form\QuickEditFieldForm', $form_state);

    if ($form_state->isExecuted()) {
      // The form submission saved the entity in PrivateTempStore. Return the
      // updated view of the field from the PrivateTempStore copy.
      $entity = $this->tempStoreFactory->get('quickedit')->get($entity->uuid());

      // Closure to render the field given a view mode.
      $render_field_in_view_mode = function ($view_mode_id) use ($entity, $field_name, $langcode) {
        return $this->renderField($entity, $field_name, $langcode, $view_mode_id);
      };

      // Re-render the updated field.
      $output = $render_field_in_view_mode($view_mode_id);

      // Re-render the updated field for other view modes (i.e. for other
      // instances of the same logical field on the user's page).
      $other_view_mode_ids = $request->request->get('other_view_modes') ?: array();
      $other_view_modes = array_map($render_field_in_view_mode, array_combine($other_view_mode_ids, $other_view_mode_ids));

      $response->addCommand(new FieldFormSavedCommand($output, $other_view_modes));
    }
    else {
      $output = (string) $this->renderer->renderRoot($form);
      // When working with a hidden form, we don't want its CSS/JS to be loaded.
      if ($request->request->get('nocssjs') !== 'true') {
        $response->setAttachments($form['#attached']);
      }
      $response->addCommand(new FieldFormCommand($output));

      $errors = $form_state->getErrors();
      if (count($errors)) {
        $status_messages = array(
          '#type' => 'status_messages'
        );
        $response->addCommand(new FieldFormValidationErrorsCommand((string) $this->renderer->renderRoot($status_messages)));
      }
    }

    return $response;
  }

  /**
   * Renders a field.
   *
   * If the view mode ID is not an Entity Display view mode ID, then the field
   * was rendered using a custom render pipeline (not the Entity/Field API
   * render pipeline).
   *
   * An example could be Views' render pipeline. In that case, the view mode ID
   * would probably contain the View's ID, display and the row index.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param string $field_name
   *   The name of the field that is being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @param string $view_mode_id
   *   The view mode the field should be rerendered in. Either an Entity Display
   *   view mode ID, or a custom one. See hook_quickedit_render_field().
   *
   * @return string
   *   Rendered HTML.
   *
   * @see hook_quickedit_render_field()
   */
  protected function renderField(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $entity_view_mode_ids = array_keys($this->entityManager()->getViewModes($entity->getEntityTypeId()));
    if (in_array($view_mode_id, $entity_view_mode_ids)) {
      $entity = \Drupal::entityManager()->getTranslationFromContext($entity, $langcode);
      $output = $entity->get($field_name)->view($view_mode_id);
    }
    else {
      // Each part of a custom (non-Entity Display) view mode ID is separated
      // by a dash; the first part must be the module name.
      $mode_id_parts = explode('-', $view_mode_id, 2);
      $module = reset($mode_id_parts);
      $args = array($entity, $field_name, $view_mode_id, $langcode);
      $output = $this->moduleHandler()->invoke($module, 'quickedit_render_field', $args);
    }

    return (string) $this->renderer->renderRoot($output);
  }

  /**
   * Saves an entity into the database, from PrivateTempStore.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function entitySave(EntityInterface $entity) {
    // Take the entity from PrivateTempStore and save in entity storage.
    // fieldForm() ensures that the PrivateTempStore copy exists ahead.
    $tempstore = $this->tempStoreFactory->get('quickedit');
    $tempstore->get($entity->uuid())->save();
    $tempstore->delete($entity->uuid());

    // Return information about the entity that allows a front end application
    // to identify it.
    $output = array(
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id()
    );

    // Respond to client that the entity was saved properly.
    $response = new AjaxResponse();
    $response->addCommand(new EntitySavedCommand($output));
    return $response;
  }

}
