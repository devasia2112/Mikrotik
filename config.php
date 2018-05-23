<?php
ini_set("display_errors", true);
error_reporting(0); // E_ALL


$root = pathinfo($_SERVER['SCRIPT_FILENAME']);
define('PROTOCOL',   'http://');
define('DIRNAME',     basename($root['dirname'])); // 'basename', 'extension' and 'filename'
define('SERVER_ROOT', realpath(dirname(__FILE__)));
define('PUB_URL',     PROTOCOL . $_SERVER['HTTP_HOST'] . '/' . DIRNAME);



/*
* include classes
*   PDO class (medoo.class.php)
*   Mikrotik Class (RouterosAPI.class.php)
*
* O nome da classe precisa ser igual ao nome do arquivo para o 
* `spl_autoload_register()` funcionar de forma adequada.
*/
spl_autoload_register(function($class) {
    include 'class/' . $class . '.class.php';
});



/*
* start session
*/
if (php_sapi_name() !== 'cli')
{
   // checa versao do php para poder comparar status da sessao
   if (version_compare(phpversion(), '5.4.0', '>='))
   {
	  // PHP_SESSION_DISABLED = 0
	  // PHP_SESSION_NONE = 1
	  // PHP_SESSION_ACTIVE = 2
	  $ss = session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
	  if ($ss === FALSE ) session_start();
   }
   else
   {
	  $ss = session_id() === '' ? FALSE : TRUE;
	  if ($ss === FALSE ) session_start();
   }
}



/*
* inicializar PDO via medoo
* Veja arquivo: DOC
*/
$database = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'synet',
    'server'        => 'localhost',
    'username'      => 'synet',
    'password'      => 'nZQ4xw1EhruaEVTZ',
    'charset'       => 'utf8'
));