/**
 * ACF Data Tables - Frontend JavaScript
 */
(function() {
    'use strict';
    
    /**
     * Initialize all tables on the page
     */
    function init() {
        var tables = document.querySelectorAll('.acf-dt-table');
        tables.forEach(function(table) {
            initTable(table);
        });
    }
    
    /**
     * Initialize a single table
     */
    function initTable(table) {
        var tableId = table.getAttribute('data-table-id');
        
        // Initialize sorting
        if (table.classList.contains('acf-dt-sortable')) {
            initSorting(table);
        }
        
        // Initialize search
        if (table.classList.contains('acf-dt-searchable')) {
            initSearch(table);
        }
    }
    
    /**
     * Initialize Sorting
     */
    function initSorting(table) {
        var headers = table.querySelectorAll('thead th');
        
        headers.forEach(function(header, index) {
            header.addEventListener('click', function() {
                sortTable(table, index, header);
            });
        });
    }
    
    /**
     * Sort Table
     */
    function sortTable(table, colIndex, header) {
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var isAsc = header.classList.contains('sort-asc');
        
        // Remove sort classes from all headers
        table.querySelectorAll('thead th').forEach(function(th) {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        // Sort rows
        rows.sort(function(a, b) {
            var aVal = getCellValue(a, colIndex);
            var bVal = getCellValue(b, colIndex);
            
            // Try numeric comparison
            var aNum = parseNumber(aVal);
            var bNum = parseNumber(bVal);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAsc ? bNum - aNum : aNum - bNum;
            }
            
            // String comparison
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
            
            if (isAsc) {
                return bVal.localeCompare(aVal);
            }
            return aVal.localeCompare(bVal);
        });
        
        // Update header class
        header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
        
        // Reorder rows in DOM
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });
    }
    
    /**
     * Get Cell Value
     */
    function getCellValue(row, index) {
        var cell = row.cells[index];
        if (!cell) return '';
        
        // Get text content, ignoring HTML
        return cell.textContent.trim();
    }
    
    /**
     * Parse Number (handles currency, percentages, etc.)
     */
    function parseNumber(str) {
        // Remove currency symbols, commas, and percent signs
        var cleaned = str.replace(/[$€£¥,\s%]/g, '');
        return parseFloat(cleaned);
    }
    
    /**
     * Initialize Search
     */
    function initSearch(table) {
        var wrapper = table.closest('.acf-dt-responsive-wrapper') || table.parentElement;
        var searchWrapper = wrapper.previousElementSibling;
        
        if (!searchWrapper || !searchWrapper.classList.contains('acf-dt-search-wrapper')) {
            return;
        }
        
        var searchInput = searchWrapper.querySelector('.acf-dt-search');
        if (!searchInput) return;
        
        var debounceTimer;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                filterTable(table, searchInput.value);
            }, 200);
        });
    }
    
    /**
     * Filter Table
     */
    function filterTable(table, query) {
        var tbody = table.querySelector('tbody');
        var rows = tbody.querySelectorAll('tr');
        var searchTerms = query.toLowerCase().trim().split(/\s+/);
        var hasResults = false;
        
        // Remove existing no-results row
        var noResultsRow = tbody.querySelector('.acf-dt-no-results-row');
        if (noResultsRow) {
            noResultsRow.remove();
        }
        
        rows.forEach(function(row) {
            if (row.classList.contains('acf-dt-no-results-row')) return;
            
            var rowText = row.textContent.toLowerCase();
            var matches = true;
            
            // All search terms must match
            searchTerms.forEach(function(term) {
                if (term && rowText.indexOf(term) === -1) {
                    matches = false;
                }
            });
            
            row.style.display = matches ? '' : 'none';
            
            if (matches) {
                hasResults = true;
            }
        });
        
        // Show no results message
        if (!hasResults && query.trim()) {
            var colCount = table.querySelectorAll('thead th').length;
            var noResultsHtml = '<tr class="acf-dt-no-results-row"><td colspan="' + colCount + '" class="acf-dt-no-results">No results found for "' + escapeHtml(query) + '"</td></tr>';
            tbody.insertAdjacentHTML('beforeend', noResultsHtml);
        }
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
