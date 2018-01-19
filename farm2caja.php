<?php
include_once('cappobck.php');
include_once('dbconfigremoto.php');
set_time_limit(30);
try
{
	$sqlremoto="select * from transitofarmacia where pr_prestamo = 0 order by id_consumo";
    // echo $sqlremoto;
//    $dbr->begintransaction();
//    $dbl->begintransaction();
	$resultr=$dbr->prepare($sqlremoto);
	$resultr->execute();
    $sqll="select now() as ahora";
    $resl = $dbl->prepare($sqll);
    $resl->execute();
    $ahora=$resl->fetch(PDO::FETCH_ASSOC);
    $ahora=$ahora['ahora'];
    $sqll="select cuent_pres from sgcaf360 where cod_pres = '004'";
    $resl = $dbl->prepare($sqll);
    $resl->execute();
    $cuenta1=$resl->fetch(PDO::FETCH_ASSOC); 
    $cuenta1 = $cuenta1['cuent_pres'];
    $cuentafarmacia = trim($cuenta1);

	while($datosr=$resultr->fetch(PDO::FETCH_BOTH)) 
	{
		$cedula=$datosr['cedula'];
        $cedula = substr($cedula,0,4).'.'.substr($cedula,4,3).'.'.substr($cedula,7,3);
		$codigo=$datosr['codigo'];
        $monto = $datosr['monto'];
        $fecha = $datosr['fecha_consumo'];
        $farmacia = $datosr['farmacia'];
        $cuenta_caja = $datosr['cuenta_caja'];
        $id_consumo = $datosr['id_consumo'];
        $cuenta_caja = $datosr['cuenta_caja'];
		$sqll="select * from sgcaf310 where cedsoc_sdp='$cedula' and codpre_sdp='004' and stapre_sdp='A' and (renovado = 0)";
        $resl = $dbl->prepare($sqll);
		$resl->execute();
        // echo $sqll;
        if ($resl->rowCount() > 0)
        {
            echo 'existe <br>';
            $dato = $resl->fetch(PDO::FETCH_ASSOC); 
            $numero = $dato['nropre_sdp'];
            $sqll="update sgcaf310 set monpre_sdp = monpre_sdp + ".$monto." where nropre_sdp = '".$numero."'";
            $resl = $dbl->prepare($sqll);
    		$resl->execute();
            echo 'termine existe <br>';
        }
        else
        {
            echo 'nuevo <br>';
	        $sql_acta="select * from sgcafact where especial = 0 order by fecha desc limit 1";
	        $las_actas=$dbl->prepare($sql_acta);
            $las_actas->execute();
	        $el_acta=$las_actas->fetch(PDO::FETCH_BOTH); 
            $fechaacta = $el_acta['fecha'];
	        $primerdcto=($el_acta['f_dcto']);
	        $sql="select date_sub('".$primerdcto.",INTERVAL 1 DAY) as fecha";
	        $rsql=$dbl->prepare($sql);
	        $asql=$rsql->fetch(PDO::FETCH_BOTH); 
	        $primerdcto=($asql['fecha']);
        	$elnumero=numero_prestamo($micedula, $codigo, $dbl);

        	$sqll="insert into sgcaf310 (codsoc_sdp, cedsoc_sdp, nropre_sdp, codpre_sdp, f_soli_sdp, f_1cuo_sdp, monpre_sdp, monpag_sdp, nrofia_sdp, 
                stapre_sdp, tipo_fianz, cuota, nrocuotas, interes_sd, cuota_ucla, netcheque, nro_acta, fecha_acta, ip, inicial, intereses, quien) 
            values  ('$codigo', '$cedula', '$elnumero','004','$fecha', '$fecha', $monto, 0, 0, 'A', '',$monto, 1, 0, $monto, $monto, '$nroacta', 
                '$fechaacta', 'localhost', 0, 0, '')";
            $resl = $dbl->prepare($sqll);
    		$resl->execute();
            echo 'termine nuevo<br>';
        }
        $b = $elasiento = substr($fecha,0,10);
        $elasiento=explode('-', $elasiento);
        $elasiento=substr($elasiento[0],2,2).$elasiento[1].$elasiento[2].$codigo;
        echo "Generando encabezado contable <strong>$elasiento </strong> <br>";
        $sqll = "INSERT INTO sgcaf830 (enc_clave, enc_fecha, enc_desco, enc_desc1, enc_debe, enc_haber, enc_item, enc_dif, enc_igual, enc_refer, enc_sw, enc_explic) VALUES ('$elasiento', '$b', 'Prestamo Farmacia $farmacia','',0,0,0,0,0,0,0,'')"; 
        $resl = $dbl->prepare($sqll);
        $resl->execute();
        $cuenta1 = $cuentafarmacia.'-'.substr($codigo,1,4);
        agregar_f820($elasiento, $b, '+', $cuenta1, 'Prestamo Farmacia '.$farmacia.' '.$fecha, $monto, 0, 0,'cron',0,$id_consumo,'','S',0, $dbl); 
        echo 'registre cargo<br>';
        $cuenta1=$cuenta_caja;
        agregar_f820($elasiento, $b, '-', $cuenta1, 'Prestamo Farmacia '.$farmacia.' '.$fecha, $monto, 0, 0,'cron',0,$id_consumo,'','S',0, $dbl); 
        echo 'registre abono<br>';
        $sqlr="update transitofarmacia set pr_prestamo = 1, fecha_prestamo = '$ahora' where id_consumo = :id_consumo";
        echo 'registro '.$id_consumo.'<br>';
        $resr = $dbr->prepare($sqlr);
        // echo $id_consumo.'/'.$sqlr; 
        $prueba ='No';
        if ($prueba == 'No')
      		$resr->execute(array(
                ":id_consumo"=>$id_consumo,
                ));
        //$dbl->commit();
        // $dbr->commit();
	}
}
catch(PDOException $e)
{
	echo 'algo fallo '.$sqll.'<br>'.$sqlr.'<br>';
    $dbr->rollback();
    $dbl->rollback();
	die($e->getMessage());
}
/*
$sqlremoto="select now() as fecha";
$resultr=$dbr->prepare($sqlremoto);
$resultr->execute();
$rfila=$resultr->fetch(PDO::FETCH_ASSOC);
$fecha=$rfila['fecha'];

$sqlremoto="update sgcaf100 set fechaactdatos='$fecha' limit 1";
$resultr=$dbr->prepare($sqlremoto);
$resultr->execute();
echo 'Proceso total'.$procesados.'<br>';
*/
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

