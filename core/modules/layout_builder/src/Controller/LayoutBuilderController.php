<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
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
  use AjaxHelperTrait;

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
   *
   * @return array
   *   A render array.
   */
  public function layout(SectionStorageInterface $section_storage) {
    return [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
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

}
