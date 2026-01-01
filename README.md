# ACF Data Tables

A custom WordPress plugin for creating and managing data tables using ACF Pro. Features an Excel-like editing interface, CSV/HTML import, and shortcode embedding.

## Requirements

- WordPress 5.0+
- ACF Pro (Advanced Custom Fields Pro)
- PHP 7.4+

## Installation

1. Upload the `acf-data-tables` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Go to **Data Tables** in the admin menu

## Creating a Table

### Method 1: Manual Entry

1. Click **Add New Table**
2. Give it a title
3. Go to the **Columns** tab and define your columns:
   - **Key**: Internal identifier (no spaces)
   - **Label**: Display name
   - **Type**: Text, Number, Currency, Percentage, Link, Image URL, or HTML
   - **Align**: Left, Center, or Right
   - **Width**: Optional (e.g., `150px` or `20%`)
4. Use the **Excel-Style Editor** below to add data
5. Click **Save Table Data** then **Publish**

### Method 2: Import CSV

1. Create a new table
2. In the **Import Data** sidebar box, choose a CSV file
3. Click **Import CSV**
4. The first row becomes column headers, remaining rows become data
5. Review and save

### Method 3: Import HTML Table

1. Create a new table
2. In the **Import Data** sidebar box, paste an HTML `<table>` element
3. Click **Import HTML**
4. The plugin parses `<th>` as headers and `<td>` as data
5. Review and save

## Using the Shortcode

Basic usage:

```
[acf_table id="123"]
```

With custom class:

```
[acf_table id="123" class="my-custom-table"]
```

The shortcode is displayed in the table edit screen sidebar after publishing.

## Table Settings

- **First Row is Header**: Uses `<thead>` for the first row
- **Striped Rows**: Alternating row backgrounds
- **Hover Effect**: Highlight rows on mouse hover
- **Responsive**: Horizontal scroll on mobile
- **Enable Sorting**: Click headers to sort columns
- **Enable Search**: Adds a search box above the table
- **Custom CSS Class**: Add your own class for styling

## Column Types

| Type | Description |
|------|-------------|
| Text | Plain text (default) |
| Number | Formatted with commas (1,234) |
| Currency | Dollar format ($1,234.00) |
| Percentage | Adds % suffix (45.5%) |
| Link | Clickable URL |
| Image URL | Displays image |
| HTML | Renders raw HTML |

## Excel-Style Editor

The editor supports:

- **Tab**: Move to next cell
- **Shift+Tab**: Move to previous cell
- **Enter**: Move to cell below
- **Shift+Enter**: Move to cell above
- **Right-click**: Context menu with insert/duplicate/delete options
- **Checkbox selection**: Select multiple rows for bulk delete

## Styling

The plugin includes minimal default styles. Override with your theme CSS using these classes:

```css
/* Table wrapper */
.acf-dt-responsive-wrapper { }

/* Search input */
.acf-dt-search { }

/* Base table */
.acf-dt-table { }
.acf-dt-table th { }
.acf-dt-table td { }

/* Variants */
.acf-dt-striped { }
.acf-dt-hover { }
.acf-dt-sortable { }

/* Alignment */
.acf-dt-align-left { }
.acf-dt-align-center { }
.acf-dt-align-right { }
```

## Programmatic Access

Get table data in your theme:

```php
// Get columns
$columns = get_field('dt_columns', $table_id);

// Get rows
$rows = get_field('dt_rows', $table_id);

// Parse row data
foreach ($rows as $row) {
    $data = json_decode($row['row_data'], true);
    // Use $data['column_key']
}
```

## Changelog

### 1.0.0
- Initial release
- Custom post type for tables
- ACF-based data storage
- CSV and HTML import
- Excel-style admin editor
- Shortcode with sorting and search
- Responsive table support

## License

GPL v2 or later
