const testingMode = process.env.TESTING_MODE || 'local';

const options = {
  src_folders: ['tests/Drupal/Nightwatch'],
  output_folder: false,
  custom_commands_path: '',
  custom_assertions_path: '',
  page_objects_path: '',
  globals_path: 'tests/nightwatch_bootstrap.js',
  selenium: {
    start_process: false,
  },
  test_settings: {
    default: {
      selenium_port: 9515,
      selenium_host: 'localhost',
      default_path_prefix: '',
      desiredCapabilities: {
        browserName: 'chrome',
        chromeOptions: {
          args: ['--no-sandbox'],
        },
        acceptSslCerts: true,
      },
    },
  },
};

if (testingMode === 'ci') {
  options.output_folder = process.env.NIGHTWATCH_OUTPUT;
}

module.exports = options;
