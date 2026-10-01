import PropTypes from "prop-types";
import { Link } from "react-router-dom";

const PageTitle = ({ motherMenu, activeMenu }) => {
  return (
    <div className="row page-titles mx-0">
      <ol className="breadcrumb">
        <li className="breadcrumb-item active">
          <Link to="/dashboard">{motherMenu}</Link>
        </li>
        <li className="breadcrumb-item">
          <Link to="/dashboard">{activeMenu}</Link>
        </li>
      </ol>
    </div>
  );
};

PageTitle.propTypes = {
  motherMenu: PropTypes.string.isRequired,
  activeMenu: PropTypes.string.isRequired,
};

export default PageTitle;