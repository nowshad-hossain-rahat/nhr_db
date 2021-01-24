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

$users = $db->new_table(TABLE_NAME_TO_CREATE);

// Now you have to add columns to this table.So to add columns :

$users->col("YOUR_COLUMN_NAME",DATA_TYPE,PRIMARY_KEY,NOT_NULL,UNIQUE);

// ** 1st and 2nd parameters are compulsary and others are optional.
    
// For DATA_TYPE :

>> $users->int(LENGTH);
>> $users->str(LENGTH);
>> $users->txt();
>> $users->dat();
>> $users->datime();
>> $users->bool();

For PRIMARY_KEY = true/false (default : false)
For NOT_NULL = true/false (default : false)
For UNIQUE = true/false (default : false)


// After adding columns you must call a function to create the table with these colums.

$users->create():

// To add columns after creating the table
// I mean,after calling the "create()" method

$users->add(ALL_THE_PARAMETERS_ARE_SAME_AS "col" FUNCTION);

// To drop any table

$users->drop("COLUMN_NAME");

// To drop all the columns or to drop the whole TABLE_NAME

$users->drop_all();

// ** NOTE : col,add,drop,drop_all these methods
// will return the "table object" $users in this case
// so you can do method chaining like

$users-col()->add->drop->drop_all();



