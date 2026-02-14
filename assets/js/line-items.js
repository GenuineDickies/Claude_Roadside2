/**
 * RoadRunner — Line Items data manager
 * Manages the add/remove/calculate cycle for document line items.
 * Rendering is left to each page since templates differ.
 *
 * Usage:
 *   var mgr = RR.createLineItems({ hasMarkup: true });
 *   mgr.add({ item_type: 'labor', description: 'Diag', quantity: 1, unit_price: 85 });
 *   mgr.remove(0);
 *   mgr.getItems();  // → array of line item objects
 *   mgr.getTotals(); // → { labor, parts, services, tow, subtotal }
 *   mgr.getTaxable(); // → sum of taxable extended prices
 */
var RR = window.RR || {};

RR.createLineItems = function(opts) {
    var items = [];
    var lineNum = 0;
    var hasMarkup = opts && opts.hasMarkup;

    return {
        /** Add a line item, returns its index */
        add: function(item) {
            lineNum++;
            var qty = parseFloat(item.quantity) || 1;
            var price = parseFloat(item.unit_price) || 0;
            var markup = hasMarkup && item.markup_pct != null && item.markup_pct !== ''
                ? parseFloat(item.markup_pct) : null;
            var ext = Math.round(qty * price * (1 + (markup || 0) / 100) * 100) / 100;
            items.push({
                line_number: lineNum,
                item_type: item.item_type || 'labor',
                description: item.description || '',
                quantity: qty,
                unit_price: price,
                markup_pct: markup,
                extended_price: ext,
                taxable: item.taxable !== undefined ? item.taxable : 1
            });
            return items.length - 1;
        },

        /** Remove item at index and renumber remaining */
        remove: function(idx) {
            items.splice(idx, 1);
            for (var i = 0; i < items.length; i++) items[i].line_number = i + 1;
            lineNum = items.length;
        },

        /** Get all items */
        getItems: function() { return items; },

        /** Get item count */
        getCount: function() { return items.length; },

        /** Sum extended prices by category */
        getTotals: function() {
            var labor = 0, parts = 0, services = 0, tow = 0;
            items.forEach(function(li) {
                switch (li.item_type) {
                    case 'labor': labor += li.extended_price; break;
                    case 'parts': parts += li.extended_price; break;
                    case 'service_fee': services += li.extended_price; break;
                    case 'tow_mileage': tow += li.extended_price; break;
                }
            });
            return { labor: labor, parts: parts, services: services, tow: tow,
                     subtotal: labor + parts + services + tow };
        },

        /** Sum of taxable extended prices */
        getTaxable: function() {
            return items.reduce(function(sum, li) {
                return sum + (li.taxable ? li.extended_price : 0);
            }, 0);
        },

        /** Reset to empty */
        clear: function() { items.length = 0; lineNum = 0; }
    };
};

window.RR = RR;
