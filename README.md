# CodeIgniter 4 DataTables Library
This library provides a simple and efficient way to implement server-side processing for DataTables in CodeIgniter 4 applications.



## Installation
You can install this library via Composer. Run the following command in your project directory:

```bash
composer require alifbint/codeigniter4-datatables
```


## Usage
### 1. Load the Library
In your controller, load the DataTables library and configure it as needed:
```php
<?php

namespace App\Controllers;

use \Raliable\DataTables\DataTables;
use CodeIgniter\API\ResponseTrait;

class DataTableController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
    	return view('datatable_view');
    }

    public function ajax_list()
    {
        $config = [
            'table' => 'your_table_name',
        ];

        $datatables = new DataTables($config);
        $datatables->select('id, name, address, email')
                   ->join('another_table', 'another_table.foreign_key = your_table_name.id', 'left')
                   ->where('status', 1)
                   ->orderBy('id', 'asc');

        return $datatables->generate();
    }
}
```

### 2. Configure Your View
Create a view file (e.g., `datatable_view.php`) to display the DataTable:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DataTables Example</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#example').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "<?= base_url('datatablecontroller/ajax_list') ?>",
                    "type": "POST",
                    "data": function (d) {
                        d.<?= csrf_token() ?> = "<?= csrf_hash() ?>";
                    }
                },
                "columns": [
                    { "data": "id", "bSortable": false, "searchable": false },
                    { "data": "name", "bSortable": true, "searchable": true },
                    { "data": "address", "bSortable": false, "searchable": true },
                    { "data": "email", "bSortable": true, "searchable": true }
                ]
            });
        });
    </script>
</head>
<body>
    <table id="example" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Email</th>
            </tr>
        </thead>
    </table>
</body>
</html>
```

### Methods
-   `select(string $columns)`: Select the columns to be returned.
-   `join(string $table, string $fk, string $type = null)`: Join another table.
-   `where(string $keyCondition, $val = null)`: Add a `where` clause.
-   `orWhere(string $keyCondition, $val = null)`: Add an `orWhere` clause.
-   `whereIn(string $keyCondition, array $val = [])`: Add a `whereIn` clause.
-   `orderBy($column, string $order = 'ASC')`: Add an `orderBy` clause.
-   `groupBy(string $groupBy)`: Add a `groupBy` clause.
-   `generate(bool $raw = false)`: Generate the DataTables response. If `raw` is `true`, returns an array, otherwise returns a JSON response.