/**
 * ACF Data Tables - Admin JavaScript
 */
(function($) {
    'use strict';
    
    // State
    var columns = [];
    var rows = [];
    var postId = 0;
    var hasChanges = false;
    
    /**
     * Initialize
     */
    function init() {
        postId = $('#acf-dt-post-id').val();
        
        // Load columns from hidden field
        var columnsJson = $('#acf-dt-columns-json').val();
        if (columnsJson) {
            try {
                columns = JSON.parse(columnsJson);
            } catch (e) {
                columns = [];
            }
        }
        
        // Load rows from table
        loadRowsFromTable();
        
        // Bind events
        bindEvents();
    }
    
    /**
     * Load Rows From Table
     */
    function loadRowsFromTable() {
        rows = [];
        $('#acf-dt-editor tbody tr:not(.acf-dt-empty-row)').each(function() {
            var rowData = {};
            $(this).find('.acf-dt-cell').each(function() {
                var colKey = $(this).data('col');
                rowData[colKey] = $(this).val();
            });
            if (Object.keys(rowData).length > 0) {
                rows.push(rowData);
            }
        });
    }
    
    /**
     * Bind Events
     */
    function bindEvents() {
        // Cell change
        $(document).on('input', '.acf-dt-cell', function() {
            hasChanges = true;
            updateRowData($(this));
        });
        
        // Cell navigation with Tab and Enter
        $(document).on('keydown', '.acf-dt-cell', function(e) {
            var $cell = $(this);
            var $td = $cell.closest('td');
            var $tr = $td.closest('tr');
            
            if (e.key === 'Tab') {
                e.preventDefault();
                var $nextTd = e.shiftKey ? $td.prev() : $td.next();
                
                if ($nextTd.length && $nextTd.find('.acf-dt-cell').length) {
                    $nextTd.find('.acf-dt-cell').focus().select();
                } else if (!e.shiftKey) {
                    var $nextRow = $tr.next('tr');
                    if ($nextRow.length) {
                        $nextRow.find('.acf-dt-cell').first().focus().select();
                    }
                } else {
                    var $prevRow = $tr.prev('tr');
                    if ($prevRow.length) {
                        $prevRow.find('.acf-dt-cell').last().focus().select();
                    }
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                var $nextRow = e.shiftKey ? $tr.prev('tr') : $tr.next('tr');
                if ($nextRow.length) {
                    var colIndex = $td.index();
                    $nextRow.find('td').eq(colIndex).find('.acf-dt-cell').focus().select();
                }
            }
        });
        
        // Select all checkbox
        $('#acf-dt-select-all').on('change', function() {
            var checked = $(this).prop('checked');
            $('.acf-dt-row-select').prop('checked', checked);
            updateRowSelection();
        });
        
        // Row select checkbox
        $(document).on('change', '.acf-dt-row-select', function() {
            updateRowSelection();
        });
        
        // Add Row
        $('#acf-dt-add-row').on('click', addRow);
        
        // Add Column
        $('#acf-dt-add-col').on('click', showAddColumnDialog);
        
        // Delete Selected Rows
        $('#acf-dt-delete-row').on('click', deleteSelectedRows);
        
        // Save Data
        $('#acf-dt-save-data').on('click', saveData);
        
        // Import CSV
        $('#acf-dt-import-csv').on('click', importCSV);
        
        // Import HTML
        $('#acf-dt-import-html').on('click', importHTML);
        
        // Right-click context menu
        $(document).on('contextmenu', '.acf-dt-editor tbody tr', function(e) {
            e.preventDefault();
            showContextMenu(e, $(this));
        });
        
        // Close context menu on click elsewhere
        $(document).on('click', function() {
            $('.acf-dt-context-menu').remove();
        });
        
        // Warn on leave with unsaved changes
        $(window).on('beforeunload', function() {
            if (hasChanges) {
                return 'You have unsaved changes.';
            }
        });
        
        // Clear warning on form submit
        $('form#post').on('submit', function() {
            hasChanges = false;
        });
    }
    
    /**
     * Update Row Data
     */
    function updateRowData($cell) {
        var rowIndex = $cell.closest('tr').data('row');
        var colKey = $cell.data('col');
        
        if (rows[rowIndex]) {
            rows[rowIndex][colKey] = $cell.val();
        }
    }
    
    /**
     * Update Row Selection
     */
    function updateRowSelection() {
        $('#acf-dt-editor tbody tr').each(function() {
            if ($(this).find('.acf-dt-row-select').prop('checked')) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        });
    }
    
    /**
     * Add Row
     */
    function addRow() {
        if (columns.length === 0) {
            alert('Please define columns first in the Columns tab.');
            return;
        }
        
        // Remove empty row message
        $('.acf-dt-empty-row').remove();
        
        // Create new row data
        var newRowData = {};
        columns.forEach(function(col) {
            newRowData[col.col_key] = '';
        });
        
        // Create row HTML
        var rowIndex = rows.length;
        var html = '<tr data-row="' + rowIndex + '">';
        html += '<td class="acf-dt-select-col"><input type="checkbox" class="acf-dt-row-select"></td>';
        
        columns.forEach(function(col) {
            html += '<td><input type="text" class="acf-dt-cell" data-col="' + col.col_key + '" value=""></td>';
        });
        
        html += '</tr>';
        
        // Append to table
        $('#acf-dt-editor tbody').append(html);
        rows.push(newRowData);
        
        // Focus first cell of new row
        $('#acf-dt-editor tbody tr:last .acf-dt-cell:first').focus();
        
        hasChanges = true;
    }
    
    /**
     * Show Add Column Dialog
     */
    function showAddColumnDialog() {
        var html = '<div class="acf-dt-dialog-overlay">';
        html += '<div class="acf-dt-dialog">';
        html += '<h3>Add Column</h3>';
        html += '<div class="acf-dt-dialog-row">';
        html += '<label>Column Key (no spaces)</label>';
        html += '<input type="text" id="acf-dt-new-col-key" placeholder="column_name">';
        html += '</div>';
        html += '<div class="acf-dt-dialog-row">';
        html += '<label>Column Label</label>';
        html += '<input type="text" id="acf-dt-new-col-label" placeholder="Column Name">';
        html += '</div>';
        html += '<div class="acf-dt-dialog-row">';
        html += '<label>Type</label>';
        html += '<select id="acf-dt-new-col-type">';
        html += '<option value="text">Text</option>';
        html += '<option value="number">Number</option>';
        html += '<option value="currency">Currency</option>';
        html += '<option value="percent">Percentage</option>';
        html += '<option value="link">Link</option>';
        html += '<option value="image">Image URL</option>';
        html += '<option value="html">HTML</option>';
        html += '</select>';
        html += '</div>';
        html += '<div class="acf-dt-dialog-buttons">';
        html += '<button type="button" class="button" id="acf-dt-dialog-cancel">Cancel</button>';
        html += '<button type="button" class="button button-primary" id="acf-dt-dialog-add">Add Column</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        
        $('#acf-dt-new-col-key').focus();
        
        // Auto-generate key from label
        $('#acf-dt-new-col-label').on('input', function() {
            var label = $(this).val();
            var key = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            $('#acf-dt-new-col-key').val(key);
        });
        
        // Cancel
        $('#acf-dt-dialog-cancel').on('click', function() {
            $('.acf-dt-dialog-overlay').remove();
        });
        
        // Add
        $('#acf-dt-dialog-add').on('click', function() {
            var key = $('#acf-dt-new-col-key').val().trim();
            var label = $('#acf-dt-new-col-label').val().trim();
            var type = $('#acf-dt-new-col-type').val();
            
            if (!key || !label) {
                alert('Please enter both key and label.');
                return;
            }
            
            // Check for duplicate key
            var duplicate = columns.some(function(col) {
                return col.col_key === key;
            });
            
            if (duplicate) {
                alert('Column key already exists.');
                return;
            }
            
            // Add column
            addColumn(key, label, type);
            $('.acf-dt-dialog-overlay').remove();
        });
        
        // Close on Escape
        $(document).on('keydown.dialog', function(e) {
            if (e.key === 'Escape') {
                $('.acf-dt-dialog-overlay').remove();
                $(document).off('keydown.dialog');
            }
        });
    }
    
    /**
     * Add Column
     */
    function addColumn(key, label, type) {
        // Add to columns array
        columns.push({
            col_key: key,
            col_label: label,
            col_type: type,
            col_align: 'left',
            col_width: ''
        });
        
        // Add header cell
        $('#acf-dt-editor thead tr').append('<th data-key="' + key + '">' + label + '</th>');
        
        // Add cell to each row
        $('#acf-dt-editor tbody tr:not(.acf-dt-empty-row)').each(function(index) {
            $(this).append('<td><input type="text" class="acf-dt-cell" data-col="' + key + '" value=""></td>');
            if (rows[index]) {
                rows[index][key] = '';
            }
        });
        
        // Update empty row colspan
        var $emptyRow = $('.acf-dt-empty-row td');
        if ($emptyRow.length) {
            $emptyRow.attr('colspan', columns.length + 1);
        }
        
        hasChanges = true;
    }
    
    /**
     * Delete Selected Rows
     */
    function deleteSelectedRows() {
        var $selected = $('#acf-dt-editor tbody tr.selected');
        
        if ($selected.length === 0) {
            alert('Please select rows to delete.');
            return;
        }
        
        if (!confirm('Delete ' + $selected.length + ' selected row(s)?')) {
            return;
        }
        
        // Remove from rows array (in reverse order to maintain indices)
        var indices = [];
        $selected.each(function() {
            indices.push($(this).data('row'));
        });
        
        indices.sort(function(a, b) { return b - a; }).forEach(function(index) {
            rows.splice(index, 1);
        });
        
        // Remove from DOM
        $selected.remove();
        
        // Reindex remaining rows
        $('#acf-dt-editor tbody tr').each(function(index) {
            $(this).attr('data-row', index);
        });
        
        // Show empty message if no rows
        if (rows.length === 0) {
            var colspan = columns.length + 1;
            $('#acf-dt-editor tbody').append(
                '<tr class="acf-dt-empty-row"><td colspan="' + colspan + '">No data yet. Add rows above or import data.</td></tr>'
            );
        }
        
        // Uncheck select all
        $('#acf-dt-select-all').prop('checked', false);
        
        hasChanges = true;
    }
    
    /**
     * Show Context Menu
     */
    function showContextMenu(e, $row) {
        $('.acf-dt-context-menu').remove();
        
        var rowIndex = $row.data('row');
        
        var html = '<div class="acf-dt-context-menu" style="left: ' + e.pageX + 'px; top: ' + e.pageY + 'px;">';
        html += '<div class="acf-dt-context-menu-item" data-action="insert-above">Insert Row Above</div>';
        html += '<div class="acf-dt-context-menu-item" data-action="insert-below">Insert Row Below</div>';
        html += '<div class="acf-dt-context-menu-divider"></div>';
        html += '<div class="acf-dt-context-menu-item" data-action="duplicate">Duplicate Row</div>';
        html += '<div class="acf-dt-context-menu-divider"></div>';
        html += '<div class="acf-dt-context-menu-item destructive" data-action="delete">Delete Row</div>';
        html += '</div>';
        
        $('body').append(html);
        
        $('.acf-dt-context-menu-item').on('click', function(e) {
            e.stopPropagation();
            var action = $(this).data('action');
            handleContextMenuAction(action, $row, rowIndex);
            $('.acf-dt-context-menu').remove();
        });
    }
    
    /**
     * Handle Context Menu Action
     */
    function handleContextMenuAction(action, $row, rowIndex) {
        switch (action) {
            case 'insert-above':
                insertRow(rowIndex);
                break;
            case 'insert-below':
                insertRow(rowIndex + 1);
                break;
            case 'duplicate':
                duplicateRow(rowIndex);
                break;
            case 'delete':
                $row.find('.acf-dt-row-select').prop('checked', true);
                updateRowSelection();
                deleteSelectedRows();
                break;
        }
    }
    
    /**
     * Insert Row at Index
     */
    function insertRow(index) {
        var newRowData = {};
        columns.forEach(function(col) {
            newRowData[col.col_key] = '';
        });
        
        rows.splice(index, 0, newRowData);
        
        var html = '<tr data-row="' + index + '">';
        html += '<td class="acf-dt-select-col"><input type="checkbox" class="acf-dt-row-select"></td>';
        
        columns.forEach(function(col) {
            html += '<td><input type="text" class="acf-dt-cell" data-col="' + col.col_key + '" value=""></td>';
        });
        
        html += '</tr>';
        
        var $target = $('#acf-dt-editor tbody tr[data-row="' + index + '"]');
        if ($target.length) {
            $target.before(html);
        } else {
            $('#acf-dt-editor tbody').append(html);
        }
        
        // Reindex
        $('#acf-dt-editor tbody tr').each(function(i) {
            $(this).attr('data-row', i);
        });
        
        hasChanges = true;
    }
    
    /**
     * Duplicate Row
     */
    function duplicateRow(index) {
        var newRowData = $.extend({}, rows[index]);
        rows.splice(index + 1, 0, newRowData);
        
        var html = '<tr data-row="' + (index + 1) + '">';
        html += '<td class="acf-dt-select-col"><input type="checkbox" class="acf-dt-row-select"></td>';
        
        columns.forEach(function(col) {
            var value = newRowData[col.col_key] || '';
            html += '<td><input type="text" class="acf-dt-cell" data-col="' + col.col_key + '" value="' + escapeHtml(value) + '"></td>';
        });
        
        html += '</tr>';
        
        $('#acf-dt-editor tbody tr[data-row="' + index + '"]').after(html);
        
        // Reindex
        $('#acf-dt-editor tbody tr').each(function(i) {
            $(this).attr('data-row', i);
        });
        
        hasChanges = true;
    }
    
    /**
     * Save Data
     */
    function saveData() {
        var $btn = $('#acf-dt-save-data');
        var $status = $('#acf-dt-save-status');
        
        $btn.prop('disabled', true).text('Saving...');
        $status.text('').removeClass('success error');
        
        // Get current data from inputs
        loadRowsFromTable();
        
        $.ajax({
            url: acfDT.ajaxurl,
            type: 'POST',
            data: {
                action: 'acf_dt_save_data',
                nonce: acfDT.nonce,
                post_id: postId,
                columns: JSON.stringify(columns),
                rows: JSON.stringify(rows)
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Saved!').addClass('success');
                    hasChanges = false;
                    
                    setTimeout(function() {
                        $status.text('');
                    }, 3000);
                } else {
                    $status.text('Error: ' + response.data).addClass('error');
                }
            },
            error: function() {
                $status.text('Save failed').addClass('error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save Table Data');
            }
        });
    }
    
    /**
     * Import CSV
     */
    function importCSV() {
        var file = $('#acf-dt-csv-file')[0].files[0];
        if (!file) {
            alert('Please select a CSV file.');
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var csvData = e.target.result;
            
            $('#acf-dt-import-status').text('Importing...');
            
            $.ajax({
                url: acfDT.ajaxurl,
                type: 'POST',
                data: {
                    action: 'acf_dt_import_csv',
                    nonce: acfDT.nonce,
                    post_id: postId,
                    csv_data: csvData
                },
                success: function(response) {
                    if (response.success) {
                        $('#acf-dt-import-status').text(response.data.message);
                        location.reload();
                    } else {
                        $('#acf-dt-import-status').text('Error: ' + response.data);
                    }
                },
                error: function() {
                    $('#acf-dt-import-status').text('Import failed');
                }
            });
        };
        reader.readAsText(file);
    }
    
    /**
     * Import HTML
     */
    function importHTML() {
        var html = $('#acf-dt-html-input').val().trim();
        if (!html) {
            alert('Please paste HTML table code.');
            return;
        }
        
        $('#acf-dt-import-status').text('Importing...');
        
        $.ajax({
            url: acfDT.ajaxurl,
            type: 'POST',
            data: {
                action: 'acf_dt_import_html',
                nonce: acfDT.nonce,
                post_id: postId,
                html: html
            },
            success: function(response) {
                if (response.success) {
                    $('#acf-dt-import-status').text(response.data.message);
                    location.reload();
                } else {
                    $('#acf-dt-import-status').text('Error: ' + response.data);
                }
            },
            error: function() {
                $('#acf-dt-import-status').text('Import failed');
            }
        });
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Initialize on ready
    $(document).ready(init);
    
})(jQuery);
