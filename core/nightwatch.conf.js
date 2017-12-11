const outputFolder = process.env.NIGHTWATCH_OUTPUT ? process.env.NIGHTWATCH_OUTPUT : 'reports/nightwatch';
const hostname = process.env.NIGHTWATCH_HOSTNAME ? process.env.NIGHTWATCH_HOSTNAME : 'localhost';

module.exports = {
  src_folders: ['tests/Drupal/Nightwatch/Tests'],
  output_folder: outputFolder,
  custom_commands_path: '',
  custom_assertions_path: '',
  page_objects_path: '',
  globals_path: 'tests/Drupal/Nightwatch/globals.js',
  selenium: {
    start_process: false,
  },
  test_settings: {
    default: {
      selenium_port: 9515,
      selenium_host: hostname,
      default_path_prefix: '',
      desiredCapabilities: {
        browserName: 'chrome',
        acceptSslCerts: true,
        chromeOptions: {
          args: [
            '--headless',
            '--disable-gpu',
            '--disable-notifications',
          ],
        },
      },
      screenshots: {
        enabled: true,
        on_failure: true,
        on_error: true,
        path: `${outputFolder}/screenshots`,
      },
    },
  },
};
