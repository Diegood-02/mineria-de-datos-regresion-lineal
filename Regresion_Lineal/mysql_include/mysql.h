#ifndef MYSQL_H
#define MYSQL_H

#ifdef __cplusplus
extern "C" {
#endif

#include <stddef.h>

typedef struct MYSQL        MYSQL;
typedef struct MYSQL_RES    MYSQL_RES;
typedef char**              MYSQL_ROW;

#ifdef _WIN32
  #define STDCALL __stdcall
#else
  #define STDCALL
#endif

typedef enum {
    MYSQL_OPT_CONNECT_TIMEOUT, MYSQL_OPT_COMPRESS, MYSQL_OPT_NAMED_PIPE,
    MYSQL_INIT_COMMAND, MYSQL_READ_DEFAULT_FILE, MYSQL_READ_DEFAULT_GROUP,
    MYSQL_SET_CHARSET_DIR, MYSQL_SET_CHARSET_NAME, MYSQL_OPT_LOCAL_INFILE,
    MYSQL_OPT_PROTOCOL, MYSQL_SHARED_MEMORY_BASE_NAME, MYSQL_OPT_READ_TIMEOUT,
    MYSQL_OPT_WRITE_TIMEOUT, MYSQL_OPT_USE_RESULT, MYSQL_REPORT_DATA_TRUNCATION,
    MYSQL_OPT_RECONNECT, MYSQL_PLUGIN_DIR, MYSQL_DEFAULT_AUTH,
    MYSQL_OPT_BIND, MYSQL_OPT_SSL_KEY, MYSQL_OPT_SSL_CERT,
    MYSQL_OPT_SSL_CA, MYSQL_OPT_SSL_CAPATH, MYSQL_OPT_SSL_CIPHER,
    MYSQL_OPT_SSL_CRL, MYSQL_OPT_SSL_CRLPATH, MYSQL_OPT_CONNECT_ATTR_RESET,
    MYSQL_OPT_CONNECT_ATTR_ADD, MYSQL_OPT_CONNECT_ATTR_DELETE,
    MYSQL_SERVER_PUBLIC_KEY, MYSQL_ENABLE_CLEARTEXT_PLUGIN,
    MYSQL_OPT_CAN_HANDLE_EXPIRED_PASSWORDS, MYSQL_OPT_MAX_ALLOWED_PACKET,
    MYSQL_OPT_NET_BUFFER_LENGTH, MYSQL_OPT_TLS_VERSION,
    MYSQL_OPT_SSL_MODE, MYSQL_OPT_GET_SERVER_PUBLIC_KEY,
    MYSQL_OPT_RETRY_COUNT, MYSQL_OPT_OPTIONAL_RESULTSET_METADATA,
    MYSQL_OPT_SSL_FIPS_MODE, MYSQL_OPT_TLS_CIPHERSUITES,
    MYSQL_OPT_COMPRESSION_ALGORITHMS, MYSQL_OPT_ZSTD_COMPRESSION_LEVEL,
    MYSQL_OPT_LOAD_DATA_LOCAL_DIR, MYSQL_OPT_USER_PASSWORD,
    MYSQL_OPT_SSL_SESSION_DATA
} mysql_option;

int         STDCALL mysql_options(MYSQL *mysql, mysql_option option, const void *arg);
MYSQL*      STDCALL mysql_init(MYSQL *mysql);
MYSQL*      STDCALL mysql_real_connect(MYSQL *mysql,
                        const char *host, const char *user,
                        const char *passwd, const char *db,
                        unsigned int port,
                        const char *unix_socket,
                        unsigned long clientflag);
int         STDCALL mysql_query(MYSQL *mysql, const char *stmt_str);
MYSQL_RES*  STDCALL mysql_store_result(MYSQL *mysql);
unsigned long long STDCALL mysql_num_rows(MYSQL_RES *result);
MYSQL_ROW   STDCALL mysql_fetch_row(MYSQL_RES *result);
void        STDCALL mysql_free_result(MYSQL_RES *result);
void        STDCALL mysql_close(MYSQL *mysql);
const char* STDCALL mysql_error(MYSQL *mysql);

#ifdef __cplusplus
}
#endif

#endif
