import { useContext, useMemo } from "react";

import { Link } from "react-router-dom";

import PageTitle from "../layouts/PageTitle";
import { AuthContext } from "../context/AuthContext";
import { getMenuEntriesForUser } from "../layouts/nav/Menu";
import { getPrimaryRoleLabel, ROLES } from "../utils/roles";

/**
 * Encabezado contextual por rol.
 *
 * Describe la funcion institucional del rol, sin datos, cifras ni KPIs.
 */
const ROLE_CONTEXT = {
  [ROLES.ADMIN]: {
    label: "Administración del sistema",
    description:
      "Responsable de la configuración institucional, del registro de usuarios y del soporte general a las unidades organizacionales.",
  },
  [ROLES.TECH]: {
    label: "Atención técnica",
    description:
      "Responsable del diagnóstico y resolución técnica de las solicitudes asignadas por la Mesa de Ayuda.",
  },
  [ROLES.REQUESTER]: {
    label: "Solicitud de soporte",
    description:
      "Responsable de registrar y hacer seguimiento de las solicitudes de soporte informático propias de su unidad.",
  },
};

/**
 * Presentacion del nombre de la persona o del usuario institucional.
 *
 * @param {{ full_name?: string, first_name?: string, username?: string }} user
 * @returns {string}
 */
function getDisplayName(user) {
  return user?.full_name || user?.first_name || user?.username || "";
}

/**
 * Dashboard institucional único (Fase 7.3).
 *
 * Muestra exclusivamente información real de la sesión obtenida de
 * AuthContext (GET /api/me) y el estado declarado de los módulos de
 * navegación. No contiene contadores, KPIs, gráficos ni datos de demostración:
 * los módulos funcionales se incorporan en fases posteriores.
 */
const Dashboard = () => {
  const { user } = useContext(AuthContext);

  const displayName = getDisplayName(user);
  const roleLabel = getPrimaryRoleLabel(user);

  // Los modulos visibles provienen del catálogo declarativo de Menu.jsx y se
  // filtran con los roles reales del usuario: no hay lista paralela aqui.
  const modules = useMemo(() => getMenuEntriesForUser(user), [user]);

  const context = useMemo(() => {
    const roleCodes = (user?.roles ?? []).map((role) => role?.code);

    return ROLE_CONTEXT[roleCodes.find((code) => ROLE_CONTEXT[code])] ?? null;
  }, [user]);

  return (
    <>
      <PageTitle motherMenu="Inicio" activeMenu="Dashboard" />

      {/* Identidad del usuario autenticado */}
      <div className="row">
        <div className="col-xl-12">
          <div className="card">
            <div className="card-body">
              <div className="d-flex align-items-center justify-content-between">
                <div>
                  <h4 className="card-title">
                    {displayName
                      ? `Bienvenido/a, ${displayName}`
                      : "Mesa de Ayuda TI"}
                  </h4>
                  <p className="text-muted mb-0">
                    Sistema de Mesa de Ayuda TI &mdash; Cámara de Senadores de
                    Bolivia
                  </p>
                </div>
                <span className="fs-40 text-primary">
                  <i className="flaticon-025-dashboard"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Datos reales de la sesion */}
      <div className="row">
        <div className="col-xl-4 col-md-6">
          <div className="card">
            <div className="card-body">
              <h4 className="card-title mb-2 font-w600">Usuario</h4>
              <p className="mb-1">
                <strong>{displayName || "No informado"}</strong>
              </p>
              <p className="text-muted mb-0">
                {user?.username ? `Usuario: ${user.username}` : "Usuario no informado"}
              </p>
            </div>
          </div>
        </div>

        <div className="col-xl-4 col-md-6">
          <div className="card">
            <div className="card-body">
              <h4 className="card-title mb-2 font-w600">Correo</h4>
              <p className="mb-1">{user?.email || "No informado"}</p>
              <p className="text-muted mb-0">Cuenta institucional</p>
            </div>
          </div>
        </div>

        <div className="col-xl-4 col-md-12">
          <div className="card">
            <div className="card-body">
              <h4 className="card-title mb-2 font-w600">Rol asignado</h4>
              <p className="mb-1">{roleLabel}</p>
              <p className="text-muted mb-0">
                {context ? context.label : "Rol institucional vigente"}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Descripción institucional del perfil */}
      {context && (
        <div className="row">
          <div className="col-xl-12">
            <div className="card">
              <div className="card-body">
                <h4 className="card-title">Alcance de su perfil</h4>
                <p className="text-muted mb-0">{context.description}</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modulos declarados para el rol, con su estado operativo real */}
      <div className="row">
        <div className="col-xl-12">
          <div className="card">
            <div className="card-body">
              <h4 className="card-title">Módulos del sistema</h4>
              <p className="text-muted">
                Las secciones autorizadas para su rol se muestran a
                continuación. Esta fase entrega la navegación institucional;
                los módulos funcionales se habilitan en fases posteriores.
              </p>

              {modules.length === 0 ? (
                <p className="text-muted mb-0">
                  No hay módulos asociados al rol asignado.
                </p>
              ) : (
                modules.map((module) => (
                  <div
                    key={module.key}
                    className="d-flex align-items-center justify-content-between border-top py-3"
                  >
                    <div className="d-flex align-items-center">
                      <span className="fs-30 text-primary mr-3">
                        <i className={module.icon}></i>
                      </span>
                      <div>
                        <span className="font-w600">{module.label}</span>
                        {module.enabled ? (
                          <p className="text-muted mb-0">Disponible en esta fase</p>
                        ) : (
                          <p className="text-muted mb-0">
                            No operativo todavía &mdash; {module.pendingReason}
                          </p>
                        )}
                      </div>
                    </div>

                    {module.enabled ? (
                      <Link to={module.path} className="btn btn-sm btn-primary">
                        Ingresar
                      </Link>
                    ) : (
                      <span className="badge badge-secondary">No operativa</span>
                    )}
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Dashboard;