<?php

namespace Drupal\content_moderation\Plugin\Field\FieldFormatter;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_moderation_state' formatter.
 *
 * @FieldFormatter(
 *   id = "content_moderation_state",
 *   label = @Translation("Content moderation state"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class ContentModerationStateFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Create an instance of ContentModerationStateFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModerationInformationInterface $moderation_information) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $workflow = $this->moderationInformation->getWorkflowForEntity($items->getEntity());
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $workflow->getState($item->value)->label(),
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'moderation_state' && $field_definition->getTargetEntityTypeId() !== 'content_moderation_state';
  }

}
