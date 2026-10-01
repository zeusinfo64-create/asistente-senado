/**
 * Roles validos del sistema. Los nombres internos coinciden con los codigos
 * que entrega el backend en `GET /api/me` (`roles[].code`) y con los que exige
 * el middleware `role` del backend (App\Http\Middleware\EnsureUserHasRole).
 *
 * No se crean roles adicionales ni sinonimos.
 */
export const ROLES = {
  ADMIN: "ADMIN",
  TECH: "TECH",
  REQUESTER: "REQUESTER",
};

/** Lista de codigos de rol validos. */
export const ROLE_CODES = Object.values(ROLES);

/**
 * Devuelve los codigos de rol del usuario autenticado.
 *
 * @param {{ roles?: Array<{ code: string }> } | null} user
 * @returns {string[]}
 */
export function getUserRoleCodes(user) {
  if (!user || !Array.isArray(user.roles)) {
    return [];
  }

  return user.roles
    .map((role) => role?.code)
    .filter((code) => ROLE_CODES.includes(code));
}

/**
 * Indica si el usuario posee exactamente el rol indicado.
 *
 * @param {object | null} user
 * @param {string} role
 * @returns {boolean}
 */
export function hasRole(user, role) {
  return getUserRoleCodes(user).includes(role);
}

/**
 * Indica si el usuario posee al menos uno de los roles indicados.
 *
 * @param {object | null} user
 * @param {string[]} roles
 * @returns {boolean}
 */
export function hasAnyRole(user, roles) {
  const codes = getUserRoleCodes(user);

  return roles.some((role) => codes.includes(role));
}

/**
 * Nombre legible del rol principal del usuario, para mostrar en la interfaz.
 *
 * @param {object | null} user
 * @returns {string}
 */
export function getPrimaryRoleLabel(user) {
  if (!user || !Array.isArray(user.roles) || user.roles.length === 0) {
    return "Sin rol asignado";
  }

  return user.roles.map((role) => role?.name).filter(Boolean).join(" / ");
}
