
import { useContext } from "react";

/// Image
import avatar from "../../assets/images/avatar/profile-1.jpg";
import { Dropdown } from "react-bootstrap";
import { ThemeContext } from "../../context/ThemeContext";
import { AuthContext } from "../../context/AuthContext";
import { getPrimaryRoleLabel } from "../../utils/roles";

const Header = () => {
  const { background, changeBackground } = useContext(ThemeContext);
  const { user, logout, loggingOut } = useContext(AuthContext);

  const isDark = background.value === "dark";

  return (
    <div className="header">
      <div className="header-content">
        <nav className="navbar navbar-expand">
          <div className="collapse navbar-collapse justify-content-between">
            <div className="header-left">
              <li className="nav-item">
                <div className="input-group search-area">
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Buscar"
                    aria-label="Buscar"
                  />
                  <span className="input-group-text">
                    <i className="flaticon-381-search-2"></i>
                  </span>
                </div>
              </li>
            </div>
            <ul className="navbar-nav header-right main-notification">
              <li className="nav-item">
                <button
                  type="button"
                  className="nav-link ai-icon c-pointer theme-toggle"
                  onClick={() =>
                    changeBackground(
                      isDark
                        ? { value: "light", label: "Light" }
                        : { value: "dark", label: "Dark" }
                    )
                  }
                  aria-label="Cambiar tema"
                  title="Cambiar tema"
                >
                  <svg
                    width={20}
                    height={20}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    {isDark ? (
                      <circle cx="12" cy="12" r="5" />
                    ) : (
                      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                    )}
                  </svg>
                </button>
              </li>
              <Dropdown as="li" className="nav-item dropdown header-profile">
                <Dropdown.Toggle
                  variant=""
                  as="a"
                  className="nav-link i-false c-pointer"
                  role="button"
                  data-toggle="dropdown"
                >
                  <img src={avatar} width={20} alt="" />
                </Dropdown.Toggle>

                <Dropdown.Menu align="right" className="mt-3 dropdown-menu dropdown-menu-end">
                  <div className="px-3 py-2 border-bottom">
                    <p className="text-dark mb-0 font-w600 text-truncate">
                      {user?.full_name}
                    </p>
                    <p className="text-muted mb-0 fs-12 text-truncate">
                      {user?.email}
                    </p>
                    <span className="badge badge-primary mt-2">
                      {getPrimaryRoleLabel(user)}
                    </span>
                  </div>
                  <button
                    type="button"
                    className="dropdown-item ai-icon"
                    onClick={logout}
                    disabled={loggingOut}
                  >
                    <svg
                      id="icon-logout"
                      xmlns="http://www.w3.org/2000/svg"
                      className="text-primary"
                      width={18}
                      height={18}
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth={2}
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span className="ms-2">
                      {loggingOut ? "Cerrando sesión..." : "Cerrar sesión"}
                    </span>
                  </button>
                </Dropdown.Menu>
              </Dropdown>
            </ul>
          </div>
        </nav>
      </div>
    </div>
  );
};

export default Header;
