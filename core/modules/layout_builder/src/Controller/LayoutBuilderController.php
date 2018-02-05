<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a controller to provide the Layout Builder admin UI.
 *
 * @internal
 */
class LayoutBuilderController implements ContainerInjectionInterface {

  use LayoutBuilderContextTrait;
  use StringTranslationTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * LayoutBuilderController constructor.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, MessengerInterface $messenger) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('messenger')
    );
  }

  /**
   * Provides a title callback.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return string
   *   The title for the layout page.
   */
  public function title(SectionStorageInterface $section_storage) {
    return $this->t('Edit layout for %label', ['%label' => $section_storage->label()]);
  }

  /**
   * Renders the Layout UI.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param bool $is_rebuilding
   *   (optional) Indicates if the layout is rebuilding, defaults to FALSE.
   *
   * @return array
   *   A render array.
   */
  public function layout(SectionStorageInterface $section_storage, $is_rebuilding = FALSE) {
    $this->prepareLayout($section_storage, $is_rebuilding);

    $output = [];
    $count = 0;
    for ($i = 0; $i < $section_storage->count(); $i++) {
      $output[] = $this->buildAddSectionLink($section_storage, $count);
      $output[] = $this->buildAdministrativeSection($section_storage, $count);
      $count++;
    }
    $output[] = $this->buildAddSectionLink($section_storage, $count);
    $output['#attached']['library'][] = 'layout_builder/drupal.layout_builder';
    $output['#type'] = 'container';
    $output['#attributes']['id'] = 'layout-builder';
    // Mark this UI as uncacheable.
    $output['#cache']['max-age'] = 0;
    return $output;
  }

  /**
   * Prepares a layout for use in the UI.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param bool $is_rebuilding
   *   Indicates if the layout is rebuilding.
   */
  protected function prepareLayout(SectionStorageInterface $section_storage, $is_rebuilding) {
    // Only add sections if the layout is new and empty.
    if (!$is_rebuilding && $section_storage->count() === 0) {
      $sections = [];
      // If this is an empty override, copy the sections from the corresponding
      // default.
      if ($section_storage instanceof OverridesSectionStorageInterface) {
        $sections = $section_storage->getDefaultSectionStorage()->getSections();
      }

      // For an empty layout, begin with a single section of one column.
      if (!$sections) {
        $sections[] = new Section('layout_onecol');
      }

      foreach ($sections as $section) {
        $section_storage->appendSection($section);
      }
      $this->layoutTempstoreRepository->set($section_storage);
    }
  }

  /**
   * Builds a link to add a new section at a given delta.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   *
   * @return array
   *   A render array for a link.
   */
  protected function buildAddSectionLink(SectionStorageInterface $section_storage, $delta) {
    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();
    return [
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Add Section'),
        '#url' => Url::fromRoute('layout_builder.choose_section',
          [
            'section_storage_type' => $storage_type,
            'section_storage' => $storage_id,
            'delta' => $delta,
          ],
          [
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'dialog',
              'data-dialog-renderer' => 'off_canvas',
            ],
          ]
        ),
      ],
      '#type' => 'container',
      '#attributes' => [
        'class' => ['add-section'],
      ],
    ];
  }

  /**
   * Builds the render array for the layout section while editing.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section.
   *
   * @return array
   *   The render array for a given section.
   */
  protected function buildAdministrativeSection(SectionStorageInterface $section_storage, $delta) {
    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();
    $section = $section_storage->getSection($delta);

    $layout = $section->getLayout();
    $build = $section->toRenderArray($this->getAvailableContexts($section_storage), TRUE);
    $layout_definition = $layout->getPluginDefinition();

    foreach ($layout_definition->getRegions() as $region => $info) {
      if (!empty($build[$region])) {
        foreach ($build[$region] as $uuid => $block) {
          $build[$region][$uuid]['#attributes']['class'][] = 'draggable';
          $build[$region][$uuid]['#attributes']['data-layout-block-uuid'] = $uuid;
          $build[$region][$uuid]['#contextual_links'] = [
            'layout_builder_block' => [
              'route_parameters' => [
                'section_storage_type' => $storage_type,
                'section_storage' => $storage_id,
                'delta' => $delta,
                'region' => $region,
                'uuid' => $uuid,
              ],
            ],
          ];
        }
      }

      $build[$region]['layout_builder_add_block']['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Add Block'),
        '#url' => Url::fromRoute('layout_builder.choose_block',
          [
            'section_storage_type' => $storage_type,
            'section_storage' => $storage_id,
            'delta' => $delta,
            'region' => $region,
          ],
          [
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'dialog',
              'data-dialog-renderer' => 'off_canvas',
            ],
          ]
        ),
      ];
      $build[$region]['layout_builder_add_block']['#type'] = 'container';
      $build[$region]['layout_builder_add_block']['#attributes'] = ['class' => ['add-block']];
      $build[$region]['layout_builder_add_block']['#weight'] = 1000;
      $build[$region]['#attributes']['data-region'] = $region;
      $build[$region]['#attributes']['class'][] = 'layout-builder--layout__region';
    }

    $build['#attributes']['data-layout-update-url'] = Url::fromRoute('layout_builder.move_block', [
      'section_storage_type' => $storage_type,
      'section_storage' => $storage_id,
    ])->toString();
    $build['#attributes']['data-layout-delta'] = $delta;
    $build['#attributes']['class'][] = 'layout-builder--layout';

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-section'],
      ],
      'configure' => [
        '#type' => 'link',
        '#title' => $this->t('Configure section'),
        '#access' => $layout instanceof PluginFormInterface,
        '#url' => Url::fromRoute('layout_builder.configure_section', [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
        ]),
        '#attributes' => [
          'class' => ['use-ajax', 'configure-section'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
      ],
      'remove' => [
        '#type' => 'link',
        '#title' => $this->t('Remove section'),
        '#url' => Url::fromRoute('layout_builder.remove_section', [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
        ]),
        '#attributes' => [
          'class' => ['use-ajax', 'remove-section'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
      ],
      'layout-section' => $build,
    ];
  }

  /**
   * Saves the layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function saveLayout(SectionStorageInterface $section_storage) {
    $section_storage->save();
    $this->layoutTempstoreRepository->delete($section_storage);

    if ($section_storage instanceof OverridesSectionStorageInterface) {
      $this->messenger->addMessage($this->t('The layout override has been saved.'));
    }
    else {
      $this->messenger->addMessage($this->t('The layout has been saved.'));
    }

    return new RedirectResponse($section_storage->getRedirectUrl()->setAbsolute()->toString());
  }

  /**
   * Cancels the layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function cancelLayout(SectionStorageInterface $section_storage) {
    $this->layoutTempstoreRepository->delete($section_storage);

    $this->messenger->addMessage($this->t('The changes to the layout have been discarded.'));

    return new RedirectResponse($section_storage->getRedirectUrl()->setAbsolute()->toString());
  }

}
