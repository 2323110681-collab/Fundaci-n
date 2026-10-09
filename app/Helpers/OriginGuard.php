<?php

/**
 * Comprobacion de origen para peticiones que modifican datos.
 *
 * El navegador envia la cabecera Origin en las peticiones POST incluso cuando
 * son del propio sitio, y ahi llega con el puerto ('http://localhost:8081').
 * parse_url(..., PHP_URL_HOST) devuelve el host SIN puerto ('localhost'), pero
 * $_SERVER['HTTP_HOST'] lo devuelve CON puerto ('localhost:8081'). Comparar
 * ambos directamente hacia que cualquier despliegue en un puerto distinto del
 * 80 se rechazara como si fuera de otro sitio, y por eso el inicio de sesion
 * devolvia 403 en cuanto se abria el panel en http://localhost:8081.
 *
 * Aqui se normalizan ambos lados a host:puerto antes de comparar.
 */
class OriginGuard
{
    /**
     * Indica si la peticion procede del propio sitio y debe permitirse.
     * Sin cabecera Origin no hay nada que verificar y se permite continuar.
     *
     * Se comparan host y puerto, pero no el esquema. Detras de un proxy que
     * termina TLS, PHP ve HTTP mientras el navegador ve HTTPS; exigir que los
     * esquemas coincidan dejaria el panel sin poder guardar nada. La defense
     * real frente a CSRF la aporta la cookie de sesion con SameSite=Lax junto
     * con el token CSRF, no esta comprobacion.
     */
    public static function isSameOrigin(?string $origin, ?string $hostHeader, bool $isHttps = false): bool
    {
        $origin = trim((string) $origin);
        if ($origin === '') {
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        if (!is_string($originHost) || $originHost === '') {
            return false;
        }

        $originScheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
        $originPort = parse_url($origin, PHP_URL_PORT);
        $originPort = $originPort ?: ($originScheme === 'https' ? '443' : '80');

        $originHost = self::normalizeHost($originHost);
        $requestHost = self::splitHost($hostHeader, $requestPort);
        if ($requestHost === null) {
            return false;
        }
        $requestPort = $requestPort ?: ($isHttps ? '443' : '80');

        return $originHost === $requestHost
            && (string) $originPort === (string) $requestPort;
    }

    /**
     * parse_url() devuelve el host IPv6 entre corchetes ('[::1]'), mientras que
     * $_SERVER['HTTP_HOST'] tambien los trae. Se quitan en ambos lados para
     * comparar el mismo valor.
     */
    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if (strlen($host) > 1 && $host[0] === '[' && substr($host, -1) === ']') {
            return substr($host, 1, -1);
        }
        return $host;
    }

    /**
     * Separa 'ejemplo.com:8081' en host y puerto. Admite IPv6 entre corchetes
     * ('[::1]:8081'), donde los dos puntos pertenecen a la direccion y no al
     * separador del puerto.
     *
     * @return array{0: ?string, 1: ?string} [host, puerto]
     */
    private static function splitHost(?string $hostHeader, ?string &$port): ?string
    {
        $port = null;
        $hostHeader = strtolower(trim((string) $hostHeader));
        if ($hostHeader === '') {
            return null;
        }

        if ($hostHeader[0] === '[') {
            $cierre = strpos($hostHeader, ']');
            if ($cierre === false) {
                return null;
            }
            $host = substr($hostHeader, 1, $cierre - 1);
            $resto = substr($hostHeader, $cierre + 1);
            if ($resto !== '' && $resto[0] === ':') {
                $port = substr($resto, 1);
            }
            return $host !== '' ? $host : null;
        }

        $separador = strrpos($hostHeader, ':');
        if ($separador === false) {
            return $hostHeader;
        }

        $posiblePuerto = substr($hostHeader, $separador + 1);
        if ($posiblePuerto !== '' && ctype_digit($posiblePuerto)) {
            $port = $posiblePuerto;
            return substr($hostHeader, 0, $separador);
        }

        // Sin puerto explicito: es parte del host (IPv6 sin corchetes).
        return $hostHeader;
    }
}
