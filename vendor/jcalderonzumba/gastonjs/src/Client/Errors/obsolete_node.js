Poltergeist.ObsoleteNode = (function (_super) {
  __extends(ObsoleteNode, _super);

  function ObsoleteNode() {
    _ref = ObsoleteNode.__super__.constructor.apply(this, arguments);
    return _ref;
  }

  ObsoleteNode.prototype.name = "Poltergeist.ObsoleteNode";

  ObsoleteNode.prototype.args = function () {
    return [];
  };

  ObsoleteNode.prototype.toString = function () {
    return this.name;
  };

  return ObsoleteNode;

})(Poltergeist.Error);
