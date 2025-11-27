<?php

declare(strict_types=1);

namespace Drupal\media_test_oembed\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Alters media bundles.
 */
#[Hook('entity_bundle_field_info_alter')]
final class EntityBundleInfoAlter {

  public const STATE_FLAG = 'media_test_oembed_only_urls_fieldname';

  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  public function __invoke(array &$fields, EntityTypeInterface $entityType, string $bundle): void {
    $constrained_fields_by_bundle = $this->state->get(self::STATE_FLAG, []);
    if (\count($constrained_fields_by_bundle) === 0) {
      return;
    }
    $constrained_fields = \array_keys(\array_intersect_key($fields, \array_flip($constrained_fields_by_bundle[$bundle] ?? [])));
    if ($entityType->id() !== 'media' ||
      \count($constrained_fields) === 0
    ) {
      return;
    }
    foreach ($constrained_fields as $field_name) {
      \assert($fields[$field_name] instanceof FieldConfigInterface);
      $fields[$field_name]->addConstraint('UniqueField');
      $fields[$field_name]->addPropertyConstraints('value', [
        'AllowedValues' => [
          'choices' => [
            'https://www.youtube.com/watch?v=BnEgnrUCXPY',
            'https://www.youtube.com/watch?v=15Nqbic6HZs',
          ],
          'message' => 'This site only allows Jazz videos, try again cat 🎷',
        ],
      ]);
    }
  }

}
