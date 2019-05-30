<?php

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shortcut\ShortcutSetStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Builds the shortcut set deletion form.
 *
 * @internal
 */
class ShortcutSetDeleteForm extends EntityDeleteForm {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The shortcut storage.
   *
   * @var \Drupal\shortcut\ShortcutSetStorageInterface
   */
  protected $storage;

  /**
   * Constructs a ShortcutSetDeleteForm object.
   */
  public function __construct(Connection $database, ShortcutSetStorageInterface $storage) {
    $this->database = $database;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('shortcut_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Find out how many users are directly assigned to this shortcut set, and
    // make a message.
    $number = $this->storage->countAssignedUsers($this->entity);
    $info = '';
    if ($number) {
      $info .= '<p>' . $this->formatPlural($number,
        '1 user has chosen or been assigned to this shortcut set.',
        '@count users have chosen or been assigned to this shortcut set.') . '</p>';
    }

    // Also, if a module implements hook_shortcut_default_set(), it's possible
    // that this set is being used as a default set. Add a message about that too.
    if ($this->moduleHandler->getImplementations('shortcut_default_set')) {
      $info .= '<p>' . t('If you have chosen this shortcut set as the default for some or all users, they may also be affected by deleting it.') . '</p>';
    }

    $form['info'] = [
      '#markup' => $info,
    ];

    return parent::buildForm($form, $form_state);
  }

}
