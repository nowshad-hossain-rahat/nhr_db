# nhr_db

This PHP library will help you to create database connetion using PDO and creating tables,inserting,updating,fetching,deleting data from database without writing any SQL code.Just create an object of the "DB" class and then the power is yours! :)

# How to install :

```bash
[username@host]$ composer require nhrdev/nhr_db
```

# How to use :

- To connect

YOU MUST PASS AN ARRAY WITH THESE KEYS AND YOUR SPECIFIC VALUES
TO CONNECT TO THE DATABASE.

`driver` `host` `port` and `charset` are optional.

`driver` is set to `mysql` and
`host` is set to `localhost` by default

```php
use NhrDev\NHR_DB\DB;

$db = new DB(`DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`, [`DB_HOST`, `DB_PORT`, `DB_CHARACTERSET`]);
```

- To disconnect :

```php
$db->disconnect();
```

- To connect if disconnected :

```php
$db->connect();
```

- To check if connected or not :

```php
if($db->is_connected()){
    echo "Connected!";
}
```

- To create a new table :

```php
$table = $db->table( 'table_name' );

$table->id()
      ->int( 'id', 255 )
      ->unsigned_int( 'id', 255 )
      ->unsigned_bigint( 'id', 255 )
      ->col( 'id', DB::int(1), true )

      ->str( 'username', 255)
      ->col( 'username', DB::str(100) )

      ->text( 'details' )
      ->col( 'details', DB::text() )

      ->float( 'amount' )
      ->col( 'amount', DB::float() )

      ->enum( ['true', 'false'] )
      ->col( 'enum', DB::enum(['value1', 'value2']) )

      ->col( 'date', DB::date() )
      ->col( 'datetime', DB::datetime() )

      ->timestamp();

$table->create();
```

To add columns after creating the table, after calling the `create()` method

```php
$table->add(string $name, string $type_and_length, bool $is_primary = false, $is_auto_increment = false, bool $is_not_null = false, bool $is_unique = false);
```

- To drop any table

```php
$table->drop("COLUMN_NAME");
```

- To drop all the columns or to drop the whole `TABLE_NAME`

```php
$table->drop_all();
```

NOTE : `col`,`add`,`drop`,`drop_all` these methods will return the `table object` `$table`, so in this case so you can do method chaining like

```php
$table->col()->add->drop->drop_all();
```

- To insert row into the table

```php
$table->insert([
  'column_name' => 'value'
]);
```

- To get the last inserted id
  This will return false if no insertion is made.

```php
$table->last_insert_id()
```

- To update specific rows

```php
$table->update([
  'column_name' => 'value'
])->where("active", DB::eq(true))
  ->where("user", "=", "true")
  ->where("amount", DB::between(10, 2001))
  ->or("referrer", "=", -1)
  ->or("roll", DB::between(10, 59))
  ->where("username", DB::begins_like("ra"))
  ->execute();

Returns the number of rows affected or false on failure.
```

- To update all rows

```php
$table->update([
  'column_name' => 'value'
]);
```

- To delete specific rows

```php
$table->delete()
  ->where("active", DB::eq(true))
  ->where("user", "=", "true")
  ->where("amount", DB::between(10, 2001))
  ->or("referrer", "=", -1)
  ->or("roll", DB::between(10, 59))
  ->where("username", DB::begins_like("ra"))
  ->order_by('id', 'DESC')
  ->limit(5)
  ->execute();

Returns the number of rows affected or false on failure.
```

- To delete all rows

```php
$table->delete();
```

- To fetch rows

This function will return `\NhrDev\NHR_DB\Src\Result` object with some functions to access the fetched data.

```php
$rows = $table->select([], DB::OBJ)
  ->where("active", DB::eq('true'))
  ->or("amount", DB::between(10, 500))
  ->order_by("id", 'DESC')
  ->limit(1)
  ->offset(1)
  ->execute();
```

Here `DB::OBJ` for object `DB::ASSOC` for associative array and `DB::IND` for indexed array

- To get all the rows from the fetched data

```php
$rows->all();
```

- To get the first row

```php
$rows->first();
```

- To get the last row

```php
$rows->last();
```

- To loop through the rows

The second parameter is `false` by default. If you set this `true` then the loop will be in reverse order.

```php
$rows->each(function($row, $index){
    # your code
}, false);
```

- To get a single row by INDEX

This will return `false` if you pass an index less than `0` or greater than the number of rows fetched

```php
$rows->get(5);
```
