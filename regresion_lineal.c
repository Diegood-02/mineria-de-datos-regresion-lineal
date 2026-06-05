#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <mysql.h>

// Valores por defecto (entorno local XAMPP). Se pueden sobreescribir con
// variables de entorno para no exponer credenciales en el codigo fuente.
#define DEF_HOST     "127.0.0.1"
#define DEF_USER     "root"
#define DEF_PASSWORD ""
#define DEF_DATABASE "mineria_datos"
#define DEF_PORT     3306
#define PLUGIN_DIR   "C:\\Program Files\\MySQL\\MySQL Workbench 8.0 CE"

// Devuelve el valor de la variable de entorno o el valor por defecto.
static const char *env_o_def(const char *nombre, const char *valor_def) {
    const char *v = getenv(nombre);
    return (v && *v) ? v : valor_def;
}

typedef struct {
    char mes[30];
    double inversion;
    double ventas;
} Dato;

void regresion_lineal(Dato *datos, int n, double *b0, double *b1) {
    double suma_x = 0, suma_y = 0, suma_xy = 0, suma_x2 = 0;

    for (int i = 0; i < n; i++) {
        double x = datos[i].inversion;
        double y = datos[i].ventas;
        suma_x  += x;
        suma_y  += y;
        suma_xy += x * y;
        suma_x2 += x * x;
    }

    *b1 = (n * suma_xy - suma_x * suma_y) / (n * suma_x2 - suma_x * suma_x);
    *b0 = (suma_y - (*b1) * suma_x) / n;
}

void metricas(Dato *datos, int n, double b0, double b1,
              double *r2, double *mse, double *rmse) {
    double suma_y = 0;
    for (int i = 0; i < n; i++) suma_y += datos[i].ventas;
    double y_media = suma_y / n;

    double ss_tot = 0, ss_res = 0;
    for (int i = 0; i < n; i++) {
        double y     = datos[i].ventas;
        double y_est = b0 + b1 * datos[i].inversion;
        ss_tot += (y - y_media) * (y - y_media);
        ss_res += (y - y_est) * (y - y_est);
    }

    *r2   = (ss_tot > 0) ? 1.0 - ss_res / ss_tot : 1.0;
    *mse  = ss_res / n;
    *rmse = sqrt(*mse);
}

int main() {
    MYSQL *conn = mysql_init(NULL);
    if (!conn) {
        fprintf(stderr, "Error al inicializar MySQL\n");
        return 1;
    }

    mysql_options(conn, MYSQL_PLUGIN_DIR, PLUGIN_DIR);

    const char *host = env_o_def("DB_HOST", DEF_HOST);
    const char *user = env_o_def("DB_USER", DEF_USER);
    const char *pass = env_o_def("DB_PASS", DEF_PASSWORD);
    const char *db   = env_o_def("DB_NAME", DEF_DATABASE);
    const char *port_s = getenv("DB_PORT");
    unsigned int port = (port_s && *port_s) ? (unsigned int)atoi(port_s) : DEF_PORT;

    if (!mysql_real_connect(conn, host, user, pass, db, port, NULL, 0)) {
        fprintf(stderr, "Error de conexion: %s\n", mysql_error(conn));
        mysql_close(conn);
        return 1;
    }

    if (mysql_query(conn, "SELECT mes, inversion, ventas FROM inversion_ventas ORDER BY id")) {
        fprintf(stderr, "Error en consulta: %s\n", mysql_error(conn));
        mysql_close(conn);
        return 1;
    }

    MYSQL_RES *resultado = mysql_store_result(conn);
    if (!resultado) {
        fprintf(stderr, "Error al obtener resultado: %s\n", mysql_error(conn));
        mysql_close(conn);
        return 1;
    }

    int n = (int)mysql_num_rows(resultado);
    Dato *datos = malloc(n * sizeof(Dato));

    printf("========================================\n");
    printf("  DATOS DE LA BASE DE DATOS\n");
    printf("========================================\n");
    printf("%-12s %14s %12s\n", "Mes", "Inversion (X)", "Ventas (Y)");
    printf("----------------------------------------\n");

    MYSQL_ROW fila;
    int i = 0;
    while ((fila = mysql_fetch_row(resultado))) {
        strncpy(datos[i].mes, fila[0], sizeof(datos[i].mes) - 1);
        datos[i].inversion = atof(fila[1]);
        datos[i].ventas    = atof(fila[2]);
        printf("%-12s %14.2f %12.2f\n", datos[i].mes, datos[i].inversion, datos[i].ventas);
        i++;
    }

    mysql_free_result(resultado);
    mysql_close(conn);

    double b0, b1;
    regresion_lineal(datos, n, &b0, &b1);

    printf("\n========================================\n");
    printf("  REGRESION LINEAL\n");
    printf("========================================\n");
    printf("Intercepto (b0): %.4f\n", b0);
    printf("Pendiente  (b1): %.4f\n", b1);
    printf("Ecuacion:  Y = %.2f + %.2f * X\n", b0, b1);

    double r2, mse, rmse;
    metricas(datos, n, b0, b1, &r2, &mse, &rmse);

    printf("\n========================================\n");
    printf("  METRICAS DE BONDAD DE AJUSTE\n");
    printf("========================================\n");
    printf("R^2  (determinacion): %.4f  (%.2f%% explicado)\n", r2, r2 * 100);
    printf("MSE  (error cuad. medio): %.4f\n", mse);
    printf("RMSE (raiz del MSE): %.4f\n", rmse);

    printf("\n========================================\n");
    printf("  PREDICCIONES\n");
    printf("========================================\n");
    double valores_pred[] = {10, 20, 30, 40, 50, 60};
    int num_pred = sizeof(valores_pred) / sizeof(valores_pred[0]);
    for (int j = 0; j < num_pred; j++) {
        double x = valores_pred[j];
        double y = b0 + b1 * x;
        printf("  Inversion = %5.0f  =>  Ventas estimadas = %.2f\n", x, y);
    }

    free(datos);
    return 0;
}
