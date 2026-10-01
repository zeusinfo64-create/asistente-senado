<?php

/*
|--------------------------------------------------------------------------
| Orígenes permitidos (CORS)
|--------------------------------------------------------------------------
|
| La SPA React (Vite en localhost:5173) llama a la API Laravel desde un
| origen distinto al de la aplicación. Sanctum exige que estas peticiones
| lleven la cookie de sesión, por lo que el navegador exige una respuesta
| CORS con un origen explícito y `Access-Control-Allow-Credentials: true`.
|
| No se permite "*": con `supports_credentials` activo el navegador rechaza
| el comodín porque no puede asociarlo a credenciales. Los orígenes se
| declaran de forma explícita mediante CORS_ALLOWED_ORIGINS.
|
*/

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Rutas del proyecto que aceptan peticiones con credenciales desde la SPA.
    | `sanctum/csrf-cookie` debe incluirse porque emite la cookie XSRF.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    */

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Imprescindible para la autenticación por cookie de Sanctum: el
    | navegador solo envía y expone cookies cuando esta bandera está activa.
    |
    */

    'supports_credentials' => true,

];
