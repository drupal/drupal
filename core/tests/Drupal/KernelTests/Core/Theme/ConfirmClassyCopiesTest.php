<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Confirms that theme assets copied from Classy have not been changed.
 *
 * If a copied Classy asset is changed, it should no longer be in a /classy
 * subdirectory. The files there should be exact copies from Classy. Once it has
 * changed, it is custom to the theme and should be moved to a different
 * location.
 *
 * @group Theme
 */
class ConfirmClassyCopiesTest extends KernelTestBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->themeHandler = $this->container->get('theme_handler');
    $this->container->get('theme_installer')->install([
      'umami',
      'bartik',
      'seven',
      'claro',
    ]);
  }

  /**
   * Confirms that files copied from Classy have not been altered.
   *
   * The /classy subdirectory in a theme's css, js and images directories is for
   * unaltered copies of files from Classy. If a file in that subdirectory has
   * changed, then it is custom to that theme and should be moved to a different
   * directory. Additional information can be found in the README.txt of each of
   * those /classy subdirectories.
   *
   * @param string $theme
   *   The theme being tested.
   * @param string[] $file_hashes
   *   Provides an md5 hash for every asset copied from Classy.
   *
   * @dataProvider providerTestClassyCopies
   */
  public function testClassyCopies($theme, array $file_hashes) {
    $theme_path = $this->root . '/' . $this->themeHandler->getTheme($theme)->getPath();

    foreach (['images', 'css', 'js'] as $sub_folder) {
      $asset_path = "$theme_path/$sub_folder/classy";
      // If a theme has completely customized all files of a type there is
      // potentially no Classy subdirectory for that type. Tests can be skipped
      // for that type.
      if (!file_exists($asset_path)) {
        $this->assertEmpty($file_hashes[$sub_folder]);
        continue;
      }

      // Create iterators to collect all files in a asset directory.
      $directory = new \RecursiveDirectoryIterator($asset_path);
      $iterator = new \RecursiveIteratorIterator($directory);
      $filecount = 0;
      foreach ($iterator as $fileinfo) {
        $extension = $fileinfo->getExtension();
        if ($extension === $sub_folder || ($sub_folder === 'images' && $extension === 'png')) {
          $filecount++;
          $filename = $fileinfo->getFilename();
          $hash = md5_file($fileinfo->getPathname());
          $this->assertNotEmpty($file_hashes[$sub_folder][$filename], "$sub_folder file: $filename not present.");
          $this->assertEquals(
            $file_hashes[$sub_folder][$filename],
            $hash,
            "$filename is in the theme's /classy subdirectory, but the file contents no longer match the original file from Classy. This should be moved to a new directory and libraries should be updated. The file can be removed from the data provider."
          );
        }

      }
      $this->assertCount($filecount, $file_hashes[$sub_folder], "Different count for $sub_folder files in the /classy subdirectory. If a file was added to /classy, it shouldn't have been. If it was intentionally removed, it should also be removed from this test's data provider.");
    }
  }

  /**
   * Provides md5 hashes for a theme's asset files copied from Classy.
   *
   * @return array
   *   Theme name and asset file hashes.
   */
  public function providerTestClassyCopies() {
    return [
      'umami' => [
        'theme-name' => 'umami',
        'file-hashes' => [
          'css' => [
            'media-library.css' => 'bb405519d30970c721405452dfb7b38e',
            'action-links.css' => '6abb88c2b3b6884c1a64fa5ca4853d45',
            'file.css' => 'b644547e5e8eb6aa23505b307dc69c32',
            'dropbutton.css' => 'f8e4b0b81ff60206b27f622e85a6a0ee',
            'book-navigation.css' => 'e8219368d360bd4a10763610ada85a1c',
            'tableselect.css' => '8e966ac85a0cc60f470717410640c8fe',
            'ui-dialog.css' => '4a3d036007ba8c8c80f4a21a369c72cc',
            'user.css' => '0ec6acc22567a7c9c228f04b5a97c711',
            'item-list.css' => '1d519afe6007f4b01e00f22b0ba8bf33',
            'image-widget.css' => '2da54829199f64a2c390930c3b0913a3',
            'field.css' => '8f4718bc926eea7e007ecfd6f410ee8d',
            'tablesort.css' => 'f6ed3b44832bebffa09fc3b4b6ce27ab',
            'tabs.css' => 'e58827db5c767c41b67488244c14056c',
            'forum.css' => '297a40db815570c2195515767c4b3144',
            'progress.css' => '5147a9b07ede9f456c6a3f3efeb520e1',
            'collapse-processed.css' => '95039b6f71bbdd3c986179f075f74d2f',
            'details.css' => 'fdd0606ea856072f5e6a19ab1a2e850e',
            'inline-form.css' => 'cc5cbfd34511d9021a53ec693c110740',
            'link.css' => '22f42d430fe458080a7739c70a2d2ea5',
            'textarea.css' => '2bc390c137c5205bbcd7645d6c1c86de',
            'links.css' => '21fe64349f5702cd5b89104a1d3b9cd3',
            'form.css' => 'f9bd159b5ed0e1bfb2ca8d759e8c031c',
            'exposed-filters.css' => '396a5f76dafec5f78f4e736f69a0874f',
            'tabledrag.css' => '98d24ff864c7699dfa6da9190c5e70df',
            'pager.css' => 'd10589366720f9c15b66df434baab4da',
            'search-results.css' => 'ce3ca8fcd54e72f142ba29da5a3a5c9a',
            'button.css' => '3abebf58e144fd4150d80facdbe5d10f',
            'node.css' => '81ea0a3fef211dbc32549ac7f39ec646',
            'dialog.css' => '1c1f05dde2dff1b6befacaa811c019f8',
            'menu.css' => 'b9587d2e8f71fe2bbc625fc40b989112',
            'icons.css' => 'c067e837e6e6d576734d443b7d40447b',
            'breadcrumb.css' => '14268f8071dffd40ce7a39862b8fbc56',
            'media-embed-error.css' => 'c66322e308b78af92a30401326d19d52',
            'container-inline.css' => 'ae9caee6071b319ac97bf0bb3e14b542',
            'more-link.css' => 'b2ebfb826e035334340193b42246b180',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js' => 'decf95c314bf22c642fb630179502e43',
            'media_embed_ckeditor.theme.js' => '1b17d61e258c4fdaa129acecf773f04e',
          ],
          'images' => [
            'x-office-spreadsheet.png' => 'fc5d4b32f259ea6d0f960b17a0886f63',
            'application-octet-stream.png' => 'fef73511632890590b5ae0a13c99e4bf',
            'x-office-presentation.png' => '8ba9f51c97a2b47de2c8c117aafd7dcd',
            'x-office-document.png' => '48e0c92b5dec1a027f43a5c6fe190f39',
            'image-x-generic.png' => '9aca2e02c3cdbb391ca721d40fa4c0c6',
            'text-x-script.png' => 'f9dc156d35298536011ea48226b21682',
            'text-html.png' => '9d2d3003a786ab392d42744b2d064eec',
            'video-x-generic.png' => 'a5dc89b884a8a1b666c15bb41fd88ee9',
            'forum-icons.png' => 'dfa091b192819cc14523ccd653e7b5ff',
            'text-x-generic.png' => '1b769df473f54d6f78f7aba79ec25e12',
            'application-pdf.png' => 'bb41f8b679b9d93323b30c87fde14de9',
            'application-x-executable.png' => 'fef73511632890590b5ae0a13c99e4bf',
            'package-x-generic.png' => 'bb8581301a2030b48ff3c67374eed88a',
            'text-plain.png' => '1b769df473f54d6f78f7aba79ec25e12',
            'audio-x-generic.png' => 'f7d0e6fbcde58594bd1102db95e3ea7b',
          ],
        ],
      ],
      // Will be populated when Classy libraries are copied to Claro.
      'claro' => [
        'theme-name' => 'claro',
        'file-hashes' => [
          'css' => [],
          'js' => [],
          'images' => [],
        ],
      ],
      'seven' => [
        'theme-name' => 'seven',
        'file-hashes' => [
          'css' => [
            'media-library.css' => 'bb405519d30970c721405452dfb7b38e',
            'action-links.css' => '6abb88c2b3b6884c1a64fa5ca4853d45',
            'file.css' => 'b644547e5e8eb6aa23505b307dc69c32',
            'dropbutton.css' => 'f8e4b0b81ff60206b27f622e85a6a0ee',
            'book-navigation.css' => 'e8219368d360bd4a10763610ada85a1c',
            'tableselect.css' => '8e966ac85a0cc60f470717410640c8fe',
            'ui-dialog.css' => '4a3d036007ba8c8c80f4a21a369c72cc',
            'user.css' => '0ec6acc22567a7c9c228f04b5a97c711',
            'item-list.css' => '1d519afe6007f4b01e00f22b0ba8bf33',
            'image-widget.css' => '2da54829199f64a2c390930c3b0913a3',
            'field.css' => '8f4718bc926eea7e007ecfd6f410ee8d',
            'tablesort.css' => 'f6ed3b44832bebffa09fc3b4b6ce27ab',
            'tabs.css' => 'e58827db5c767c41b67488244c14056c',
            'forum.css' => '297a40db815570c2195515767c4b3144',
            'progress.css' => '5147a9b07ede9f456c6a3f3efeb520e1',
            'collapse-processed.css' => 'a287a092b5af52ee41c9962776df073e',
            'inline-form.css' => 'cc5cbfd34511d9021a53ec693c110740',
            'link.css' => '22f42d430fe458080a7739c70a2d2ea5',
            'textarea.css' => '2bc390c137c5205bbcd7645d6c1c86de',
            'links.css' => '21fe64349f5702cd5b89104a1d3b9cd3',
            'form.css' => '27ecf2f2e4627e292f0c48b5e05c4ef5',
            'exposed-filters.css' => '396a5f76dafec5f78f4e736f69a0874f',
            'tabledrag.css' => '98d24ff864c7699dfa6da9190c5e70df',
            'indented.css' => '48e214a106d9fede1e05aa10b4796361',
            'messages.css' => '21659ecd2f7ee6884805434329e6bea4',
            'pager.css' => 'd10589366720f9c15b66df434baab4da',
            'search-results.css' => 'ce3ca8fcd54e72f142ba29da5a3a5c9a',
            'button.css' => '3abebf58e144fd4150d80facdbe5d10f',
            'node.css' => '81ea0a3fef211dbc32549ac7f39ec646',
            'menu.css' => 'ddb533716fc3be2ad76f283c5532ee85',
            'icons.css' => '85b21f21c0017e6a9fc83d00462904d0',
            'breadcrumb.css' => '14268f8071dffd40ce7a39862b8fbc56',
            'media-embed-error.css' => '015171a1f01fff8e2bec4e06d9b451e7',
            'container-inline.css' => 'ae9caee6071b319ac97bf0bb3e14b542',
            'more-link.css' => 'b2ebfb826e035334340193b42246b180',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js' => 'decf95c314bf22c642fb630179502e43',
            'media_embed_ckeditor.theme.js' => '1b17d61e258c4fdaa129acecf773f04e',
          ],
          'images' => [
            'x-office-spreadsheet.png' => 'fc5d4b32f259ea6d0f960b17a0886f63',
            'application-octet-stream.png' => 'fef73511632890590b5ae0a13c99e4bf',
            'x-office-presentation.png' => '8ba9f51c97a2b47de2c8c117aafd7dcd',
            'x-office-document.png' => '48e0c92b5dec1a027f43a5c6fe190f39',
            'image-x-generic.png' => '9aca2e02c3cdbb391ca721d40fa4c0c6',
            'text-x-script.png' => 'f9dc156d35298536011ea48226b21682',
            'text-html.png' => '9d2d3003a786ab392d42744b2d064eec',
            'video-x-generic.png' => 'a5dc89b884a8a1b666c15bb41fd88ee9',
            'forum-icons.png' => 'dfa091b192819cc14523ccd653e7b5ff',
            'text-x-generic.png' => '1b769df473f54d6f78f7aba79ec25e12',
            'application-pdf.png' => 'bb41f8b679b9d93323b30c87fde14de9',
            'application-x-executable.png' => 'fef73511632890590b5ae0a13c99e4bf',
            'package-x-generic.png' => 'bb8581301a2030b48ff3c67374eed88a',
            'text-plain.png' => '1b769df473f54d6f78f7aba79ec25e12',
            'audio-x-generic.png' => 'f7d0e6fbcde58594bd1102db95e3ea7b',
          ],
        ],
      ],
      // Will be populated when Classy libraries are copied to Bartik.
      'bartik' => [
        'theme-name' => 'bartik',
        'file-hashes' => [
          'css' => [],
          'js' => [],
          'images' => [],
        ],
      ],
    ];
  }

}
