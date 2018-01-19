<?php
$Servidorr="144.217.69.95";
$bddr="cappoucl_sica";
$Usuarior="cappoucl_datos";
$Passwordr="t3wp0r@1";
$Usuarior="cappoucl_nuevous";
$Passwordr="t3wp0r@1";
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

