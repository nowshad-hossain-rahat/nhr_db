# nhr_db
This PHP library will help you to create database connetion using PDO and creating tables,inserting,updating,fetching,deleting data from database without writing any SQL code.Just create an object of the "DB" class and then the power is yours! :)

# How to use :

* To connect

YOU MUST PASS AN ARRAY WITH THESE KEYS AND YOUR SPECIFIC VALUES
TO CONNECT TO THE DATABASE.

"port" and "charset" are optional.

```php
use Nowshad\DB;

$db = new DB([
        "driver"=>"YOUR_DRIVER_NAME",
        "host"=>"YOUR_HOST_NAME_OR_ADDRESS",
        "dbname"=>"YOUR_DATABASE_NAME",
        "port"=>"YOUR_HOST_PORT",
        "user"=>"YOUR_DATABASE_USERNAME",
        "pass"=>"YOUR_DATABASE_PASSWORD"
    ]);
```

* To disconnect :

```php
$db->disconnect();
```
* To connect if disconnected :

```php
$db->connect();
```

* To check if connected or not :

```php
if($db->is_connected()){
    echo "Connected!";
}
```

* To create a new table :

```php
$table = $db->table( 'table_name' );

$table->col( 'int', DB::int(1), true )
        ->col( 'varchar', DB::str(100) )
        ->col( 'text', DB::text() )
        ->col( 'float', DB::float() )
        ->col( 'enum', DB::enum(['value1', 'value2']) )
        ->col( 'date', DB::date() )
        ->col( 'datetime', DB::datetime() );

$table->create();
```

To add columns after creating the table, after calling the "create()" method

```php
$table->add(ALL_THE_PARAMETERS_ARE_SAME_AS "col" FUNCTION);
```

* To drop any table

```php
$table->drop("COLUMN_NAME");
```

* To drop all the columns or to drop the whole TABLE_NAME

```php
$table->drop_all();
```

NOTE : col,add,drop,drop_all these methods will return the "table object" $table, so in this case so you can do method chaining like

```php
$table->col()->add->drop->drop_all();
```

* To insert row into the table

```php
$table->insert([
            'column_name' => 'value'
        ]);
```

* To get the last inserted id
This will return false if no insertion is made.

```php
$table->last_insert_id()
```

* To update row of the table

```php
$table->update([
            'column_name' => 'value'
       ],
       [
            // conditions
            'id' => '0',
            'ORDER_BY' => 'id desc',
            'LIMIT' => '5'
       ]);
```

* To delete row
```php
$table->delete([
            // conditions
            'column_name' => 'value'
        ]);
```

* To fetch rows
```php
$table->fetch(['column_names_to_fetch'], [
            // conditions
            'column_name' => 'value'
        ], /* return type */ DB::OBJ|DB::ASSOC|DB::IND);
```

