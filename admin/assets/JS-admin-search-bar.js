/**
 * File: /sisme-games-editor/admin/assets/JS-admin-search-bar.js
 * JavaScript pour l'interface admin des recherches
 */

/**
 * @param {string} searchInputId - The ID of the search input element.
 * @param {string} listContainerId - The ID of the list container element.
 * @param {string} [itemTag='li'] - The tag name of the list items.
 */
function setupSimpleSearchFilter(searchInputId, listContainerId, cardSelector, nameSelector) {
    const input = document.getElementById(searchInputId);
    const list = document.getElementById(listContainerId);
    if (!input || !list) return;

    input.addEventListener('input', function () {
        const filter = normalizeString(input.value);
        const cards = list.querySelectorAll(cardSelector);
        for (let i = 0; i < cards.length; i++) {
            const nameElem = cards[i].querySelector(nameSelector);
            const name = nameElem ? (nameElem.textContent || nameElem.innerText) : '';
            cards[i].style.display = normalizeString(name).includes(filter) ? '' : 'none';
        }
    });
}

function normalizeString(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

document.addEventListener('DOMContentLoaded', function() {
    setupSimpleSearchFilter('sisme-admin-search-input-user', 'sisme-admin-users-list', '.sisme-admin-search-results', 'h3[id^="sisme-admin-user-"]');
});