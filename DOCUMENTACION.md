# Documentación del Proyecto: Regresión Lineal con Base de Datos
**Materia:** Minería de Datos  
**Instituto:** Instituto Politécnico Nacional (IPN)  
**Semestre:** 7mo semestre  
**Fecha:** Mayo 2026

---

## Índice
1. [Descripción general](#1-descripción-general)
2. [Herramientas instaladas](#2-herramientas-instaladas)
3. [Base de datos](#3-base-de-datos)
4. [Regresión lineal — teoría](#4-regresión-lineal--teoría)
5. [Python](#5-python)
6. [PHP y Dashboard web](#6-php-y-dashboard-web)
7. [C](#7-c)
8. [Cómo ejecutar cada componente](#8-cómo-ejecutar-cada-componente)
9. [Estructura de archivos del proyecto](#9-estructura-de-archivos-del-proyecto)

---

## 1. Descripción general

El proyecto consiste en una aplicación de **regresión lineal simple** que:

- Almacena datos de Inversión (X) y Ventas (Y) en una base de datos MySQL/MariaDB local.
- Calcula el modelo de regresión lineal a partir de los datos almacenados.
- Expone las consultas y cálculos desde tres lenguajes de programación: **Python**, **PHP** y **C**.
- Presenta un **dashboard web interactivo** (PHP + Chart.js) que muestra la gráfica, la ecuación, predicciones y permite agregar nuevos registros.

El sistema es **elástico**: al agregar más registros a la base de datos, el modelo se recalcula automáticamente sin modificar el código.

---

## 2. Herramientas instaladas

| Herramienta | Versión | Propósito |
|---|---|---|
| XAMPP | 8.2.12 | Servidor local: Apache + MariaDB + PHP |
| MySQL Workbench | 8.0 CE | Cliente gráfico para administrar la BD |
| Python | 3.14.4 (vía uv) | Script de regresión lineal |
| mysql-connector-python | 9.7.0 | Librería Python para conectarse a MySQL |
| GCC (MinGW-w64) | 13.2.0 | Compilador de C |
| libmysql.dll / .lib | MySQL Workbench | Librería C para conectarse a MySQL |
| Chart.js | CDN | Gráficas interactivas en el dashboard |
| 7-Zip | 23.01 | Descompresión de archivos |

> **Nota:** XAMPP debe estar corriendo con **MySQL** y **Apache** activos desde el panel de control (`C:\xampp\xampp-control.exe`) antes de usar cualquier componente del proyecto.

---

## 3. Base de datos

### Servidor
- **Host:** 127.0.0.1  
- **Puerto:** 3306  
- **Usuario:** root  
- **Contraseña:** *(vacía)*  
- **Base de datos:** `mineria_datos`

### Tabla: `inversion_ventas`

| Campo | Tipo | Descripción |
|---|---|---|
| id | INT AUTO_INCREMENT PK | Identificador único |
| mes | VARCHAR(20) | Nombre del mes |
| inversion | DECIMAL(10,2) | Variable independiente X |
| ventas | DECIMAL(10,2) | Variable dependiente Y |

### Datos iniciales

| id | mes | inversion (X) | ventas (Y) |
|---|---|---|---|
| 1 | Enero | 10.00 | 50.00 |
| 2 | Febrero | 20.00 | 80.00 |
| 3 | Marzo | 30.00 | 100.00 |

### Script SQL (`ventas.sql`)
```sql
CREATE DATABASE IF NOT EXISTS mineria_datos;
USE mineria_datos;

CREATE TABLE IF NOT EXISTS inversion_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mes VARCHAR(20) NOT NULL,
    inversion DECIMAL(10, 2) NOT NULL,
    ventas DECIMAL(10, 2) NOT NULL
);

INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES
    ('Enero',   10, 50.00),
    ('Febrero', 20, 80.00),
    ('Marzo',   30, 100.00);

SELECT * FROM inversion_ventas;
```

### Agregar nuevos datos (ejemplo: Abril)
```sql
USE mineria_datos;
INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES ('Abril', 40, 126.66);
```

---

## 4. Regresión lineal — teoría

La regresión lineal simple busca la recta que mejor se ajusta a un conjunto de puntos (X, Y):

```
Y = b0 + b1 * X
```

Donde:
- **b1** (pendiente) = cuánto aumenta Y por cada unidad de X
- **b0** (intercepto) = valor de Y cuando X = 0

### Fórmulas

```
       n·ΣXY  -  ΣX·ΣY
b1 = ─────────────────────
       n·ΣX²  -  (ΣX)²

       ΣY - b1·ΣX
b0 = ───────────────
            n
```

### Cálculo con los datos del proyecto

```
n  = 3
ΣX  = 10 + 20 + 30  = 60
ΣY  = 50 + 80 + 100 = 230
ΣXY = (10×50) + (20×80) + (30×100) = 500 + 1600 + 3000 = 5100
ΣX² = 100 + 400 + 900 = 1400

b1 = (3×5100 - 60×230) / (3×1400 - 60²)
   = (15300 - 13800) / (4200 - 3600)
   = 1500 / 600
   = 2.5

b0 = (230 - 2.5×60) / 3
   = (230 - 150) / 3
   = 80 / 3
   = 26.6667
```

**Ecuación resultante:**
```
Y = 26.67 + 2.50 * X
```

### Predicciones

| Inversión (X) | Ventas estimadas (Y) |
|---|---|
| 10 | 51.67 |
| 20 | 76.67 |
| 30 | 101.67 |
| 40 | 126.67 |
| 50 | 151.67 |
| 60 | 176.67 |

---

## 5. Python

### Archivo
`regresion_lineal.py`

### Ejecutable de Python
```
C:\Users\diego\AppData\Roaming\uv\python\cpython-3.14.4-windows-x86_64-none\python.exe
```

### Librería requerida
```
mysql-connector-python 9.7.0
```
Instalada con:
```
python.exe -m pip install mysql-connector-python --break-system-packages
```

### Descripción del script
1. Se conecta a la base de datos `mineria_datos` en `127.0.0.1:3306`.
2. Obtiene todos los registros de `inversion_ventas`.
3. Calcula b0 y b1 usando las fórmulas de regresión lineal (sin librerías externas de ML).
4. Imprime la tabla de datos, la ecuación y predicciones para X = 10, 20, 30, 40, 50, 60.

### Código (`regresion_lineal.py`)
```python
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
    conn   = conectar()
    cursor = conn.cursor()
    datos  = obtener_datos(cursor)
    # ... impresión de resultados y predicciones
    b0, b1 = regresion_lineal(datos)
    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()
```

---

## 6. PHP y Dashboard web

### Archivos
| Archivo | Ruta en servidor |
|---|---|
| `regresion.php` | `C:\xampp\htdocs\mineria_datos\regresion.php` |
| `index.php` (dashboard) | `C:\xampp\htdocs\mineria_datos\index.php` |

### URLs de acceso
```
http://localhost/mineria_datos/regresion.php
http://localhost/mineria_datos/
```

### Dependencias
- Apache activo en XAMPP
- PHP 8.2.12 (incluido en XAMPP)
- Extensión `mysqli` (activada por defecto en XAMPP)
- Chart.js (cargado desde CDN, requiere internet)

### regresion.php
Script PHP básico que:
1. Conecta a la BD vía `mysqli`.
2. Obtiene los datos de la tabla.
3. Calcula la regresión lineal.
4. Muestra HTML con tabla, ecuación y formulario para predecir (parámetro GET `?x=valor`).

### index.php — Dashboard
Dashboard completo que incluye:

| Sección | Descripción |
|---|---|
| Tarjetas resumen | Registros en BD, b0, b1, predicción actual |
| Gráfica | Scatter plot con puntos reales y línea de regresión (Chart.js) |
| Tabla | Datos leídos directamente de la BD |
| Calculadora | Campo para ingresar X y obtener Y estimada |
| Formulario | Agregar nuevos registros a la BD desde el navegador |

**Elasticidad:** Al agregar un nuevo registro (vía formulario o SQL), el dashboard recalcula automáticamente la regresión y actualiza la gráfica al recargar.

### Predicción dinámica (GET)
```
http://localhost/mineria_datos/?x=50
```
Calcula la predicción para X=50 y la muestra en la tarjeta y en la sección de predicción.

---

## 7. C

### Archivo fuente
`regresion_lineal.c`

### Compilador
GCC 13.2.0 — MinGW-w64, instalado en `C:\mingw64\mingw64\bin\gcc.exe`

### Librería MySQL para C
Se utilizó `libmysql.dll` y `libmysql.lib` de la instalación de **MySQL Workbench 8.0 CE**:
```
C:\Program Files\MySQL\MySQL Workbench 8.0 CE\swb\router\lib\libmysql.dll
C:\Program Files\MySQL\MySQL Workbench 8.0 CE\swb\router\lib\libmysql.lib
```
Ambos archivos se copiaron a la carpeta del proyecto.

### Header personalizado
Dado que MySQL Workbench no incluye cabeceras C públicas, se creó un header mínimo `mysql_include/mysql.h` que declara únicamente las funciones necesarias:
- `mysql_init`, `mysql_options`, `mysql_real_connect`
- `mysql_query`, `mysql_store_result`, `mysql_num_rows`
- `mysql_fetch_row`, `mysql_free_result`, `mysql_close`, `mysql_error`

### Plugin de autenticación
`libmysql.dll` v8.0 requiere que el directorio de plugins sea accesible. Se configuró en el código:
```c
#define PLUGIN_DIR "C:\\Program Files\\MySQL\\MySQL Workbench 8.0 CE"
mysql_options(conn, MYSQL_PLUGIN_DIR, PLUGIN_DIR);
```

### Comando de compilación
```
gcc regresion_lineal.c -I.\mysql_include -L. -lmysql -o regresion_lineal.exe
```

### Descripción del programa
1. Inicializa la conexión con `mysql_init()`.
2. Configura el directorio de plugins con `mysql_options()`.
3. Conecta a la BD con `mysql_real_connect()`.
4. Ejecuta la consulta SQL y almacena los resultados en un arreglo de structs `Dato`.
5. Calcula b0 y b1 con la función `regresion_lineal()`.
6. Imprime tabla, ecuación y predicciones para X = 10, 20, 30, 40, 50, 60.
7. Libera recursos con `mysql_free_result()`, `mysql_close()` y `free()`.

---

## 8. Cómo ejecutar cada componente

### Requisito previo
Abrir el panel de XAMPP (`C:\xampp\xampp-control.exe`) y activar:
- **MySQL** → Start
- **Apache** → Start (solo para PHP/Dashboard)

### Python
Abrir CMD o PowerShell en la carpeta del proyecto y ejecutar:
```
"C:\Users\diego\AppData\Roaming\uv\python\cpython-3.14.4-windows-x86_64-none\python.exe" regresion_lineal.py
```

### PHP (script básico)
Con Apache activo, abrir en el navegador:
```
http://localhost/mineria_datos/regresion.php
```

### Dashboard
Con Apache activo, abrir en el navegador:
```
http://localhost/mineria_datos/
```

### C (compilar y ejecutar)
Abrir CMD en la carpeta del proyecto:
```
"C:\mingw64\mingw64\bin\gcc.exe" regresion_lineal.c -I.\mysql_include -L. -lmysql -o regresion_lineal.exe
regresion_lineal.exe
```

---

## 9. Estructura de archivos del proyecto

```
Mineria de Datos/
│
├── ventas.sql                  Script SQL para crear la BD y la tabla
├── regresion_lineal.py         Script Python de regresión lineal
├── regresion_lineal.c          Programa C de regresión lineal
├── regresion_lineal.exe        Ejecutable compilado de C
├── libmysql.dll                Librería dinámica MySQL para C
├── libmysql.lib                Librería estática MySQL para C
├── mysql_native_password.dll   Plugin de autenticación MySQL
│
├── mysql_include/
│   └── mysql.h                 Header mínimo de la API MySQL para C
│
└── DOCUMENTACION.md            Este archivo

C:\xampp\htdocs\mineria_datos/
├── regresion.php               Script PHP básico de regresión
└── index.php                   Dashboard web completo
```

---

*Documentación generada para el proyecto de Minería de Datos — IPN, 7mo semestre, Mayo 2026.*
