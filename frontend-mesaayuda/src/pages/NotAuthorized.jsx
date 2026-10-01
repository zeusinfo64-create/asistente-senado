import { Link } from "react-router-dom";

import PageTitle from "../layouts/PageTitle";

/**
 * Respuesta institucional 403 / acceso no autorizado.
 *
 * Se muestra cuando el usuario tiene sesion valida pero su rol no esta
 * autorizado para la ruta solicitada. Los roles se determinan en RoleRoute a
 * partir del catalogo declarativo de Menu.jsx.
 */
const NotAuthorized = () => {
  return (
    <>
      <PageTitle motherMenu="Inicio" activeMenu="Acceso no autorizado" />

      <div className="row">
        <div className="col-xl-8 col-lg-10">
          <div className="card">
            <div className="card-body text-center">
              <span className="fs-50 text-warning">
                <i className="flaticon-050-info"></i>
              </span>
              <h2 className="mt-3 mb-2">403</h2>
              <h4 className="card-title">Acceso no autorizado</h4>
              <p className="text-muted mb-0">
                Su usuario no tiene permisos para acceder a esta seccion del
                Sistema de Mesa de Ayuda TI. Si considera que se trata de un
                error, solicite la habilitación correspondiente a la Unidad de
                Tecnologías de Información.
              </p>

              <Link to="/dashboard" className="btn btn-primary mt-4">
                Volver al Dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default NotAuthorized;