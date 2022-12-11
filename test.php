<?php
use NhrDev\NHR_DB\NHR_DB;

require "./vendor/autoload.php";

$db = new NHR_DB([
  'user' => 'nowshad',
  'pass' => 'Password_/1234/',
  'dbname' => 'wordpress'
]);

echo $db->is_connected() ? "[+] Connected to DB!\n" : "";

$test_table = $db->table('test_table');

echo $test_table->drop_all() ? "[+] Table dropped!\n" : "[+] Failed to drop table!\n";

$test_table->id();
$test_table->str('username');
$test_table->str('password');
$test_table->timestamp();

if (!$test_table->exists())
  echo $test_table->create() ? "[+] Table `". $test_table->get_name() ."` created!\n" : "";

echo $test_table->exists() ? "[+] Table `" . $test_table->get_name() . "` exists!\n" : '';

if ($db->disconnect())
  echo "[+] Disconnected!\n";

?>
