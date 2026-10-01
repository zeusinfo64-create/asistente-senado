import PropTypes from "prop-types";
import { createContext, useCallback, useEffect, useMemo, useState } from "react";

import { setUnauthorizedHandler } from "../api/axios";
import { fetchCurrentUser, login as loginRequest, logout as logoutRequest } from "../api/auth";
import { getUserRoleCodes, hasAnyRole, hasRole } from "../utils/roles";

export const AuthContext = createContext(null);

/**
 * Estado de autenticacion del frontend.
 *
 * La sesion vive en la cookie de Laravel. Este provider solo conserva en
 * memoria el usuario devuelto por `/api/me`; al recargar la pagina se vuelve
 * a validar contra el backend.
 */
const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [loggingOut, setLoggingOut] = useState(false);

  const isAuthenticated = Boolean(user);

  /**
   * Reemplaza el usuario en memoria tras un cambio de sesion.
   * Se usa como handler del interceptor 401 de axios.
   */
  const clearSession = useCallback(() => {
    setUser(null);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(clearSession);

    return () => setUnauthorizedHandler(null);
  }, [clearSession]);

  /**
   * Comprobacion inicial de sesion. Se ejecuta una sola vez al montar la app
   * para que recargar /dashboard no cierre la sesion ni provoque un bucle de
   * redirecciones.
   */
  useEffect(() => {
    let active = true;

    fetchCurrentUser()
      .then((currentUser) => {
        if (active) {
          setUser(currentUser);
        }
      })
      .catch(() => {
        if (active) {
          setUser(null);
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  const login = useCallback(async (credentials) => {
    const authenticatedUser = await loginRequest(credentials);

    setUser(authenticatedUser);

    return authenticatedUser;
  }, []);

  const logout = useCallback(async () => {
    setLoggingOut(true);

    try {
      await logoutRequest();
    } finally {
      // El estado del frontend se limpia incluso si la llamada falla: la
      // sesion del navegador ya no debe considerarse valida.
      setUser(null);
      setLoggingOut(false);
    }
  }, []);

  const refreshUser = useCallback(async () => {
    const currentUser = await fetchCurrentUser();

    setUser(currentUser);

    return currentUser;
  }, []);

  const value = useMemo(
    () => ({
      user,
      loading,
      loggingOut,
      isAuthenticated,
      roles: getUserRoleCodes(user),
      hasRole: (role) => hasRole(user, role),
      hasAnyRole: (roles) => hasAnyRole(user, roles),
      login,
      logout,
      refreshUser,
    }),
    [user, loading, loggingOut, isAuthenticated, login, logout, refreshUser]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

AuthProvider.propTypes = {
  children: PropTypes.node,
};

export default AuthProvider;