function numero_prestamo($micedula, $laparte, $dbl)
{
	$sql_310="select count(nropre_sdp) as cantidad from sgcaf310 where (cedsoc_sdp='$micedula') group by cedsoc_sdp";
	$a_310=$dbl->prepare($sql_310);
    $a_310->execute();
	$elnumero=$a_310->fetch(PDO::FETCH_ASSOC);
	$elnumero=$elnumero['cantidad'];
echo 'este es el '.$elnumero.'<br>';
	$buscarhasta=400;
	for ($unnumero=$elnumero; $unnumero < $buscarhasta; $unnumero++)
	{
		$elnumero=$elnumero+1;
		$lnumero=$laparte.ceroizq($elnumero,3);
		echo 'Buscando '.$lnumero;
		$sql_310="select * from sgcaf310 where (nropre_sdp='$lnumero')";
        $a_310->execute();
		$a_310=$dbl->prepare($sql_310);
		if (($a_310->rowCount() < 1))
			$unnumero = $buscarhasta+1;
//		else echo ' ya existe<br>';
	}
	$elnumero=$lnumero;
	// fin de generar nuevo numero
	return $elnumero;
}

function ceroizq($laultima,$digitos)
{
	$tamano=$digitos-strlen($laultima);
	$nuevacadena="";
	// echo $tamano;
	// (5-$tamano)=$posicion)
	for ($posicion=1;$posicion <= $tamano;$posicion++) {
		$nuevacadena=$nuevacadena."0"; 
		// echo $nuevacadena."-";
		}
		// echo $nuevacadena."---------".$laultima;
	$nuevacadena=$nuevacadena.$laultima;
	// echo $nuevacadena;
	return $nuevacadena;
		
}


