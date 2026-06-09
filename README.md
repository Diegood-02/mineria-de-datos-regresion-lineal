# Regresión Lineal con Base de Datos — Minería de Datos

Proyecto para la materia de **Minería de Datos** del Instituto Politécnico Nacional (IPN), 7mo semestre.

Implementa un modelo de **regresión lineal simple** conectado a una base de datos MySQL/MariaDB, desarrollado en tres lenguajes de programación y visualizado en un sistema web multi-página con navegación.

---

## ¿Qué hace este proyecto?

- Almacena datos de **Inversión (X)** y **Ventas (Y)** en una base de datos MySQL.
- Calcula la ecuación de regresión lineal: `Y = b0 + b1 * X`
- Reporta **métricas de bondad de ajuste**: R², MSE y RMSE.
- Permite predecir ventas para cualquier valor de inversión.
- Es **elástico**: agregar o eliminar datos en la BD actualiza el modelo automáticamente.
- Incluye **validación de outliers** (±2.5σ) y consultas seguras contra inyección SQL.

---

## Tecnologías utilizadas

| Componente | Tecnología |
|---|---|
| Base de datos | MySQL / MariaDB (XAMPP) |
| Backend web | PHP 8 |
| Script de análisis | Python 3 |
| Programa nativo | C (GCC / MinGW-w64) |
| Dashboard | HTML + CSS + Chart.js |

---

## Estructura del proyecto

```
Regresion_Lineal/
├── ventas.sql                  Script para crear la BD y cargar datos iniciales
├── config.example.php          Plantilla de credenciales (copiar a config.php)
├── config.php                  Credenciales reales de la BD (NO se sube a git)
│
├── db.php                      Conexión MySQLi compartida
├── funciones.php               calcular_regresion() y detectar_outlier()
├── header.php                  Navbar + CSS del sistema (compartido)
├── footer.php                  Cierre HTML (compartido)
│
├── index.php                   Dashboard: tarjetas de métricas + gráfica
├── datos.php                   Gestión de datos: tabla, agregar y eliminar registros
├── modelo.php                  Modelo: ecuación, R², MSE, RMSE y gráfica
├── prediccion.php              Calculadora de predicción
│
├── regresion_lineal.py         Regresión lineal desde Python
├── regresion_lineal.c          Regresión lineal desde C
├── regresion_lineal.exe        Ejecutable compilado (Windows x64)
│
└── DOCUMENTACION.md            Documentación técnica completa del proyecto
```

---

## Requisitos previos

- [XAMPP](https://www.apachefriends.org/) con **MySQL** activo
- Python 3.x con `mysql-connector-python`
- GCC (MinGW-w64) para compilar el programa en C

---

## Configuración

1. Iniciar MySQL desde el panel de XAMPP.
2. Ejecutar `ventas.sql` para crear la base de datos con los datos iniciales.
3. Copiar `config.example.php` a `config.php` y ajustar credenciales si es necesario.

Datos iniciales cargados:

| Mes | Inversión (X) | Ventas (Y) |
|---|---|---|
| Enero | 10 | 50.00 |
| Febrero | 20 | 80.00 |
| Marzo | 30 | 100.00 |

---

## Cómo ejecutar

### Dashboard web (PHP)
```bash
php -S localhost:8080 -t Regresion_Lineal/
```
Abrir: `http://localhost:8080/index.php`

### Python
```bash
python Regresion_Lineal/regresion_lineal.py
```

### C
```bash
gcc regresion_lineal.c -I.\mysql_include -L. -lmysql -o regresion_lineal.exe
.\regresion_lineal.exe
```

---

## Resultado del modelo (con 3 datos iniciales)

```
Ecuación:  Y = 26.67 + 2.50 * X

Métricas de bondad de ajuste:
  R²   = 0.9868   (98.68% de la variabilidad explicada)
  MSE  = 5.5556
  RMSE = 2.3570
```

---

## Dashboard

El sistema web incluye 4 secciones accesibles desde el navbar:

| Sección | Descripción |
|---|---|
| **Dashboard** | Tarjetas con R², b0, b1 y predicción base + gráfica de dispersión |
| **Gestión de Datos** | Tabla de registros con botón eliminar + formulario para agregar |
| **Modelo** | Ecuación completa, métricas R²/MSE/RMSE e interpretación |
| **Predicción** | Calculadora: ingresa X → obtiene ventas estimadas |

---

*Instituto Politécnico Nacional — Minería de Datos, 7mo semestre, 2026*
