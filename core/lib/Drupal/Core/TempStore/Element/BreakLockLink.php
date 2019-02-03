<?php

namespace Drupal\Core\TempStore\Element;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a link to break a tempstore lock.
 *
 * Properties:
 * - #label: The label of the object that is locked.
 * - #lock: \Drupal\Core\TempStore\Lock object.
 * - #url: \Drupal\Core\Url object pointing to the break lock form.
 *
 * Usage example:
 * @code
 * $build['examples_lock'] = [
 *   '#type' => 'break_lock_link',
 *   '#label' => $this->t('example item'),
 *   '#lock' => $tempstore->getMetadata('example_key'),
 *   '#url' => \Drupal\Core\Url::fromRoute('examples.break_lock_form'),
 * ];
 * @endcode
 *
 * @RenderElement("break_lock_link")
 */
class BreakLockLink extends RenderElement implements ContainerFactoryPluginInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new BreakLockLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [$this, 'preRenderLock'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders a lock into #markup.
   *
   * @param array $element
   *   A structured array with the following keys:
   *   - #label: The label of the object that is locked.
   *   - #lock: The lock object.
   *   - #url: The URL object with the destination to the break lock form.
   *
   * @return array
   *   The passed-in element containing a rendered lock in '#markup'.
   */
  public function preRenderLock($element) {
    if (isset($element['#lock']) && isset($element['#label']) && isset($element['#url'])) {
      /** @var \Drupal\Core\TempStore\Lock $lock */
      $lock = $element['#lock'];
      $age = $this->dateFormatter->formatTimeDiffSince($lock->getUpdated());
      $owner = $this->entityTypeManager->getStorage('user')->load($lock->getOwnerId());
      $username = [
        '#theme' => 'username',
        '#account' => $owner,
      ];
      $element['#markup'] = $this->t('This @label is being edited by user @user, and is therefore locked from editing by others. This lock is @age old. Click here to <a href=":url">break this lock</a>.', [
        '@label' => $element['#label'],
        '@user' => $this->renderer->render($username),
        '@age' => $age,
        ':url' => $element['#url']->toString(),
      ]);
    }
    return $element;
  }

}
