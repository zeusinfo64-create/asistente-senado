import { Fragment, useContext, useState } from "react";

/// React router dom
import { Link } from "react-router-dom";
import { ThemeContext } from "../../context/ThemeContext";

export function NavMenuToggle() {
  setTimeout(() => {
    const mainwrapper = document.querySelector("#main-wrapper");
    if (mainwrapper.classList.contains("menu-toggle")) {
      mainwrapper.classList.remove("menu-toggle");
    } else {
      mainwrapper.classList.add("menu-toggle");
    }
  }, 200);
}

const NavHader = () => {
  const [toggle, setToggle] = useState(false);
  const { navigationHader, background } = useContext(ThemeContext);

  const isDarkBrand = background.value === "dark" || navigationHader !== "color_1";

  return (
    <div className="nav-header">
      <Link to="/dashboard" className="brand-logo">
        {isDarkBrand ? (
          <Fragment>
            <svg
              className="logo-abbr"
              width="56"
              height="56"
              viewBox="0 0 56 56"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                className="rect-primary-rect"
                d="M0 20C0 8.95431 8.95431 0 20 0H36C47.0457 0 56 8.95431 56 20V36C56 47.0457 47.0457 56 36 56H20C8.95431 56 0 47.0457 0 36V20Z"
                fill="url(#paint0_linear)"
              />
              <path
                d="M18 22a10 10 0 0 1 20 0v6h1.5A2.5 2.5 0 0 1 42 30.5v5A2.5 2.5 0 0 1 39.5 38H18a4 4 0 0 1-4-4v-6a4 4 0 0 1 4-4V22Zm4 0a6 6 0 0 1 12 0v6H22v-6Z"
                fill="white"
              />
              <path
                d="M38 42a6 6 0 0 1-12 0h12Z"
                fill="white"
                transform="translate(6 0)"
              />
              <defs>
                <linearGradient
                  id="paint0_linear"
                  x1="28"
                  y1="0"
                  x2="28"
                  y2="56"
                  gradientUnits="userSpaceOnUse"
                />
              </defs>
            </svg>
            <span className="brand-title-text">Mesa de Ayuda TI</span>
          </Fragment>
        ) : (
          <Fragment>
            <svg
              className="logo-abbr"
              width="56"
              height="56"
              viewBox="0 0 56 56"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                className="rect-primary-rect"
                d="M0 20C0 8.95431 8.95431 0 20 0H36C47.0457 0 56 8.95431 56 20V36C56 47.0457 47.0457 56 36 56H20C8.95431 56 0 47.0457 0 36V20Z"
                fill="url(#paint0_linear)"
              />
              <path
                d="M18 22a10 10 0 0 1 20 0v6h1.5A2.5 2.5 0 0 1 42 30.5v5A2.5 2.5 0 0 1 39.5 38H18a4 4 0 0 1-4-4v-6a4 4 0 0 1 4-4V22Zm4 0a6 6 0 0 1 12 0v6H22v-6Z"
                fill="white"
              />
              <path
                d="M38 42a6 6 0 0 1-12 0h12Z"
                fill="white"
                transform="translate(6 0)"
              />
              <defs>
                <linearGradient
                  id="paint0_linear"
                  x1="28"
                  y1="0"
                  x2="28"
                  y2="56"
                  gradientUnits="userSpaceOnUse"
                />
              </defs>
            </svg>
            <span className="brand-title-text">Mesa de Ayuda TI</span>
          </Fragment>
        )}
      </Link>

      <div
        className="nav-control"
        onClick={() => {
          setToggle(!toggle);
          NavMenuToggle();
        }}
      >
        <div className={`hamburger ${toggle ? "is-active" : ""}`}>
          <span className="line"></span>
          <span className="line"></span>
          <span className="line"></span>
        </div>
      </div>
    </div>
  );
};

export default NavHader;
