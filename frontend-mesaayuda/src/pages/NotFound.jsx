import { Link } from "react-router-dom";

import PageTitle from "../layouts/PageTitle";

/**
 * Respuesta institucional 404 / recurso no encontrado.
 *
 * Sustituye a la redireccion silenciosa a /dashboard para que una ruta
 * inexistente no se presente como una pagina valida.
 */
const NotFound = () => {
  return (
    <>
      <PageTitle motherMenu="Inicio" activeMenu="Página no encontrada" />

      <div className="row">
        <div className="col-xl-8 col-lg-10">
          <div className="card">
            <div className="card-body text-center">
              <span className="fs-50 text-warning">
                <i className="flaticon-072-printer"></i>
              </span>
              <h2 className="mt-3 mb-2">404</h2>
              <h4 className="card-title">Página no encontrada</h4>
              <p className="text-muted mb-0">
                La dirección solicitada no corresponde a un recurso existente
                en el Sistema de Mesa de Ayuda TI. Verifique el enlace
                utilizado o regrese al Dashboard.
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

export default NotFound;