function agregar_f820 ($pcom_nrocom, $pcom_fecha, $pcom_debcre, $pcom_cuenta, $pcom_descri, $elmonto, $pcom_monto2, $pcom_monto, $pcom_ip, $pcom_nroite, $pcom_refere, $pcom_tipmov, $agregar, $registro, $db_con)
{
    $pcom_monto1 = $pcom_monto2 = 0;
    if (($pcom_debcre =='+') or ($pcom_debcre == '1') or ($pcom_debcre == on)) 
        { $pcom_monto1=$elmonto; $pcom_debcre = '+';}
        else { $pcom_debcre= '-';  $pcom_monto2 = $elmonto;} 
    if ($agregar == 'S') {
        $elsql="INSERT INTO sgcaf820 (
com_nrocom, com_fecha, com_debcre, com_cuenta, com_descri, com_monto1, com_monto2, com_monto, com_ip, com_nroite, com_refere, com_tipmov, cobrado, fecha_cobro) VALUES (
'$pcom_nrocom', '$pcom_fecha', '$pcom_debcre', '$pcom_cuenta', '$pcom_descri', '$pcom_monto1', '$pcom_monto2', '$pcom_monto', '$pcom_ip', '$pcom_nroite', '$pcom_refere', '$pcom_tipmov', 0, '1001-01-01')"; 
//      $elsql="call sp_inc_r_820 (
// '$pcom_nrocom', '$pcom_fecha', '$pcom_debcre', '$pcom_cuenta', '$pcom_descri', '$pcom_monto1', '$pcom_monto2', '$pcom_monto', '$pcom_ip', '$pcom_nroite', '$pcom_refere', '$pcom_tipmov')";  
}
    else if ($agregar == 'N') {
            $elsql="UPDATE sgcaf820 SET com_debcre='$pcom_debcre', com_cuenta='$pcom_cuenta', com_descri='$pcom_descri', com_monto1='$pcom_monto1', com_monto2='$pcom_monto2', com_ip='$pcom_ip', com_nroite='$pcom_nroite', com_refere='$pcom_refere', com_tipmov='$pcom_tipmov' WHERE nro_registro=$registro"; 
        }
        else {
            $elsql="DELETE FROM sgcaf820 WHERE nro_registro = $registro";
            }
    $rs=$db_con->prepare($elsql);
  // echo $elsql.'<br>';
    $res=$rs->execute();
    if (!$res) die ("<p />Estimado usuario $usuario contacte al administrador Código 820-1- <br><br>".$elsql);
// $final = explode(" ", microtime());
// $tiempo = ($final[1] + $final[0]) - ($comienzo[1] - $comienzo[0]); 
// echo "comando ejecutado en $tiempo segundos";
    
    $elsql="SELECT SUM(com_monto1) as debe, SUM(com_monto2) AS haber, COUNT(com_nrocom) as items FROM sgcaf820 WHERE com_nrocom='$pcom_nrocom'";
    $rs=$db_con->prepare($elsql);
    $res=$rs->execute();
    // echo $elsql.'<br>';
    if (!$res) die ("<p />Estimado usuario $usuario contacte al administrador Código 830-1");
    $fila = $rs->fetch(PDO::FETCH_ASSOC);
    if ($rs->rowCount() > 0) 
    {
        $elsql="UPDATE sgcaf830 SET enc_debe='$fila[debe]', enc_haber='$fila[haber]', enc_item='$fila[items]',enc_fecha='$pcom_fecha' WHERE enc_clave = '$pcom_nrocom'";
            $rs=$db_con->prepare($elsql);
            $res=$rs->execute();
        // echo $elsql;
            if (!$res) die ("<p />Estimado usuario $usuario contacte al administrador Código 830-2<br>".$sql);
    }
    // actualizar los niveles en la 810
    $losniveles = "SELECT * FROM sgcafniv order by con_nivel"; 
    $rs=$db_con->prepare($losniveles);
    $res=$rs->execute();
