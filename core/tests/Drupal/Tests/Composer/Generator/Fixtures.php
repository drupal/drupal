<?php

namespace Drupal\Tests\Composer\Generator;

use Drupal\Composer\Generator\Util\DrupalCoreComposer;

/**
 * Convenience class for creating fixtures.
 */
class Fixtures {

  /**
   * Generate a suitable DrupalCoreComposer fixture for testing.
   *
   * @return \Drupal\Composer\Generator\Util\DrupalCoreComposer
   *   DrupalCoreComposer fixture.
   */
  public function drupalCoreComposerFixture() {
    return new DrupalCoreComposer($this->composerJson(), $this->composerLock());
  }

  /**
   * Data for a composer.json fixture.
   *
   * @return array
   *   composer.json fixture data.
   */
  protected function composerJson() {
    return [
      'name' => 'drupal/project-fixture',
      'description' => 'A fixture for testing the metapackage generator.',
      'type' => 'project',
      'license' => 'GPL-2.0-or-later',
      'require' =>
      [
        'composer/installers' => '^1.9',
        'php' => '>=7.0.8',
        'symfony/yaml' => '~3.4.5',
      ],
      'require-dev' =>
      [
        'behat/mink' => '^1.8',
      ],
    ];
  }

  /**
   * Data for a composer.lock fixture.
   *
   * @return array
   *   composer.lock fixture data.
   */
  protected function composerLock() {
    return [
      '_readme' =>
      [
        'This is a composer.lock fixture. It contains only a subset of a',
        'typical composer.lock file (just what is needed for testing).',
      ],
      'content-hash' => 'da9910627bab73a256b39ceda83d7167',
      'packages' =>
      [
        [
          'name' => "composer/installers",
          'version' => 'v1.9.0',
          'source' => [
            'type' => 'git',
            'url' => 'https://github.com/composer/installers.git',
            'reference' => 'b93bcf0fa1fccb0b7d176b0967d969691cd74cca',
          ],
        ],
        [
          'name' => 'symfony/polyfill-ctype',
          'version' => 'v1.12.0',
          'source' =>
          [
            'type' => 'git',
            'url' => 'https://github.com/symfony/polyfill-ctype.git',
            'reference' => '550ebaac289296ce228a706d0867afc34687e3f4',
          ],
        ],
        [
          'name' => 'symfony/yaml',
          'version' => 'v3.4.32',
          'source' =>
          [
            'type' => 'git',
            'url' => 'https://github.com/symfony/yaml.git',
            'reference' => '768f817446da74a776a31eea335540f9dcb53942',
          ],
        ],
      ],
      'packages-dev' =>
      [
        [
          'name' => 'behat/mink',
          'version' => 'v1.8.0',
          'source' =>
          [
            'type' => 'git',
            'url' => 'https://github.com/minkphp/Mink.git',
            'reference' => 'e1772aabb6b654464264a6cc72158c8b3409d8bc',
          ],
        ],
        [
          'name' => 'symfony/css-selector',
          'version' => 'v4.3.5',
          'source' =>
          [
            'type' => 'git',
            'url' => 'https://github.com/symfony/css-selector.git',
            'reference' => 'f4b3ff6a549d9ed28b2b0ecd1781bf67cf220ee9',
          ],
        ],
      ],
    ];
  }

}
