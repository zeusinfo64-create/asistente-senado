
import { useContext, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import { extractApiErrorMessage } from "../../api/axios";
import { AuthContext } from "../../context/AuthContext";

/**
 * Pantalla institucional de inicio de sesion de Mesa de Ayuda TI.
 * Diseno propio: no reutiliza la pagina de login del template original.
 */
const Login = () => {
  const { login } = useContext(AuthContext);
  const navigate = useNavigate();
  const location = useLocation();

  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");

  const handleSubmit = async (event) => {
    event.preventDefault();

    // Evita envios incompletos: el backend sigue siendo la autoridad.
    if (!username.trim() || !password) {
      setErrorMessage("Ingrese su usuario y contraseña.");

      return;
    }

    setSubmitting(true);
    setErrorMessage("");

    try {
      await login({ username: username.trim(), password });

      setPassword("");

      const from = location.state?.from?.pathname;

      navigate(from && from !== "/login" ? from : "/dashboard", { replace: true });
    } catch (error) {
      setErrorMessage(
        extractApiErrorMessage(error, "No se pudo iniciar sesión. Intente nuevamente.")
      );
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="authincation">
      <div className="authincation-content w-100">
        <div className="row g-0 h-100">
          <div className="col-lg-5 d-none d-lg-flex flex-column justify-content-between welcome-content">
            <div className="brand-logo">
              <span className="mb-0 d-block">Cámara de Senadores de Bolivia</span>
            </div>
            <div>
              <h2 className="welcome-title">Mesa de Ayuda TI</h2>
              <p className="mb-0">
                Sistema institucional de gestión de solicitudes de soporte
                tecnológico.
              </p>
              <p className="mt-3 mb-0">
                El acceso está habilitado para el personal de la Cámara de
                Senadores.
              </p>
            </div>
          </div>

          <div className="col-lg-7">
            <div className="auth-form d-flex flex-column justify-content-center">
              <div className="text-center mb-4 d-lg-none">
                <h3 className="font-w700 mb-1">Mesa de Ayuda TI</h3>
                <p className="text-muted mb-0 fs-13">
                  Unidad de Tecnologías de Información
                </p>
              </div>

              <div className="mb-4 d-none d-lg-block">
                <h3 className="text-dark font-w700 mb-1">Iniciar sesión</h3>
                <p className="text-muted mb-0">
                  Ingrese con sus credenciales institucionales para acceder al
                  sistema.
                </p>
              </div>

              {errorMessage ? (
                <div className="alert alert-danger" role="alert">
                  {errorMessage}
                </div>
              ) : null}

              <form onSubmit={handleSubmit} noValidate>
                <div className="form-group">
                  <label htmlFor="login-username" className="text-muted mb-1">
                    Usuario
                  </label>
                  <input
                    id="login-username"
                    name="username"
                    type="text"
                    className="form-control"
                    placeholder="usuario institucional"
                    autoComplete="username"
                    value={username}
                    disabled={submitting}
                    onChange={(event) => setUsername(event.target.value)}
                  />
                </div>

                <div className="form-group">
                  <label htmlFor="login-password" className="text-muted mb-1">
                    Contraseña
                  </label>
                  <input
                    id="login-password"
                    name="password"
                    type="password"
                    className="form-control"
                    placeholder="Ingrese su contraseña"
                    autoComplete="current-password"
                    value={password}
                    disabled={submitting}
                    onChange={(event) => setPassword(event.target.value)}
                  />
                </div>

                <button
                  type="submit"
                  className="btn btn-primary btn-block"
                  disabled={submitting}
                >
                  {submitting ? "Verificando credenciales..." : "Iniciar sesión"}
                </button>
              </form>

              <p className="text-muted text-center mt-4 mb-0 fs-13">
                Mesa de Ayuda TI · Unidad de Tecnologías de Información
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
