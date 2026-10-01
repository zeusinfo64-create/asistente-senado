import { Suspense, useContext } from "react";

import { Navigate, Route, Outlet, Routes } from "react-router-dom";

import Nav from "../layouts/nav/Nav";
import Footer from "../layouts/Footer";
import ScrollToTop from "../layouts/ScrollToTop";
import Dashboard from "../pages/Dashboard";
import Login from "../pages/auth/Login";
import NotFound from "../pages/NotFound";
import { ProtectedRoute, GuestRoute } from "./ProtectedRoute";
import { RoleRoute } from "./RoleRoute";
import { ThemeContext } from "../context/ThemeContext";

function LoadingFallback() {
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

export function MainLayout() {
  const { menuToggle, sidebariconHover } = useContext(ThemeContext);

  return (
    <div
      id="main-wrapper"
      className={`show ${sidebariconHover ? "iconhover-toggle" : ""} ${
        menuToggle ? "menu-toggle" : ""
      }`}
    >
      <Nav />
      <div className="content-body">
        <div className="container-fluid">
          <Outlet />
        </div>
      </div>
      <Footer />
    </div>
  );
}

export function AppRoutes() {
  return (
    <Suspense fallback={<LoadingFallback />}>
      <Routes>
        {/* Ruta publica: solo accesible sin sesion activa. */}
        <Route element={<GuestRoute />}>
          <Route exact path="/login" element={<Login />} />
        </Route>

        {/* Rutas privadas: exigen sesion Laravel valida. */}
        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route exact path="/" element={<Navigate to="/dashboard" replace />} />

            {/* Rutas con rol institucional requerido. Los roles autorizados se
                derivan del catalogo declarativo de Menu.jsx; el backend sigue
                siendo la autoridad de la autorizacion real. */}
            <Route element={<RoleRoute />}>
              <Route exact path="/dashboard" element={<Dashboard />} />
            </Route>

            {/* Cualquier ruta no declarada responde 404 en lugar de redirigir
                al Dashboard como si fuera funcional. */}
            <Route path="*" element={<NotFound />} />
          </Route>
        </Route>
      </Routes>
      <ScrollToTop />
    </Suspense>
  );
}