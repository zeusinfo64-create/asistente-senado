
import { useContext } from "react";
import { Navigate, Outlet, useLocation } from "react-router-dom";

import { AuthContext } from "../context/AuthContext";

/** Pantalla institucional de carga mientras se comprueba la sesion. */
export function SessionLoading() {
  return (
    <div id="preloader">
      <div className="sk-three-bounce">
        <div className="sk-child sk-bounce1"></div>
        <div className="sk-child sk-bounce2"></div>
        <div className="sk-child sk-bounce3"></div>
      </div>
    </div>
  );
}

/**
 * Ruta privada: exige sesion activa.
 *
 * Mientras `loading` es true no se muestra contenido privado ni se redirige,
 * para evitar el bucle de redireccion al recargar la pagina.
 */
export function ProtectedRoute() {
  const { isAuthenticated, loading } = useContext(AuthContext);
  const location = useLocation();

  if (loading) {
    return <SessionLoading />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <Outlet />;
}

/**
 * Ruta publica restringida: si ya hay sesion, /login no debe mostrarse.
 */
export function GuestRoute() {
  const { isAuthenticated, loading } = useContext(AuthContext);
  const location = useLocation();

  if (loading) {
    return <SessionLoading />;
  }

  if (isAuthenticated) {
    const from = location.state?.from?.pathname;

    return <Navigate to={from && from !== "/login" ? from : "/dashboard"} replace />;
  }

  return <Outlet />;
}

export default ProtectedRoute;
