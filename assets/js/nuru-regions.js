/**
 * Canonical Namibian region -> town data, shared by every buyer/seller/agent
 * form (public and staff-facing). This is the single source of truth - do
 * not duplicate this list inline in a form's own JS/PHP again. Region keys
 * match the option values already used across every region <select> in the
 * system (e.g. "Karas", not "Karas (Karas)").
 */
(function (global) {
    'use strict';

    var NURU_TOWNS_BY_REGION = {
        'Khomas': ['Windhoek', 'Dordabis', 'Groot Aub'],
        'Erongo': ['Swakopmund', 'Walvis Bay', 'Henties Bay', 'Arandis', 'Usakos', 'Karibib', 'Omaruru'],
        'Oshana': ['Oshakati', 'Ondangwa', 'Ongwediva'],
        'Kavango East': ['Rundu', 'Mashare', 'Ndiyona'],
        'Kavango West': ['Nkurenkuru', 'Mpungu'],
        'Zambezi': ['Katima Mulilo', 'Bukalo', 'Linyanti'],
        'Otjozondjupa': ['Otjiwarongo', 'Grootfontein', 'Okahandja', 'Okakarara'],
        'Karas': ['Keetmanshoop', 'Luderitz', 'Karasburg', 'Oranjemund', 'Rosh Pinah', 'Bethanie', 'Aus'],
        'Omaheke': ['Gobabis', 'Aminuis', 'Otjinene'],
        'Omusati': ['Outapi', 'Oshikuku', 'Ruacana', 'Okahao', 'Tsandi'],
        'Oshikoto': ['Tsumeb', 'Omuthiya', 'Oniipa', 'Onayena'],
        'Ohangwena': ['Eenhana', 'Ongenga', 'Okongo', 'Engela'],
        'Hardap': ['Mariental', 'Rehoboth', 'Maltahohe', 'Aranos', 'Gibeon'],
        'Kunene': ['Opuwo', 'Khorixas', 'Outjo', 'Kamanjab']
    };

    // Derived town -> region lookup, for forms/legacy code that still needs
    // to go the other direction (e.g. an autocomplete that only captures a
    // town name). Kept in sync automatically - never populated by hand.
    var NURU_REGION_BY_TOWN = {};
    Object.keys(NURU_TOWNS_BY_REGION).forEach(function (region) {
        NURU_TOWNS_BY_REGION[region].forEach(function (town) {
            NURU_REGION_BY_TOWN[town] = region;
        });
    });

    global.NURU_TOWNS_BY_REGION = NURU_TOWNS_BY_REGION;
    global.NURU_REGION_BY_TOWN = NURU_REGION_BY_TOWN;
})(window);
