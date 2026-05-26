import sys
import io
import mysql.connector

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8", errors="replace")

def conectar():
    return mysql.connector.connect(
        host="127.0.0.1",
        port=3306,
        user="root",
        password="",
        database="mineria_datos"
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
