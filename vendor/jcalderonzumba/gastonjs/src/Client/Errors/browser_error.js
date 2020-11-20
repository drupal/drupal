Poltergeist.BrowserError = (function (_super) {
  __extends(BrowserError, _super);

  function BrowserError(message, stack) {
    this.message = message;
    this.stack = stack;
  }

  BrowserError.prototype.name = "Poltergeist.BrowserError";

  BrowserError.prototype.args = function () {
    return [this.message, this.stack];
  };

  return BrowserError;

})(Poltergeist.Error);
