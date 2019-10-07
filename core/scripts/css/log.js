module.exports = (message) => {
  // Logging human-readable timestamp.
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
};
