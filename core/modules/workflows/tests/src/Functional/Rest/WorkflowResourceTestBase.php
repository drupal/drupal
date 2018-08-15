<?php

namespace Drupal\Tests\workflows\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * ResourceTestBase for Workflow entity.
 */
abstract class WorkflowResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workflows',
    'workflow_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workflow';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * The Workflow entity.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer workflows']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $workflow = Workflow::create([
      'id' => 'rest_workflow',
      'label' => 'REST Worklow',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
    $configuration = $workflow->getTypePlugin()->getConfiguration();
    $configuration['example_setting'] = 'foo';
    $configuration['states']['draft']['extra'] = 'bar';
    $workflow->getTypePlugin()->setConfiguration($configuration);
    $workflow->save();
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [
        'module' => [
          'workflow_type_test',
        ],
      ],
      'id' => 'rest_workflow',
      'label' => 'REST Worklow',
      'langcode' => 'en',
      'status' => TRUE,
      'type' => 'workflow_type_complex_test',
      'type_settings' => [
        'states' => [
          'draft' => [
            'extra' => 'bar',
            'label' => 'Draft',
            'weight' => 0,
          ],
          'published' => [
            'label' => 'Published',
            'weight' => 1,
          ],
        ],
        'transitions' => [],
        'example_setting' => 'foo',
      ],
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
