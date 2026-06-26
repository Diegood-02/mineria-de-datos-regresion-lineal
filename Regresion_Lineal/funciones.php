<?php
function calcular_regresion(array $datos): array {
    $base = ['b0'=>0,'b1'=>0,'r2'=>0,'r'=>0,'mse'=>0,'rmse'=>0,'n'=>count($datos),
             'y_media'=>0,'x_media'=>0,'sxx'=>0,'se'=>0];
    $n = count($datos);
    if ($n < 2) return $base;

    $sx = $sy = $sxy = $sx2 = 0;
    foreach ($datos as $d) {
        $x = (float)$d['inversion'];
        $y = (float)$d['ventas'];
        $sx += $x; $sy += $y; $sxy += $x * $y; $sx2 += $x * $x;
    }
    $sxx_total = $n * $sx2 - $sx * $sx;
    if ($sxx_total == 0) return $base; // todos los X iguales: pendiente indefinida
    $b1 = ($n * $sxy - $sx * $sy) / $sxx_total;
    $b0 = ($sy - $b1 * $sx) / $n;

    $x_media = $sx / $n;
    $y_media = $sy / $n;
    $sxx     = $sxx_total / $n;          // Σ(x - x̄)²
    $ss_tot  = $ss_res = 0;
    foreach ($datos as $d) {
        $y     = (float)$d['ventas'];
        $y_est = $b0 + $b1 * (float)$d['inversion'];
        $ss_tot += ($y - $y_media) ** 2;
        $ss_res += ($y - $y_est) ** 2;
    }
    $r2   = $ss_tot > 0 ? 1 - $ss_res / $ss_tot : 1;
    $mse  = $ss_res / $n;
    $rmse = sqrt($mse);
    $r    = ($b1 < 0 ? -1 : 1) * sqrt(max(0, $r2)); // signo de la pendiente
    $se   = $n > 2 ? sqrt($ss_res / ($n - 2)) : 0;   // error estandar de la estimacion

    return ['b0'=>$b0,'b1'=>$b1,'r2'=>$r2,'r'=>$r,'mse'=>$mse,'rmse'=>$rmse,
            'n'=>$n,'y_media'=>$y_media,'x_media'=>$x_media,'sxx'=>$sxx,'se'=>$se];
}

// Devuelve el desglose por registro: Y real, Y estimado, residuo y bandera de outlier.
function calcular_residuos(array $datos, float $b0, float $b1): array {
    $res = [];
    if (empty($datos)) return $res;

    $errores = [];
    foreach ($datos as $d) {
        $x     = (float)$d['inversion'];
        $y     = (float)$d['ventas'];
        $y_est = $b0 + $b1 * $x;
        $e     = $y - $y_est;
        $errores[] = $e;
        $res[] = ['mes'=>$d['mes'] ?? '', 'x'=>$x, 'y_real'=>$y,
                  'y_est'=>$y_est, 'residuo'=>$e, 'es_outlier'=>false];
    }

    // Outlier = residuo a mas de 2.5 desviaciones estandar de los residuos.
    $m = count($errores);
    if ($m >= 3) {
        $media = array_sum($errores) / $m;
        $var   = array_sum(array_map(fn($e) => ($e - $media) ** 2, $errores)) / $m;
        $desv  = sqrt($var);
        if ($desv > 0) {
            foreach ($res as $i => $fila) {
                if (abs($fila['residuo'] - $media) > 2.5 * $desv) {
                    $res[$i]['es_outlier'] = true;
                }
            }
        }
    }
    return $res;
}

// Valor critico t de Student (dos colas, 95%) para df pequenos; fallback 1.96 (normal).
function valor_t(int $df): float {
    $tabla = [1=>12.706, 2=>4.303, 3=>3.182, 4=>2.776, 5=>2.571, 6=>2.447,
              7=>2.365, 8=>2.306, 9=>2.262, 10=>2.228, 11=>2.201, 12=>2.179,
              13=>2.160, 14=>2.145, 15=>2.131, 16=>2.120, 17=>2.110, 18=>2.101,
              19=>2.093, 20=>2.086, 21=>2.080, 22=>2.074, 23=>2.069, 24=>2.064,
              25=>2.060, 26=>2.056, 27=>2.052, 28=>2.048, 29=>2.045, 30=>2.042];
    if ($df < 1)            return 12.706;
    return $tabla[$df] ?? 1.96;
}

// Intervalo de prediccion al 95% para un valor X dado.
// y_est ± t * se * sqrt(1 + 1/n + (x - x̄)² / (n * Sxx))
function banda_prediccion(float $x, array $reg): array {
    $n = $reg['n'];
    $y_est = $reg['b0'] + $reg['b1'] * $x;
    if ($n < 3 || $reg['se'] <= 0 || $reg['sxx'] <= 0) {
        return ['y_est'=>$y_est, 'y_inf'=>$y_est, 'y_sup'=>$y_est];
    }
    $t      = valor_t($n - 2);
    $margen = $t * $reg['se'] * sqrt(1 + 1/$n + (($x - $reg['x_media']) ** 2) / ($n * $reg['sxx']));
    return ['y_est'=>$y_est, 'y_inf'=>$y_est - $margen, 'y_sup'=>$y_est + $margen];
}

function detectar_outlier(float $nuevo_valor, array $valores): string {
    if (count($valores) < 3) return '';
    $media = array_sum($valores) / count($valores);
    $var   = array_sum(array_map(fn($v) => ($v - $media) ** 2, $valores)) / count($valores);
    $desv  = sqrt($var);
    if ($desv > 0 && abs($nuevo_valor - $media) > 2.5 * $desv) {
        return sprintf(
            ' Advertencia: la inversion %.2f esta fuera del rango habitual (media %.2f +/- 2.5σ = %.2f). Puede sesgar el modelo.',
            $nuevo_valor, $media, 2.5 * $desv
        );
    }
    return '';
}
