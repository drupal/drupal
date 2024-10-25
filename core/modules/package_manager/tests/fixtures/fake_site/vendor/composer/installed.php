<?php

/**
 * @file
 */

return [
  'root' => [
    'name' => 'fake/site',
    'pretty_version' => '1.2.4',
    'version' => '1.2.4.0',
    'reference' => NULL,
    'type' => 'library',
    'install_path' => __DIR__ . '/../../',
    'aliases' => [],
    'dev' => TRUE,
  ],
  'versions' => [
    'drupal/core' => [
      'pretty_version' => '9.8.0',
      'version' => '9.8.0.0',
      'reference' => '31fd2270701526555acae45a3601c777e35508d5',
      'type' => 'drupal-core',
      'install_path' => __DIR__ . '/../drupal/core',
      'aliases' => [],
      'dev_requirement' => FALSE,
    ],
    'drupal/core-dev' => [
      'pretty_version' => '9.8.0',
      'version' => '9.8.0.0',
      'reference' => 'b99a99a11ff2779b5e4c5787dc43575382a3548c',
      'type' => 'package',
      'install_path' => __DIR__ . '/../drupal/core-dev',
      'aliases' => [],
      'dev_requirement' => TRUE,
    ],
    'drupal/core-recommended' => [
      'pretty_version' => '9.8.0',
      'version' => '9.8.0.0',
      'reference' => '112e4f7cfe8312457cd0eb58dcbffebc148850d8',
      'type' => 'project',
      'install_path' => __DIR__ . '/../drupal/core-recommended',
      'aliases' => [],
      'dev_requirement' => FALSE,
    ],
    'fake/site' => [
      'pretty_version' => '1.2.4',
      'version' => '1.2.4.0',
      'reference' => NULL,
      'type' => 'library',
      'install_path' => __DIR__ . '/../../',
      'aliases' => [],
      'dev_requirement' => FALSE,
    ],
  ],
];
