import api from "./axios";

/**
 * Endpoints de autenticacion verificados contra el backend Laravel.
 * NO se inventan rutas: provienen de routes/api.php y del registro de
 * Sanctum (GET /sanctum/csrf-cookie).
 */

/**
 * Solicita la cookie CSRF de Sanctum (XSRF-TOKEN). Debe ejecutarse antes de
 * cualquier POST/PATCH/DELETE para que Axios pueda enviar X-XSRF-TOKEN.
 *
 * La cookie no httpOnly se guarda en el navegador por diseño de Sanctum;
 * nunca se copia a localStorage ni se envia a mano.
 */
export async function fetchCsrfCookie() {
  await api.get("/sanctum/csrf-cookie");
}

/**
 * Inicia sesion contra Laravel. Crea la sesion y devuelve el usuario.
 *
 * El identificador es el username institucional, NO el correo: el correo es
 * dato de perfil y no se usa como credencial.
 *
 * @param {{ username: string, password: string }} credentials
 * @returns {Promise<object>} Usuario autenticado segun /api/me.
 */
export async function login({ username, password }) {
  await fetchCsrfCookie();

  const { data } = await api.post("/api/login", { username, password });

  return data.user;
}

/**
 * Obtiene el usuario de la sesion actual.
 *
 * @returns {Promise<object|null>} Usuario o null si no hay sesion activa.
 */
export async function fetchCurrentUser() {
  try {
    const { data } = await api.get("/api/me");

    return data.user;
  } catch (error) {
    // 401 es el caso normal al recargar sin sesion: no es un fallo real.
    if (error?.response?.status === 401) {
      return null;
    }

    throw error;
  }
}

/**
 * Cierra la sesion en Laravel: invalida la sesion y el token CSRF.
 * No es un borrado local, la sesion del servidor queda destruida.
 */
export async function logout() {
  await api.post("/api/logout");
}
