<?php
include_once('cappobck.php');
include_once('dbconfigremoto.php');
/*
$fuente='sgcafact';
$fuente='sgcaf8co';
$fuente='sgcaf310';
*/
set_time_limit(30);
$fuente=$_GET['fuente'];
try
{
	$sqlremoto="update sgcaf100 set fechaactdatos='1000-01-01' limit 1";
	$resultr=$dbr->prepare($sqlremoto);
	if (($fuente=='sgcaf310') or ($fuente == 'sgcaf200') or ($fuente == 'sgcaf700'))
		$resultr->execute();
	echo $sqlremoto;

	$sqlr="truncate ".$fuente;
	$resultr=$dbr->prepare($sqlr);
	$resultr->execute();
	echo $sqlr.'<br>';

	$inicio=0;
	$cantidad=99;
	$seguir = 1;
	$procesados=0;

	while ($seguir == 1)
	{
		$sqll="select * from ".$fuente." limit $inicio, $cantidad";
 if ($fuente == 'sgcaf310')
  $sqll='select * from '.$fuente. " where stapre_sdp ='A' and renovado = 0 limit $inicio, $cantidad";
		$resultl=$dbl->prepare($sqll);

		$resultl->execute();
		// flush(); 
		// ob_flush();

		// echo $inicio . ' / '.$sqll.'<br>';
// echo $inicio.'/';
		$tiempo=$resultl->rowCount();
		if ($tiempo > 0)
		{
			set_time_limit($tiempo);
			// echo 'Registros a actualizar '.$tiempo;

			$datos = $resultl->fetch(PDO::FETCH_BOTH);
			// echo json_encode($datos);
			// print_r($datos);

			$limites=10;
			$cuenta = 0;
			while($datosl=$resultl->fetch(PDO::FETCH_BOTH)) 
			{
				$procesados++;
				$campos = $resultl->columnCount();
				if ($cuenta == 0) $comando="insert into ".$fuente." values ";
				$comando.='(';
				for ($i=0; $i<$campos; $i++) {
				//	$valor="datosl['".mysql_field_name($resultl,$i)."']";
					if ($datosl[$i] == '0000-00-00')
						$valor = "'1000-01-01'";
					else if (substr($datosl[$i],0,16) == '0000-00-00 00:00')
						$valor = "'1000-01-01 00:00'";
						else $valor="'".$datosl[$i]."'";
					$comando.="'".eliminar_tildes($valor)."'";
					$comando.=(($i+1)<$campos?', ':'');
				}
				$comando.='), ';
				$cuenta++;
				try
				{
					if ($cuenta == $limites)
					{
						$tamano=strlen(($comando))-2;
						$comando=substr($comando,0,$tamano);
		//				echo 'aqui '.$comando;
						$resultr=$dbr->prepare($comando);
						$resultr->execute();
						$cuenta=0;
						$comando='';
					}
				}
				catch(PDOException $e2)
				{
					echo 'comando '.$comando.'<br>';
					die('xx '.$e2->getMessage().$comando);
				}
			}
			if (strlen($comando) > 5)
			{
                              if (substr($comando,-2) != "')")
{
				$tamano=strlen(($comando))-2;
}
else $tamano=strlen($comando);
				$comando=substr($comando,0,$tamano);
				// echo 'final '.$comando.'<br>';
				try
				{
					$resultr=$dbr->prepare($comando);
					$resultr->execute();
				}
				catch(PDOException $e)
				{
					die('10 '.$e->getMessage().$comando );
				}
			}
		}
		else
			$seguir = 0;
		$inicio+=$cantidad;
	}
}
catch(PDOException $e)
{
	echo 'algo fallo';
	die($e->getMessage());
}
$sqlremoto="select now() as fecha";
$resultr=$dbr->prepare($sqlremoto);
$resultr->execute();
$rfila=$resultr->fetch(PDO::FETCH_ASSOC);
$fecha=$rfila['fecha'];

$sqlremoto="update sgcaf100 set fechaactdatos='$fecha' limit 1";
$resultr=$dbr->prepare($sqlremoto);
$resultr->execute();
echo 'Proceso total'.$procesados.'<br>';

echo 'Finalizado';

function eliminar_tildes($cadena){
 
    //Codificamos la cadena en formato utf8 en caso de que nos de errores
    $cadena = utf8_encode($cadena);
 
    //Ahora reemplazamos las letras
    $cadena = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä','Ã'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A'),
        $cadena
    );
 
    $cadena = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $cadena );
 
    $cadena = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $cadena );
 
    $cadena = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $cadena );
 
    $cadena = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $cadena );
 
    $cadena = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç','Ñ'),
        array('n', 'N', 'c', 'C','N'),
        $cadena
    );

    $cadena = str_replace(
        array('º','°','·','º','º',"'",'"'),
        array('.','.','.','.','.','',''),
        $cadena
    );
 
    return $cadena;
}

/*
update sgcaf200 set ultap_extr='1001-01-01' where ultap_extr is null;
update sgcaf200 set ultap_div='1001-01-01' where ultap_div is null;
update sgcaf200 set ultap_prof='1001-01-01' where ultap_prof is null;
update sgcaf200 set ultap_emp='1001-01-01' where ultap_emp is null;
update sgcaf200 set ultapm_extr=0 where ultapm_extr is null;
update sgcaf200 set ultapm_div=0 where ultapm_div is null;
update sgcaf200 set libre_prof=0 where libre_prof is null;
update sgcaf200 set ultapm_prof=0 where ultapm_prof is null;
update sgcaf200 set ultapm_emp=0 where ultapm_emp is null;

*/
?>
