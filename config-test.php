<?php
define ('SIMPLETEST_PAHT',        '/var/www/pagseguro/simpletest');
define ('JOOMLA_PATH',            '/var/www/joomla');
define ('VM_PAYMENT_MODULE_PATH', '/var/www/joomla/administrator/components/com_virtuemart/classes/payment');
define ('BASE_URL',               'http://localhost/joomla');
define ('JOOMLA_TEST_ADMIN_USER', 'admin');
define ('JOOMLA_TEST_ADMIN_PASS', '123123');

set_include_path (get_include_path().PATH_SEPARATOR.SIMPLETEST_PAHT.PATH_SEPARATOR.JOOMLA_PATH);

include ('configuration.php');
$config = new JConfig;

global $con;
$con = new mysqli($config->host, $config->user, $config->password, $config->db);
