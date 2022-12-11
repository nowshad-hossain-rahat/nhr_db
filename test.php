<?php

use NhrDev\NHR_DB\NHR_DB;

require_once "./vendor/autoload.php";

$db = new NHR_DB([
  'user' => 'nowshad',
  'pass' => 'Password_/1234/',
  'dbname' => 'wordpress'
]);

$test_table = $db->table('test_table');
print_r($test_table->exists_column('usernam2e') ? "[+] Column exists!\n" : "[+] Column doesn't exists!\n");
