import { useContext, useEffect, useMemo, useState } from "react";

import { Collapse } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom";
import { ThemeContext } from "../../context/ThemeContext";
import { AuthContext } from "../../context/AuthContext";
import { getMenuGroupsForUser } from "./Menu";

/**
 * Sidebar institucional.
 *
 * No declara rutas ni roles: todo se deriva del catálogo declarativo de
 * navegación (Menu.jsx), filtrado con los roles reales del usuario.
 * - la visibilidad por rol la resuelve `getMenuGroupsForUser`;
 * - el estado operativo de cada opción viene de `item.enabled`;
 * - las opciones no operativas se muestran sin enlace para que no naveguen
 *   a paginas inexistentes.
 *
 * La autorizacion efectiva sigue correspondiendo al backend.
 */
const SideBar = () => {
  const d = new Date();

  const { iconHover, ChangeIconSidebar } = useContext(ThemeContext);
  const { user } = useContext(AuthContext);

  const [openGroup, setOpenGroup] = useState("Dashboard");

  const visibleMenu = useMemo(() => getMenuGroupsForUser(user), [user]);

  const { pathname } = useLocation();

  const toggleGroup = (key) => {
    setOpenGroup((current) => (current === key ? "" : key));
  };

  // Abre el grupo que contiene la ruta activa y resalta la opcion visitada.
  useEffect(() => {
    const activeGroup = visibleMenu.find((group) =>
      group.items.some((item) => item.path === pathname)
    );

    if (activeGroup) {
      setOpenGroup(activeGroup.key);
    }
  }, [pathname, visibleMenu]);

  return (
    <div
      onMouseEnter={() => ChangeIconSidebar(true)}
      onMouseLeave={() => ChangeIconSidebar(false)}
      className={`deznav border-right ${iconHover}`}
    >
      <div className="deznav-scroll">
        <ul className="metismenu" id="menu">
          {visibleMenu.map((group) => (
            <li
              className={`${openGroup === group.key ? "mm-active" : ""}`}
              key={group.key}
            >
              <Link
                to="#"
                className="has-arrow"
                onClick={() => toggleGroup(group.key)}
              >
                <i className={group.icon}></i> <span className="nav-text">{group.label}</span>
              </Link>

              <Collapse in={openGroup === group.key}>
                <ul className="mm-show">
                  {group.items.map((item) => {
                    if (!item.enabled) {
                      return (
                        <li key={item.key}>
                          <span
                            className="nav-text opacity-50"
                            aria-disabled="true"
                          >
                            {item.label}
                          </span>
                        </li>
                      );
                    }

                    return (
                      <li
                        className={`${pathname === item.path ? "mm-active" : ""}`}
                        key={item.key}
                      >
                        <Link to={item.path}>{item.label}</Link>
                      </li>
                    );
                  })}
                </ul>
              </Collapse>
            </li>
          ))}
        </ul>

        <div className="copyright">
          <p>
            <strong>Mesa de Ayuda TI</strong> &copy; {d.getFullYear()}
          </p>
          <p className="fs-12">Unidad de Tecnologías de Información</p>
        </div>
      </div>
    </div>
  );
};

export default SideBar;