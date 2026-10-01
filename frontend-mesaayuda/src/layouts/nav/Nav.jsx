import { Fragment } from "react";
/// Layout
import SideBar from "./Sidebar";
import NavHader from "./NavHeader";
import Header from "./Header";

const Nav = () => {
  return (
    <Fragment>
      <NavHader />
      <Header />
      <SideBar />
    </Fragment>
  );
};

export default Nav;