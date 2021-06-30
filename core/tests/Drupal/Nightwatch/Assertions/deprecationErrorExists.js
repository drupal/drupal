module.exports.assertion = function (expected) {
  this.message = `Testing if "${expected}" deprecation error has been triggered`;
  this.expected = expected;
  this.pass = (deprecationMessages) => deprecationMessages.includes(expected);
  this.value = (result) => {
    const sessionStorageEntries = JSON.parse(result.value);
    const deprecationMessages =
      sessionStorageEntries !== null
        ? sessionStorageEntries.filter((message) =>
            new RegExp('[Deprecation]').test(message),
          )
        : [];

    return deprecationMessages.map((message) =>
      message.replace('[Deprecation] ', ''),
    );
  };
  this.command = (callback) =>
    // eslint-disable-next-line prefer-arrow-callback
    this.api.execute(function () {
      return window.sessionStorage.getItem('js_testing_log_test.warnings');
    }, callback);
};
