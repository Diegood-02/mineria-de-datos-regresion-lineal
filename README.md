# Regresión Lineal con Base de Datos — Minería de Datos

Proyecto para la materia de **Minería de Datos** del Instituto Politécnico Nacional (IPN), 7mo semestre.

Implementa un modelo de **regresión lineal simple** conectado a una base de datos MySQL/MariaDB local, expuesto desde tres lenguajes de programación y visualizado en un dashboard web interactivo.

---

## ¿Qué hace este proyecto?

- Almacena datos de **Inversión (X)** y **Ventas (Y)** en una base de datos MySQL.
- Calcula la ecuación de regresión lineal: `Y = b0 + b1 * X`
- Permite predecir ventas para cualquier valor de inversión.
- Es **elástico**: agregar más datos a la BD actualiza el modelo automáticamente.

---

## Tecnologías utilizadas

| Componente | Tecnología |
|---|---|
| Base de datos | MySQL / MariaDB (XAMPP) |
| Backend web | PHP 8.2 |
| Script de análisis | Python 3.14 |
| Programa nativo | C (GCC 13.2 / MinGW-w64) |
| Dashboard | HTML + CSS + Chart.js |
| Admin BD | MySQL Workbench 8.0 |

---

## Estructura del proyecto

```
├── ventas.sql                  Script para crear la base de datos y cargar datos
├── regresion_lineal.py         Regresión lineal desde Python
├── regresion_lineal.c          Regresión lineal desde C
├── regresion_lineal.exe        Ejecutable compilado (Windows x64)
├── libmysql.dll                Librería MySQL para C (runtime)
├── libmysql.lib                Librería MySQL para C (linkeo)
├── mysql_native_password.dll   Plugin de autenticación MySQL
├── mysql_include/
│   └── mysql.h                 Header mínimo de la API MySQL para C
├── README.md                   Este archivo
└── DOCUMENTACION.md            Documentación técnica completa del proyecto

htdocs/mineria_datos/           (carpeta en XAMPP)
├── regresion.php               Script PHP básico
└── index.php                   Dashboard web completo
```

---

## Requisitos previos

- [XAMPP](https://www.apachefriends.org/) con **MySQL** y **Apache** activos
- Python 3.x con `mysql-connector-python`
- GCC (MinGW-w64) para compilar el programa C
- MySQL Workbench (opcional, para administrar la BD)

---

## Configuración de la base de datos

1. Iniciar MySQL desde el panel de XAMPP.
2. Abrir MySQL Workbench y conectarse a `127.0.0.1:3306` con usuario `root` (sin contraseña).
3. Ejecutar el archivo `ventas.sql`.

Datos iniciales:

| Mes | Inversión (X) | Ventas (Y) |
|---|---|---|
| Enero | 10 | 50.00 |
| Febrero | 20 | 80.00 |
| Marzo | 30 | 100.00 |

---

## Cómo ejecutar

### Python
```bash
python regresion_lineal.py
```

### C (compilar y ejecutar)
```bash
gcc regresion_lineal.c -I.\mysql_include -L. -lmysql -o regresion_lineal.exe
.\regresion_lineal.exe
```

### Dashboard web (PHP)
Con Apache activo en XAMPP, copiar los archivos `.php` a `C:\xampp\htdocs\mineria_datos\` y abrir:
```
http://localhost/mineria_datos/
```

---

## Resultado del modelo (con 3 datos)

```
Ecuación:  Y = 26.67 + 2.50 * X

Inversión =  10  =>  Ventas estimadas =  51.67
Inversión =  20  =>  Ventas estimadas =  76.67
Inversión =  30  =>  Ventas estimadas = 101.67
Inversión =  40  =>  Ventas estimadas = 126.67
```

---

## Dashboard

El dashboard web (`index.php`) incluye:
- Gráfica interactiva con puntos reales y línea de regresión
- Tabla con datos de la base de datos
- Calculadora de predicción (ingresa X, obtiene Y estimada)
- Formulario para agregar nuevos registros directamente desde el navegador

---

*Instituto Politécnico Nacional — Minería de Datos, 7mo semestre, Mayo 2026*
