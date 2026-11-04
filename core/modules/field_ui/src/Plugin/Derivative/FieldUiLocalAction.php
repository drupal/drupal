<?php

namespace Drupal\field_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;

/**
 * Provides local action definitions for all entity bundles.
 */
class FieldUiLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Constructs a FieldUiLocalAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected RouteProviderInterface $routeProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        $this->derivatives["field_storage_config_add_$entity_type_id"] = [
          'route_name' => "field_ui.field_storage_config_add_$entity_type_id",
          'title' => $this->t('Create a new field'),
          'appears_on' => ["entity.$entity_type_id.field_ui_fields"],
          'options' => [
            'attributes' => [
              'class' => ['use-ajax', 'button'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '1100',
              ]),
            ],
          ],
        ];
        $this->derivatives["field_storage_config_reuse_$entity_type_id"] = [
          'route_name' => "field_ui.field_storage_config_reuse_$entity_type_id",
          'title' => $this->t('Re-use an existing field'),
          'appears_on' => ["entity.$entity_type_id.field_ui_fields"],
          'options' => [
            'attributes' => [
              'class' => ['use-ajax', 'button'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '1100',
              ]),
            ],
          ],
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
