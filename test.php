<?php

use NhrDev\NHR_DB\NHR_DB;

require_once "./vendor/autoload.php";

$db = new NHR_DB([
  'user' => 'nowshad',
  'pass' => 'Password_/1234/',
  'dbname' => 'wordpress'
]);

$test_table = $db->table('test_table');

if (!$test_table->exists_column('email')) {
  $test_table->add("email", NHR_DB::str(), false, false, true, true );
}

print_r($test_table->get_columns());
