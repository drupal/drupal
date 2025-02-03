module.exports.assertion = function () {
  this.message = 'Ensuring no deprecation errors have been triggered';
  this.expected = '';
  this.pass = (deprecationMessages) => deprecationMessages.length === 0;
  this.value = (result) => {
    const sessionStorageEntries = JSON.parse(result.value);
    const deprecationMessages =
      sessionStorageEntries !== null
        ? sessionStorageEntries.filter((message) =>
            message.includes('[Deprecation]'),
          )
        : [];

    return deprecationMessages.map((message) =>
      message.replace('[Deprecation] ', ''),
    );
  };
  this.command = (callback) =>
    // eslint-disable-next-line prefer-arrow-callback
    this.api.execute(
      function () {
        return window.sessionStorage.getItem('js_testing_log_test.warnings');
      },
      [],
      callback,
    );
};
