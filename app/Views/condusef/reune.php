<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<?php date_default_timezone_set ( 'America/Mexico_City' ) ?>
<html lang="es">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<title>Acuse de recibo</title>
</head>
<body>
<?php
	$folio = $folio ?? '';
	$dateTime = $dateTime ?? '';
	$empresa = $empresa ?? '';
	$months = [
		'Enero',
		'Febrero',
		'Marzo',
		'Abril',
		'Mayo',
		'Junio',
		'Julio',
		'Agosto',
		'Septiembre',
		'Octubre',
		'Noviembre',
		'Diciembre' ];
?>
<style type="text/css">
    .membrete {
        text-align: right;
        font-stretch: semi-condensed;
        line-height: .7em;
        font-weight: bold;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
    }

    #empresa {
        font-weight: bold;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
        text-align: left;
	    margin-top: 58px;
	    margin-bottom: 35px;
    }
    #body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
	    text-align: justify;
    }
    li{
	    line-height: 27px;
    }
</style>
<div class="container">
	<div class="membrete">
		<p>DIRECCIÓN GENERAL DE VERIFICACIÓN Y SANCIONES</p>
		<p>LA INSTITUCIÓN FINANCIERA VALIDÓ LOS DATOS DE LA UNE</p>
		<p>Proceso: VALIDACIÓN DE LA INFORMACIÓN DE DATOS REGISTRADOS DE LA UNE</p>
		<p>Periodo de validación: <?= $months[ intval ( date ( 'm' ) ) - 1 ].date ( ' - Y' ) ?></p>
		<p>Folio: <?= $folio ?></p>
		<p>Fecha y hora de emisión: <?= $dateTime ?></p>
	</div>
	<div id="empresa"><?= $empresa ?></div>
	<div id="body">
		<p>Se hace constar que la Intitución Financiera realizó la validación de los datos de la UNE en el REUNE, de
		   conformidad con el artículo 50 Bis de
		   la Ley de Protección y Defensa al Usuario de Servicios Financieros.</p>
		<br>
		<p>Para tal efecto, la Institución Financiera validó:</p>
		<ul>
			<li>Los datos registrados de la UNE,</li>
			<li>Medios de recepción o canal y</li>
			<li>Niveles de atención o contacto, registrados en el REUNE, en la sección “REUNE”</li>
		</ul>
		<p>La información validada es responsabilidad absoluta de la Institución Financiera, la cual podrá ser
		   verificada en cualquier momento por esta Comisión Nacional.</p>
		<p>Con lo anterior se da cumplimiento a lo establecido en los artículos 12, fracción III, 64 y 66, de la
		   Disposición en materia de Registros ante la CONDUSEF.</p>
		<p>El presente acuse se expide en términos del artículo 69-C, de la Ley Federal de Procedimiento
		   Administrativo, aplicable de conformidad con el artículo 1, tercer párrafo del mismo ordenamiento legal,
		   así como del artículo 9 de la Disposición en materia de registros ante la CONDUSEF y en relación con el
		   contenido de la Carta responsiva de representante y/o  apoderado legal de la institución financiera para el
		   uso de la Clave de Identidad CONDUSEF (CIC).</p>
		<p>Los datos personales proporcionados están protegidos conforme lo dispuesto en la Ley General de Protección
		   de Datos Personales en Posesión de Sujetos Obligados.</p>
	</div>
</div>
</body>
</html>