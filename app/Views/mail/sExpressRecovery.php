<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Restablecer tu contraseña</title>
	<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e2e2e2;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-body {
            padding: 20px 20px 0 20px;
            color: #333333;
        }
        .email-body p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .email-footer {
            background-color: #f4f4f4;
            color: #666666;
            text-align: center;
            padding: 10px;
            font-size: 12px;
        }
        .email-footer a {
            color: #007bff;
            text-decoration: none;
        }
	</style>
</head>
<body>
<?php
	$company = 'Solve GCM';
	$name = isset($name) && $name !== '' ? $name: '';
	$code = isset($code) && $code !== '' ? $code: '50LV3E';
?>
<div class="email-container">
	<table width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; border-collapse: collapse; border-bottom: solid 15px #5c65d1">
		<tr>
			<td style="padding: 10px; text-align: center; background-color: #f9f9f9;">
				<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
					<tr>
						<td style="padding: 0; text-align: left; vertical-align: middle; width: 30%;">
							<img src="http://express.solvegcm.mx/assets/img/dark_logo.png" alt="Logo" style="height: 6rem; display: block; margin: 0 auto;">
						</td>
						<td style="padding: 0; text-align: center; vertical-align: middle; width: 70%;">
							<p style="margin: 0; font-size: 1.8rem; font-weight: bold; color: #333;">Restablecer tu contraseña</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="email-body">
		<p>Hola <?=$name?>,</p>
		<p>Recibimos una solicitud para restablecer tu contraseña. Si no realizaste esta solicitud, simplemente ignora este correo electrónico. De lo contrario, haz clic en el botón de abajo para continuar.</p>
		<div style="text-align: center; color: #f4f4f4; padding: 25px 2.5rem 25px 2.5rem">
			<h2 style="font-weight: bolder; letter-spacing: .6rem; background-color: #5c65d1; padding: 25px; margin: 0"><?=$code?></h2>
		</div>
		<p>Este enlace es válido por 12 horas.</p>
		<p>Si tienes alguna pregunta, no dudes en <a href="mailto:ayuda@solve.com.mx?Subject=Ayuda%20con%20recuperación%20de%20contraseña" target="_blank">contactar a 
		                                                                                                                                     nuestro 
		                                                                                                                          soporte</a>
		   .</p>
		<p>Saludos,<br>El equipo de <?=$company?></p>
	</div>
	<div class="email-footer">
		<p>&copy; <?=date ('Y', strtotime ('now')).' '.$company;?> . Todos los derechos reservados.</p>
	</div>
</div>
</body>
</html>