//  echo $losniveles;
    if ($rs->rowCount() == 0) 
    {
        die("<p /><br /><p />No se han definido los niveles<span class='b'> error Niv-1</span> en la tabla");
        exit;
    }
    
    $elmes=strtotime($pcom_fecha);
    $elmes=date("m", $elmes);
    $primero=strlen($elmes);
//  echo $pcom_fecha.'-'.$elmes . '-'.$primero;
    if (($elmes < 10) and ($primero < 2)) $elmes='0'.$elmes;
    $losniveles=$rs->fetchall();
    for ($i = count($losniveles) - 1; $i >= 0; $i--) {
/*
        if (!mysql_data_seek($losniveles, $i)) {
            echo "Cannot seek to row $i: " . mysql_error() . "\n";
            continue;
        }
        if (!($niveles = mysql_fetch_assoc($losniveles))) {
            continue;
        }
*/

        // $fila = $niveles ;
        $elnivel=$losniveles[$i]['con_nivel'];
        $codigo=substr($pcom_cuenta,0,$elnivel);
        $debito='cue_deb'.$elmes;
        $credito='cue_cre'.$elmes;
        $eldebe=$pcom_monto1;
        $elhaber=$pcom_monto2;
        $sql="update sgcaf810 set $debito=$debito+'$eldebe', $credito=$credito+'$elhaber' where cue_codigo='$codigo'";
        $result = $db_con->prepare($sql);
        $result=$result->execute();
        if (!$result)
         die('Error en la F810-3 '.$sql.' '.mysql_error());         
     //   echo $sql."<br>";
    }
}

function procese($elmes,$niveles)
{
//  $sql="select com_cuenta, com_debcre, sum(com_monto1) as debe, sum(com_monto2) as haber from sgcaf820 where month(com_fecha)=$elmes group by com_cuenta order by com_cuenta";
    $sql="select fech_ejerc from sgcaf100 limit 1";
    $result=mysql_query($sql);
    $registro=mysql_fetch_assoc($result);
    $ano=$registro['fech_ejerc'];
    $ano=explode('-',$ano);
    $ano=$ano[0];
    $numero=$niveles['con_nivel'];
    // chequeo de fallas
    if ($elmes < 10)
        $mimes='0'.$elmes;
    else $mimes=$elmes;
    $sql="select com_nrocom, sum(com_monto1) as debe, sum(com_monto2) as haber,sum(com_monto1)-sum(com_monto2) as diferencia from sgcaf820 where substr(com_fecha,1,7)='$ano-$mimes' and length(trim(com_cuenta))=length('5-07-01-06-01-01-0001') group by com_nrocom order by sum(com_monto1)-sum(com_monto2) desc";
//  echo $sql;
    echo '<br>';
    $hay=false;
    $result = mysql_query($sql) or die('Error en la F820-4'.$sql.' '.mysql_error()); 
    while ($fila = mysql_fetch_assoc($result)) {
//      echo substr(trim($fila['diferencia']),1,4).'<br>';
        if (substr(trim($fila['diferencia']),0,4)!= '0.00') {
            echo "<strong><a target=\"_blank\" href='editasi2.php?asiento=".$fila['com_nrocom']."'>".$fila['com_nrocom']."</a></strong> <br>";
            echo $fila['debe'].' '.$fila['haber'].' '.$fila['diferencia'].' '.'<br>';
            $hay=true;
        }
    }
    if ($hay == true) {
        die('<h1>Revisar los comprobantes anteriores que tienen inconveniente</h1>');
    }

    $sql="select left(com_cuenta,$numero) as reducido, com_cuenta, com_debcre, sum(com_monto1) as debe, sum(com_monto2) as haber from sgcaf820 where month(com_fecha)=$elmes and year(com_fecha)=$ano group by reducido order by reducido";
//  echo $sql;
    $result = mysql_query($sql) or die('Error en la F820-3 '.$sql.' '.mysql_error()); 
    if (mysql_num_rows($result) == 0) {
        echo "<p /><br /><p />No existen movimientos en el mes <span class='b'>$elmes</span> revisar";
        exit;
    }
//  echo mysql_num_rows($result)."<br>";
    set_time_limit(mysql_num_rows($result));
    $ValorTotal=mysql_num_rows($result);
    $cuantos=0;
//  echo "<div id='progreso' style='position:relative; padding:0px;width:850px;height:20px;left:25px;'>";
    while ($fila = mysql_fetch_assoc($result)) {
        calcule_810($fila,$niveles,$elmes);
/*
        echo "<div style='float:left;margin:5px 0px 0px 1px;width:2px;height:12px;background:red;color:red;'> </div>";;
        flush();
        ob_flush();
*/
        $cuantos++;
        $porcentaje = $cuantos * 100 / $ValorTotal; //saco mi valor en porcentaje
        echo "<script>callprogress(".round($porcentaje).")</script>"; //llamo a la función JS(JavaScript) para actualizar el progreso
//      echo $porcentaje.'<br>';
        flush(); //con esta funcion hago que se muestre el resultado de inmediato y no espere a terminar todo el bucle con los 25 registros para recien mostrar el resultado
        ob_flush();
    }
    echo "</div>";
/*
    echo "<script>";
//  echo "document.getElementById('progreso').style.displaye='none';";

    echo "</script>";
*/
}

