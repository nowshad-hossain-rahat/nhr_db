# nhr_db
This PHP library will help you to create database connetion using PDO and creating tables,inserting,updating,fetching,deleting data from database without writing any SQL code.Just create an object of the "DB" class and then the power is yours! :)

# How to use :
````php
$db = new DB([
        "driver"=>"YOUR_DRIVER_NAME",
        "host"=>"YOUR_HOST_NAME_OR_ADDRESS",
        "dbname"=>"YOUR_DATABASE_NAME",
        "port"=>"YOUR_HOST_PORT",
        "user"=>"YOUR_DATABASE_USERNAME",
        "pass"=>"YOUR_DATABASE_PASSWORD"
    ]);

/*
    YOU MUST PASS AN ARRAY WITH THESE KEYS AND YOUR SPECIFIC VALUES
    TO CONNECT TO THE DATABASE.
    
    "port" and "charset" are optional.
    
*/

# To disconnect :`

$db->disconnect();

# To connect if disconnected :

$db->connect();

# To check if connected or not :

if($db->is_connected()){
    echo "Connected!";
}

# To create a new table :

# creating table
$table = $db->table( 'table_name' );

$table->col( 'int', DB::int(1), true )
        ->col( 'varchar', DB::str(100) )
        ->col( 'text', DB::text() )
        ->col( 'float', DB::float() )
        ->col( 'enum', DB::enum(['value1', 'value2']) )
        ->col( 'date', DB::date() )
        ->col( 'datetime', DB::datetime() );
 
 $table->create();

// To add columns after creating the table, after calling the "create()" method

$table->add(ALL_THE_PARAMETERS_ARE_SAME_AS "col" FUNCTION);

// To drop any table
$table->drop("COLUMN_NAME");

// To drop all the columns or to drop the whole TABLE_NAME

$table->drop_all();

// ** NOTE : col,add,drop,drop_all these methods
// will return the "table object" $table, so in this case so you can do method chaining like

$table->col()->add->drop->drop_all();

// to insert row into the table
$table->insert([
            'column_name' => 'value'
        ]);
        
// to update row of the table
$table->update([
            'column_name' => 'value'
       ],
       [
            // conditions
            'id' => '0',
            'ORDER_BY' => 'id desc',
            'LIMIT' => '5'
       ]);

// to delete row
$table->delete([
            // conditions
            'column_name' => 'value'
        ]);
        
        
// to fetch rows
$table->fetch(['column_names_to_fetch'], [
            // conditions
            'column_name' => 'value'
        ], /* return type */ DB::OBJ|DB::ASSOC|DB::IND);

