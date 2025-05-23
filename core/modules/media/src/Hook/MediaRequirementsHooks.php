<?php

namespace Drupal\media\Hook;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\Entity\MediaType;

/**
 * Requirements checks for Media module.
 */
class MediaRequirementsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    foreach (MediaType::loadMultiple() as $type) {
      // Load the default display.
      $display = $this->entityDisplayRepository->getViewDisplay('media', $type->id());

      // Check for missing source field definition.
      $source_field_definition = $type->getSource()->getSourceFieldDefinition($type);
      if (empty($source_field_definition)) {
        $requirements['media_missing_source_field_' . $type->id()] = [
          'title' => $this->t('Media'),
          'description' => $this->t('The source field definition for the %type media type is missing.',
            [
              '%type' => $type->label(),
            ]
          ),
          'severity' => RequirementSeverity::Error,
        ];
        continue;
      }

      // When a new media type with an image source is created we're
      // configuring the default entity view display using the 'large' image
      // style. Unfortunately, if a site builder has deleted the 'large' image
      // style, we need some other image style to use, but at this point, we
      // can't really know the site builder's intentions. So rather than do
      // something surprising, we're leaving the embedded media without an
      // image style and adding a warning that the site builder might want to
      // add an image style.
      // @see Drupal\media\Plugin\media\Source\Image::prepareViewDisplay
      if (!is_a($source_field_definition->getItemDefinition()->getClass(), ImageItem::class, TRUE)) {
        continue;
      }

      $component = $display->getComponent($source_field_definition->getName());
      if (empty($component) || $component['type'] !== 'image' || !empty($component['settings']['image_style'])) {
        continue;
      }

      $action_item = '';
      if ($this->moduleHandler->moduleExists('field_ui') && $this->currentUser->hasPermission('administer media display')) {
        $url = Url::fromRoute('entity.entity_view_display.media.default', [
          'media_type' => $type->id(),
        ])->toString();
        $action_item = new TranslatableMarkup('If you would like to change this, <a href=":display">add an image style to the %field_name field</a>.',
          [
            '%field_name' => $source_field_definition->label(),
            ':display' => $url,
          ]);
      }
      $requirements['media_default_image_style_' . $type->id()] = [
        'title' => $this->t('Media'),
        'description' => new TranslatableMarkup('The default display for the %type media type is not currently using an image style on the %field_name field. Not using an image style can lead to much larger file downloads. @action_item',
          [
            '%field_name' => $source_field_definition->label(),
            '@action_item' => $action_item,
            '%type' => $type->label(),
          ]
        ),
        'severity' => RequirementSeverity::Warning,
      ];
    }

    return $requirements;
  }

}
