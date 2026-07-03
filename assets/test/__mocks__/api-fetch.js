// Stub for @wordpress/api-fetch — real implementation is a WordPress global.
// Tests that need api-fetch should inject their own jest.fn() via the apiFetch prop.
const apiFetch = jest.fn();
module.exports = apiFetch;
module.exports.default = apiFetch;
