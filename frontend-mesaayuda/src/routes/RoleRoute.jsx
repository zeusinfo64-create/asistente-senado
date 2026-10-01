import { useContext } from "react";

import { Navigate, Outlet, useLocation } from "react-router-dom";

import { AuthContext } from "../context/AuthContext";
import { getRolesForPath } from "../layouts/nav/Menu";
import NotAuthorized from "../pages/NotAuthorized";
import { SessionLoading } from "./ProtectedRoute";

/**
 * Indica si el usuario tiene al menos uno de los roles indicados.
 *
 * @param {{ roles?: Array<{ code: string }> } | null} user
 * @param {string[]} requiredRoles
 * @returns {boolean}
 */
function userHasAnyRole(user, requiredRoles) {
  if (!user || !Array.isArray(user.roles)) {
    return false;
  }

  return user.roles.some((role) => requiredRoles.includes(role?.code));
}

/**
 * Ruta privada restringida por rol institucional.
 *
 * Los roles autorizados NO se declaran aquí: se derivan del catálogo
 * declarativo de navegación (layouts/nav/Menu) según la ruta visitada, de modo
 * que existe una sola fuente de verdad de permisos en el frontend.
 *
 * Se coloca dentro de ProtectedRoute, por lo que solo interviene cuando ya
 * hay sesion valida:
 *   - sin sesion  -> /login (lo resuelve ProtectedRoute);
 *   - con sesion y rol autorizado -> renderiza la ruta;
 *   - con sesion y sin autorizacion -> NotAuthorized (403).
 *
 * El backend sigue siendo la autoridad: esto evita que la navegacion del
 * frontend sustituya la autorizacion real de la API.
 */
export function RoleRoute() {
  const { user, isAuthenticated, loading } = useContext(AuthContext);
  const location = useLocation();

  if (loading) {
    return <SessionLoading />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  const requiredRoles = getRolesForPath(location.pathname);

  // Fail-closed: una ruta bajo RoleRoute debe estar declarada en el catalogo.
  // Si no existe entrada, se considera no autorizada.
  if (requiredRoles.length === 0 || !userHasAnyRole(user, requiredRoles)) {
    return <NotAuthorized />;
  }

  return <Outlet />;
}

export default RoleRoute;