function calcule_810($registro,$niveles,$elmes)
{
    $elcodigo=$registro['com_cuenta'];
    if ($elmes < 10) $elmes='0'.$elmes;
    // regreso al bof niveles
//  $filas=mysql_num_rows($niveles);
//  mysql_data_seek($niveles, 0);
//  while ($fila = mysql_fetch_assoc($niveles)) 
    $fila = $niveles ;
    {
        $elnivel=$fila['con_nivel'];
        $codigo=substr($elcodigo,0,$elnivel);
        $debito='cue_deb'.$elmes;
        $credito='cue_cre'.$elmes;
        $eldebe=$registro['debe'];
        $elhaber=$registro['haber'];
        $sql="select cue_codigo from sgcaf810 where cue_codigo='$codigo'";
//      echo $sql;
        $result = mysql_query($sql) or die('Error en la F810-4 '.$sql.' '.mysql_error());       
        if ((mysql_num_rows($result) < 1) and (strlen(trim($codigo)) > 16)) // and ($codigo != '')  and ($elnivel = '7') 
        {   // no existe la cuenta y la creo
            // busco el socio
            $socio=explode('-',$codigo);
            $socio='0'.$socio[6];
            $sql="select ape_prof, nombr_prof from sgcaf200 where cod_prof='$socio'";

// 1-01-02-01-08-08-0249
//          echo $sql;
            $result = mysql_query($sql) or die('Error en la F200-1 '.$sql.' '.mysql_error()); 
            $rsocio=mysql_fetch_assoc($result);
            $nombre=trim($rsocio['ape_prof']). ' '.$rsocio['nombr_prof'];
            $sql="insert into sgcaf810 (cue_codigo, cue_nombre, cue_nivel, cue_saldo) values ('$codigo', '$nombre', '7', 0)";
//          echo $sql;
            $result = mysql_query($sql) or die('Error en la F810-5 '.$sql.' '.mysql_error()); 
        }
        $sql="update sgcaf810 set $debito=$debito+'$eldebe', $credito=$credito+'$elhaber' where cue_codigo='$codigo'";
        $result = mysql_query($sql) or die('Error en la F810-3 '.$sql.' '.mysql_error());       
        $sql="select cue_codigo from sgcaf810 where cue_codigo='$codigo'";
        $result = mysql_query($sql) or die('Error en la F810-4 '.$sql.' '.mysql_error()); 
        if (mysql_num_rows($result) < 1)
            echo $sql.'<br>';
    }
//  echo $codigo;
}


?>
