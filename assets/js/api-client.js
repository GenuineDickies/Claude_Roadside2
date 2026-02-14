/**
 * RoadRunner — Shared API client
 * Usage:
 *   RR.apiGet('api/workflow.php?action=list_estimates')
 *     .then(data => { ... })
 *     .catch(err => alert(err.message));
 *
 *   RR.apiPost('api/workflow.php', { action: 'create_estimate', ... })
 *     .then(data => { ... });
 */
var RR = window.RR || {};

/** GET request — returns parsed JSON, throws on !success */
RR.apiGet = function(url) {
    return fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) throw new Error(data.error || 'API error');
            return data;
        });
};

/** POST request — accepts plain object or FormData, throws on !success */
RR.apiPost = function(url, params) {
    var body;
    if (params instanceof FormData) {
        body = params;
    } else {
        body = new FormData();
        Object.keys(params).forEach(function(k) {
            if (params[k] !== null && params[k] !== undefined) {
                body.append(k, params[k]);
            }
        });
    }
    return fetch(url, { method: 'POST', body: body })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) throw new Error(data.error || 'API error');
            return data;
        });
};

window.RR = RR;
