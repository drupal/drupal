<?php

declare(strict_types=1);

namespace Drupal\Core\Entity\Attribute;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an attribute for registering a bundle class.
 *
 * This makes an EntityInterface class under a module's Entity namespace
 * discoverable as a bundle class for an entity type. Because entity bundle
 * information from attribute discovery is added after 'entity_bundle_info'
 * hooks have run and before 'entity_bundle_info_alter' hooks have run, there
 * are two general use cases for using this attribute:
 * - Alter bundle information provided by 'entity_bundle_info' hooks, in which
 *   case any attribute class properties that are optional will not be set or
 *   override existing values.
 * - Provide bundle information in lieu of using the hooks, in which case the
 *   label property should be set. It is possible to define bundles with this
 *   attribute for entity types that do not have a 'bundle_entity_type' defined.
 *   For entity types that do have a 'bundle_entity_type' defined, it is not
 *   possible to use this attribute to designate a bundle class for nonexistent
 *   bundle entities.
 *
 * If there are multiple bundle classes with attributes specifying the same
 * entity type bundle, 'entity_bundle_info_alter' hooks can be implemented to
 * specify which bundle class will be used.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Bundle extends ContentEntityType {

  /**
   * Constructs a Bundle attribute object.
   *
   * @param string $entityType
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle ID, or NULL to use entity type ID as the bundle name.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the bundle.
   * @param bool|null $translatable
   *   (optional) A boolean value specifying whether this bundle has translation
   *   support enabled. If 'translatable' is defined for the bundle by an
   *   'entity_bundle_info' hook, using NULL here will prevent that from being
   *   overridden. Otherwise, NULL is the same as FALSE.
   */
  public function __construct(
    string $entityType,
    ?string $bundle = NULL,
    ?TranslatableMarkup $label = NULL,
    ?bool $translatable = NULL,
  ) {
    $bundle ??= $entityType;
    parent::__construct(
      id: "$entityType:$bundle",
      label: $label,
      additional: [
        'entity_type_bundle_info' => [
          $bundle => [
            // Setting this here because 'translatable' class property can not
            // be NULL.
            'translatable' => $translatable,
          ],
        ],
      ],
    );
  }

}
