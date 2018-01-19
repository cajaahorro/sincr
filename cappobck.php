<?php
$Usuariol="jhernandez";
$Passwordl="nene14";
$Servidorl="192.168.1.39";
$bddl="sica";
try
{
	echo 'Conectando local...<br>';
	$dbl=new PDO("mysql:host={$Servidorl};dbname={$bddl}",$Usuariol, $Passwordl);
	$dbl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
	die($e->getMessage());
}
echo 'Sali de local...<br>';

?>

