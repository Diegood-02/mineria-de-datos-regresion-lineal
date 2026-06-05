import sys
import io
import os
import math
import mysql.connector

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8", errors="replace")

def conectar():
    # Credenciales tomadas de variables de entorno (con valores por defecto
    # para el entorno local de XAMPP). Evita exponer contrasenas en el codigo.
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASS", ""),
        database=os.getenv("DB_NAME", "mineria_datos")
    )

def obtener_datos(cursor):
    cursor.execute("SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id")
    return cursor.fetchall()

def regresion_lineal(datos):
    n = len(datos)
    x = [fila[1] for fila in datos]
    y = [fila[2] for fila in datos]

    suma_x  = sum(x)
    suma_y  = sum(y)
    suma_xy = sum(xi * yi for xi, yi in zip(x, y))
    suma_x2 = sum(xi ** 2 for xi in x)

    b1 = (n * suma_xy - suma_x * suma_y) / (n * suma_x2 - suma_x ** 2)
    b0 = (suma_y - b1 * suma_x) / n

    return b0, b1

def metricas(datos, b0, b1):
    n = len(datos)
    y = [fila[2] for fila in datos]
    y_media = sum(y) / n

    ss_tot = sum((yi - y_media) ** 2 for yi in y)
    ss_res = sum((fila[2] - (b0 + b1 * fila[1])) ** 2 for fila in datos)

    r2   = 1 - ss_res / ss_tot if ss_tot > 0 else 1.0
    mse  = ss_res / n
    rmse = math.sqrt(mse)
    return r2, mse, rmse

def predecir(b0, b1, x):
    return b0 + b1 * x

def main():
    conn = conectar()
    cursor = conn.cursor()

    datos = obtener_datos(cursor)

    print("=" * 40)
    print("  DATOS DE LA BASE DE DATOS")
    print("=" * 40)
    print(f"{'Mes':<12} {'Inversion (X)':>14} {'Ventas (Y)':>12}")
    print("-" * 40)
    for mes, inv, ven in datos:
        print(f"{mes:<12} {float(inv):>14.2f} {float(ven):>12.2f}")

    b0, b1 = regresion_lineal(datos)

    print("\n" + "=" * 40)
    print("  REGRESION LINEAL")
    print("=" * 40)
    print(f"Intercepto  (b0): {b0:.4f}")
    print(f"Pendiente   (b1): {b1:.4f}")
    print(f"Ecuacion:  Y = {b0:.2f} + {b1:.2f} * X")

    r2, mse, rmse = metricas(datos, b0, b1)
    print("\n" + "=" * 40)
    print("  METRICAS DE BONDAD DE AJUSTE")
    print("=" * 40)
    print(f"R^2  (determinacion): {r2:.4f}  ({float(r2) * 100:.2f}% explicado)")
    print(f"MSE  (error cuad. medio): {mse:.4f}")
    print(f"RMSE (raiz del MSE): {rmse:.4f}")

    print("\n" + "=" * 40)
    print("  PREDICCIONES")
    print("=" * 40)
    valores_x = [row[1] for row in datos] + [40, 50, 60]
    for x_pred in valores_x:
        y_pred = predecir(b0, b1, x_pred)
        print(f"  Inversion = {float(x_pred):>5.0f}  =>  Ventas estimadas = {y_pred:.2f}")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()
