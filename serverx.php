<?php
/*
$Usuarior="cappoucl_datos";
$Passwordr="t3wp0r@1";
$Servidorr="64.16.202.46";
$Servidorr="144.217.69.95";
$bddr="cappoucl_sica";
*/
$Usuarior="acceder";
$Passwordr="acceder";
$Servidorr="192.168.1.106";
$bddr="sica";
try
{
	echo 'Conectando remoto...<br>';
	$dbr=new PDO("mysql:host={$Servidorr};dbname={$bddr}", $Usuarior, $Passwordr);
	$dbr->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
	die($e->getMessage());
}
echo 'Sali de remoto...<br>';

?>

