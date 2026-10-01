import axios from "axios";

/**
 * Cliente HTTP unico del frontend (Mesa de Ayuda TI).
 *
 * La autenticacion es por cookie de sesion de Laravel Sanctum: no se
 * almacenan tokens en localStorage, sessionStorage ni en memoria JS.
 *
 * - `withCredentials: true`  -> envia la cookie de sesion de Laravel.
 * - `withXSRFToken: true`    -> obligatorio: por defecto Axios solo envia la
 *                               cabecera XSRF en peticiones del mismo origen.
 *                               La SPA (localhost:5173) consume la API en otro
 *                               puerto, por lo que sin esta opcion Laravel
 *                               responde "CSRF token mismatch".
 * - `xsrfCookieName`         -> Sanctum emite la cookie `XSRF-TOKEN`.
 * - `xsrfHeaderName`         -> Laravel valida la cabecera `X-XSRF-TOKEN`.
 *                               Axios lee la cookie y la copia sola.
 */

const API_URL =
  import.meta.env.VITE_API_URL || "http://localhost:8000";

/** Rutas que nunca deben disparar el cierre de sesion automatico por 401. */
const AUTH_ENDPOINTS = ["/api/login", "/api/logout", "/sanctum/csrf-cookie"];

const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

/**
 * Callback invocado cuando una peticion autenticada responde 401.
 * Lo registra el AuthProvider para limpiar el estado y redirigir al login.
 * Se mantiene fuera de React para no acoplar axios al router.
 */
let unauthorizedHandler = null;

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler;
}

/** Extrae un mensaje legible de la respuesta de Laravel. */
export function extractApiErrorMessage(error, fallback = "Ocurrió un error inesperado. Intente nuevamente.") {
  const status = error?.response?.status;

  if (status === 429) {
    return "Demasiados intentos. Espere un momento e intente nuevamente.";
  }

  const message = error?.response?.data?.message;
  if (typeof message === "string" && message.trim() !== "") {
    return message;
  }

  if (error?.code === "ERR_NETWORK") {
    return "No se pudo conectar con el servidor. Verifique su conexión e intente nuevamente.";
  }

  return fallback;
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status;
    const url = error?.config?.url ?? "";

    // Solo un 401 en una peticion autenticada cierra la sesion. /api/login
    // devuelve 401 con credenciales invalidas y debe mostrarse en el formulario.
    const isAuthEndpoint = AUTH_ENDPOINTS.some((endpoint) => url.includes(endpoint));

    if (status === 401 && !isAuthEndpoint && unauthorizedHandler) {
      unauthorizedHandler();
    }

    return Promise.reject(error);
  }
);

export default api;
