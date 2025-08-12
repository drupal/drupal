<?php

namespace Drupal\Core\Entity\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Site\Settings;

/**
 * Provides an entity autocomplete form element.
 *
 * The autocomplete form element allows users to select one or multiple
 * entities, which can come from all or specific bundles of an entity type.
 *
 * Properties:
 * - #target_type: (required) The ID of the target entity type.
 * - #tags: (optional) TRUE if the element allows multiple selection. Defaults
 *   to FALSE.
 * - #default_value: (optional) The default entity or an array of default
 *   entities, depending on the value of #tags.
 * - #selection_handler: (optional) The plugin ID of the entity reference
 *   selection handler (a plugin of type EntityReferenceSelection). The default
 *   value is the lowest-weighted plugin that is compatible with #target_type.
 * - #selection_settings: (optional) An array of settings for the selection
 *   handler. Settings for the default selection handler
 *   \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection are:
 *   - target_bundles: Array of bundles to allow (omit to allow all bundles).
 *   - sort: Array with 'field' and 'direction' keys, determining how results
 *     will be sorted. Defaults to unsorted.
 * - #autocreate: (optional) Array of settings used to auto-create entities
 *   that do not exist (omit to not auto-create entities). Elements:
 *   - bundle: (required) Bundle to use for auto-created entities.
 *   - uid: User ID to use as the author of auto-created entities. Defaults to
 *     the current user.
 * - #process_default_value: (optional) Set to FALSE if the #default_value
 *   property is processed and access checked elsewhere (such as by a Field API
 *   widget). Defaults to TRUE.
 * - #validate_reference: (optional) Set to FALSE if validation of the selected
 *   entities is performed elsewhere. Defaults to TRUE.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 *  '#type' => 'entity_autocomplete',
 *  '#target_type' => 'node',
 *  '#tags' => TRUE,
 *  '#default_value' => $node,
 *  '#selection_handler' => 'default',
 *  '#selection_settings' => [
 *    'target_bundles' => ['article', 'page'],
 *   ],
 *  '#autocreate' => [
 *    'bundle' => 'article',
 *    'uid' => <a valid user ID>,
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 */
#[FormElement('entity_autocomplete')]
class EntityAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    // Apply default form element properties.
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = [];
    $info['#tags'] = FALSE;
    $info['#autocreate'] = NULL;
    // This should only be set to FALSE if proper validation by the selection
    // handler is performed at another level on the extracted form values.
    $info['#validate_reference'] = TRUE;
    // IMPORTANT! This should only be set to FALSE if the #default_value
    // property is processed at another level (e.g. by a Field API widget) and
    // its value is properly checked for access.
    $info['#process_default_value'] = TRUE;

    $info['#element_validate'] = [[static::class, 'validateEntityAutocomplete']];
    array_unshift($info['#process'], [static::class, 'processEntityAutocomplete']);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#process_default_value']) {
      if (is_array($element['#default_value']) && $element['#tags'] !== TRUE) {
        throw new \InvalidArgumentException('The #default_value property is an array but the form element does not allow multiple values.');
      }
      elseif (!empty($element['#default_value']) && !is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = [$element['#default_value']];
      }

      if ($element['#default_value']) {
        if (!(reset($element['#default_value']) instanceof EntityInterface)) {
          throw new \InvalidArgumentException('The #default_value property has to be an entity object or an array of entity objects.');
        }

        // Extract the labels from the passed-in entity objects, taking access
        // checks into account.
        return static::getEntityLabels($element['#default_value']);
      }
    }

    // Potentially the #value is set directly, so it contains the 'target_id'
    // array structure instead of a string.
    if ($input !== FALSE && is_array($input)) {
      $entity_ids = array_map(function (array $item) {
        return $item['target_id'];
      }, $input);

      $entities = \Drupal::entityTypeManager()->getStorage($element['#target_type'])->loadMultiple($entity_ids);

      return static::getEntityLabels($entities);
    }
  }

  /**
   * Adds entity autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #target_type: The ID of the target entity type.
   *   - #selection_handler: The plugin ID of the entity reference selection
   *     handler.
   *   - #selection_settings: An array of settings that will be passed to the
   *     selection handler.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #target_type or #autocreate['bundle'] are
   *   missing.
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Provide default values and sanity checks for the #autocreate parameter.
    if ($element['#autocreate']) {
      if (!isset($element['#autocreate']['bundle'])) {
        throw new \InvalidArgumentException("Missing required #autocreate['bundle'] parameter.");
      }
      // Default the autocreate user ID to the current user.
      $element['#autocreate']['uid'] = $element['#autocreate']['uid'] ?? \Drupal::currentUser()->id();
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = $element['#selection_settings'] ?? [];
    // Don't serialize the entity, it will be added explicitly afterwards.
    if (isset($selection_settings['entity']) && ($selection_settings['entity'] instanceof EntityInterface)) {
      $element['#autocomplete_query_parameters']['entity_type'] = $selection_settings['entity']->getEntityTypeId();
      $element['#autocomplete_query_parameters']['entity_id'] = $selection_settings['entity']->id();
      unset($selection_settings['entity']);
    }
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'system.entity_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    ];

    return $element;
  }

  /**
   * Form element validation handler for entity_autocomplete elements.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    // Check the value for emptiness, but allow the use of (string) "0".
    if (!empty($element['#value']) || (is_string($element['#value']) && strlen($element['#value']))) {
      $options = $element['#selection_settings'] + [
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
      ];
      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      $autocreate = (bool) $element['#autocreate'] && $handler instanceof SelectionWithAutocreateInterface;

      // GET forms might pass the validated data around on the next request, in
      // which case it will already be in the expected format.
      if (is_array($element['#value'])) {
        $value = $element['#value'];
      }
      else {
        $input_values = $element['#tags'] ? Tags::explode($element['#value']) : [$element['#value']];

        foreach ($input_values as $input) {
          $match = static::extractEntityIdFromAutocompleteInput($input);
          if ($match === NULL) {
            // Try to get a match from the input string when the user didn't use
            // the autocomplete but filled in a value manually.
            $match = static::matchEntityByTitle($handler, $input, $element, $form_state, !$autocreate);
          }

          if ($match !== NULL) {
            $value[] = [
              'target_id' => $match,
            ];
          }
          elseif ($autocreate) {
            /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $handler */
            // Auto-create item. See an example of how this is handled in
            // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
            $value[] = [
              'entity' => $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid']),
            ];
          }
        }
      }

      // Check that the referenced entities are valid, if needed.
      if ($element['#validate_reference'] && !empty($value)) {
        // Validate existing entities.
        $ids = array_reduce($value, function ($return, $item) {
          if (isset($item['target_id'])) {
            $return[] = $item['target_id'];
          }
          return $return;
        });

        if ($ids) {
          $valid_ids = $handler->validateReferenceableEntities($ids);
          if ($invalid_ids = array_diff($ids, $valid_ids)) {
            foreach ($invalid_ids as $invalid_id) {
              $form_state->setError($element, t('The referenced entity (%type: %id) does not exist.', [
                '%type' => $element['#target_type'],
                '%id' => $invalid_id,
              ]));
            }
          }
        }

        // Validate newly created entities.
        $new_entities = array_reduce($value, function ($return, $item) {
          if (isset($item['entity'])) {
            $return[] = $item['entity'];
          }
          return $return;
        });

        if ($new_entities) {
          if ($autocreate) {
            $valid_new_entities = $handler->validateReferenceableNewEntities($new_entities);
            $invalid_new_entities = array_diff_key($new_entities, $valid_new_entities);
          }
          else {
            // If the selection handler does not support referencing newly
            // created entities, all of them should be invalidated.
            $invalid_new_entities = $new_entities;
          }

          foreach ($invalid_new_entities as $entity) {
            /** @var \Drupal\Core\Entity\EntityInterface $entity */
            $form_state->setError($element, t('This entity (%type: %label) cannot be referenced.', [
              '%type' => $element['#target_type'],
              '%label' => $entity->label(),
            ]));
          }
        }
      }

      // Use only the last value if the form element does not support multiple
      // matches (tags).
      if (!$element['#tags'] && !empty($value)) {
        $last_value = $value[count($value) - 1];
        $value = $last_value['target_id'] ?? $last_value;
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * Finds an entity from an autocomplete input without an explicit ID.
   *
   * The method will return an entity ID if one single entity unambiguously
   * matches the incoming input, and assign form errors otherwise.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler
   *   Entity reference selection plugin.
   * @param string $input
   *   Single string from autocomplete element.
   * @param array $element
   *   The form element to set a form error.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param bool $strict
   *   Whether to trigger a form error if an element from $input (eg. an entity)
   *   is not found.
   *
   * @return int|null
   *   Value of a matching entity ID, or NULL if none.
   */
  protected static function matchEntityByTitle(SelectionInterface $handler, $input, array &$element, FormStateInterface $form_state, $strict) {
    $entities_by_bundle = $handler->getReferenceableEntities($input, '=', 6);
    $entities = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
      return $flattened + $bundle_entities;
    }, []);
    $params = [
      '%value' => $input,
      '@value' => $input,
      '@entity_type_plural' => \Drupal::entityTypeManager()->getDefinition($element['#target_type'])->getPluralLabel(),
    ];
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        $form_state->setError($element, t('There are no @entity_type_plural matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      // Error if there are more than 5 matching entities.
      $form_state->setError($element, t('Many @entity_type_plural are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params));
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = [];
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      $form_state->setError($element, t('Multiple @entity_type_plural match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', ['%multiple' => strip_tags(implode('", "', $multiples))] + $params));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view label' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function getEntityLabels(array $entities) {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $entity_labels = [];
    foreach ($entities as $entity) {
      // Set the entity in the correct language for display.
      $entity = $entity_repository->getTranslationFromContext($entity);

      // Use the special view label, since some entities allow the label to be
      // viewed, even if the entity is not allowed to be viewed.
      $label = ($entity->access('view label')) ? $entity->label() : t('- Restricted access -');

      // Take into account "autocreated" entities.
      if (!$entity->isNew()) {
        $label .= ' (' . $entity->id() . ')';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }

    return implode(', ', $entity_labels);
  }

  /**
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (entity id)', match the ID from inside the parentheses.
    // @todo Add support for entities containing parentheses in their ID.
    // @see https://www.drupal.org/node/2520416
    if (preg_match("/.+\s\(([^\)]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

}
