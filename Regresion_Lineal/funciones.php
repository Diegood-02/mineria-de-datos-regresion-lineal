<?php
function calcular_regresion(array $datos): array {
    $n = count($datos);
    if ($n < 2) return ['b0'=>0,'b1'=>0,'r2'=>0,'mse'=>0,'rmse'=>0,'n'=>$n,'y_media'=>0];

    $sx = $sy = $sxy = $sx2 = 0;
    foreach ($datos as $d) {
        $x = (float)$d['inversion'];
        $y = (float)$d['ventas'];
        $sx += $x; $sy += $y; $sxy += $x * $y; $sx2 += $x * $x;
    }
    $b1 = ($n * $sxy - $sx * $sy) / ($n * $sx2 - $sx * $sx);
    $b0 = ($sy - $b1 * $sx) / $n;

    $y_media = $sy / $n;
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

    return ['b0'=>$b0,'b1'=>$b1,'r2'=>$r2,'mse'=>$mse,'rmse'=>$rmse,'n'=>$n,'y_media'=>$y_media];
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
