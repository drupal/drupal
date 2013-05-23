<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController
 */

namespace Drupal\comment\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\field\FieldInfo;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;


class CommentController implements ControllerInterface {

  /**
   * Field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('field.info')
    );
  }

  /**
   * Constructs a CustomBlock object.
   *
   * @param \Drupal\field\FieldInfo $field_info
   *   Field Info service.
   */
  public function __construct(FieldInfo $field_info) {
    $this->fieldInfo = $field_info;
  }

  /**
   * Redirects legacy node links to new path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node which the comment is a reply to.
   */
  public function redirectNode(EntityInterface $node) {
    $fields = array_filter($this->fieldInfo->getFieldMap(), function ($value) use ($node) {
      if ($value['type'] == 'comment' && isset($value['bundles']['node']) &&
          in_array($node->bundle(), $value['bundles']['node'])) {
        return TRUE;
      }
    });
    // First field will do.
    if (!empty($fields) && ($field_names = array_keys($fields)) && ($field_name = reset($field_names))) {
      return new RedirectResponse(url('comment/reply/node/' . $node->id() . '/' . $field_name, array('absolute' => TRUE)));
    }

    throw new NotFoundHttpException();
  }

}
