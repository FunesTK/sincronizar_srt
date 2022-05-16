<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <title>Sincronizar el archivo .srt</title>
    <style>
      body { text-align: center; padding: 20px; }
      h1 { font-size: 1.5em; }
      body { font: 1em Helvetica, sans-serif; color: #333; }
      article { display: block; text-align: left; width: 90%; margin: 0 auto; }
      a { color: #dc8100; text-decoration: none; }
      a:hover { color: #333; text-decoration: none; }
    </style>
</head>

<body>
    <article>
 

 <?php

$debug = 1;
$debug2 = 0;


// spanish.srt
$subtitles = "spanish.srt";

$eng_desde = "00:01:00,000 --> 00:01:30,000";
$spa_desde = "00:01:30,000 --> 00:02:00,000";

$eng_hasta = "00:10:00,000 --> 00:10:10,500";
$spa_hasta = "00:15:00,000 --> 00:15:15,000";



	// CALCULA PARÁMETROS
	$info = calcula_parametros($eng_desde,$eng_hasta,$spa_desde,$spa_hasta,$debug);
	$factor = $info[0];
	$desfase = $info[1];



/*
convertir a número y calcular la duración en inglés y en español 

calcular el factor = eng/spa (por ejemplo 0,9869)
multiplicar spa_desde por el factor
por último calcular el desfase = spa_desde - eng_desde

multiplicar todos los números de spanish.srt por el factor y sumar el desfase
convertir a texto

para comprobar, convertir $spa_desde y $spa_hasta
y ver si coinciden ambos con $eng_desde y $eng_hasta
*/


// INICIO
//
//

$archivo = $subtitles;

	$lineas = file($subtitles, FILE_IGNORE_NEW_LINES);
	for($i=0; $i < count($lineas)-1; $i++) { 

		$pos = strpos($lineas[$i], " --> ");

		if ($pos == false) {
		    echo "<br>" . $lineas[$i];
		} else {
			if ($debug2) echo "<br>" . $lineas[$i]; 
			echo "<br>" . new_line($lineas[$i],$factor,$desfase);
		}

	}

?>


    </article>
</body>
</html>



<?php

// DEFINE FUNCIONES
//
//

// calcula el factor y el desfase a partir de los tiempos de inicio y fin en los dos idiomas
// devuelve un array con los dos datos
function calcula_parametros($eng_desde,$eng_hasta,$spa_desde,$spa_hasta,$debug){

	$num_eng_desde = to_num($eng_desde);
	$num_eng_hasta = to_num($eng_hasta);

	$num_spa_desde = to_num($spa_desde);
	$num_spa_hasta = to_num($spa_hasta);

	$duracion_spa = $num_spa_hasta - $num_spa_desde;
	$duracion_eng = $num_eng_hasta - $num_eng_desde;

	if ($debug) {
		echo "<br>(debug = 0 para ocultar): ";
		echo "<br>" . $eng_desde . " = " . $num_eng_desde . ' --> '. to_text($num_eng_desde);
		echo "<br>" . $eng_hasta . " = " . $num_eng_hasta . ' --> '. to_text($num_eng_hasta);
		echo "<br>duración inglés = " . $duracion_eng . " segundos"; 
		echo "<br>———";
		echo "<br>" . $spa_desde . " = " . $num_spa_desde . ' --> '. to_text($num_spa_desde);
		echo "<br>" . $spa_hasta . " = " . $num_spa_hasta . ' --> '. to_text($num_spa_hasta);
		echo "<br>duración español = " . $duracion_spa . " segundos"; 
		echo "<br>———";
	}

	$factor = $duracion_eng / $duracion_spa;

	// calculamos los nuevos tiempos, a falta del desfase
	$num_spa_desde *= $factor;
	$num_spa_hasta *= $factor;

	// calculamos el desfase (debería ser el mismo al inicio y al final)
	$desfase = $num_eng_desde - $num_spa_desde;


	if ($debug) {
		echo "<br>Cálculo de parámetros (debug = 0 para ocultar): ";
		echo "<br>factor: " . $factor;
		echo "<br>desfase: " . $desfase;
		echo "<br>———";
		echo "<br>Nuevos tiempos en español:";
		echo "<br>" . $spa_desde . ' --> '. to_text($num_spa_desde+$desfase);
		echo "<br>" . $spa_hasta . ' --> '. to_text($num_spa_hasta+$desfase);
		echo "<hr>";
	}

	$info = array($factor,$desfase);
	return $info;


}


// to_num convierte el texto 'hh:mm:ss,sss' en un número para poder operar (factor, desfase) 
function to_num($texto){

	//"00:03:32,300"
	//=D20+C20*60+B20*3600

	$hh = substr($texto, 0, 2);
	$mm = substr($texto, 3, 2);
	$miliseconds = substr($texto, 6, 6);

	// cambia la coma de los milisegundos por un punto 
	$seconds = intval(substr($miliseconds, 0, 2));
	$miliseconds = intval(substr($miliseconds, 3, 3));
	$ss = $seconds . "." . $miliseconds;


	$num_tiempo = $ss + ($mm * 60) + ($hh * 3600);
	// para comprobar que funciona, saldrá algo como [39,265]
	// echo "[".$num_tiempo."]";
	return $num_tiempo;

}

// to_text convierte el formato numérico a hh:mm:ss,sss
function to_text($num){

	$hh = intval($num/3600);
	$mm = intval(($num-($hh*3600))/60);

	// intentamos leer los milisegundos
	$ss = $num-($hh*3600)-($mm*60);
	$int_secs = intval($ss);
	$mil_secs = intval(($ss-$int_secs)*1000);

	return str_pad($hh,2,"0",STR_PAD_LEFT).":".str_pad($mm,2,"0",STR_PAD_LEFT).":".str_pad($int_secs,2,"0",STR_PAD_LEFT).",".str_pad($mil_secs,3,"0",STR_PAD_LEFT);
}


// recibe como argumento el texto hh.mm.ss,sss y lo convierte a texto después de ajustarlo
function new_srt($texto, $factor, $desfase){
	$num_spa = to_num($texto) * $factor;
	$num_spa += $desfase;

	return to_text($num_spa); 
}

// recibe como argumento la línea con ' --> ' y devuelve la nueva línea
function new_line($texto, $factor, $desfase){
	$desde = substr($texto, 0, 12);
	$hasta = substr($texto, 17, 12);

	return new_srt($desde,$factor,$desfase) . ' --> ' . new_srt($hasta,$factor,$desfase);
} 

?>