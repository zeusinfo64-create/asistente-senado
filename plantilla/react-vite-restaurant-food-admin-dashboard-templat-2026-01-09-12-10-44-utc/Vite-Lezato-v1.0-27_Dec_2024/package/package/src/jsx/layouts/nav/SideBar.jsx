/// Menu
import React, { useContext, useEffect,useReducer, useState } from "react";
/// Scroll
import PerfectScrollbar from "react-perfect-scrollbar";
import {Collapse} from 'react-bootstrap';
/// Link
import { Link } from "react-router-dom";
import {useScrollPosition} from "@n8tb1t/use-scroll-position";
import { ThemeContext } from "../../../context/ThemeContext";
import {MenuList} from './Menu';

/// Image
import plus from "../../../assets/images/plus.png";


const reducer = (previousState, updatedState) => ({
  ...previousState,
  ...updatedState,
});

const initialState = {
  active : "",
  activeSubmenu : "",
}

const SideBar = () => {
	var d = new Date();
	
  const {
		iconHover,
		sidebarposition,
		headerposition,
		sidebarLayout,
    ChangeIconSidebar,
  
	} = useContext(ThemeContext);

  const [state, setState] = useReducer(reducer, initialState);	
  let handleheartBlast = document.querySelector('.heart');
  function heartBlast(){
    return handleheartBlast.classList.toggle("heart-blast");
  }
  
 	const [hideOnScroll, setHideOnScroll] = useState(true)
	useScrollPosition(
		({ prevPos, currPos }) => {
		  const isShow = currPos.y > prevPos.y
		  if (isShow !== hideOnScroll) setHideOnScroll(isShow)
		},
		[hideOnScroll]
	)

 
	const handleMenuActive = status => {		
		setState({active : status});			
		if(state.active === status){				
			setState({active : ""});
		}   
	}
	const handleSubmenuActive = (status) => {		
		setState({activeSubmenu : status})
		if(state.activeSubmenu === status){
			setState({activeSubmenu : ""})			
		}    
	}
  
  /// Path
  let path = window.location.pathname;
  path = path.split("/");
  path = path[path.length - 1];
  /// Active menu
  useEffect(() => {
    MenuList.forEach((data) => {
      data.content?.forEach((item) => {        
        if(path === item.to){         
          setState({active : data.title})          
        }
        item.content?.forEach(ele => {
          if(path === ele.to){
            setState({activeSubmenu : item.title, active : data.title})
          }
        })
      })
  })
  },[path]);
  
  return (
    <div 
      onMouseEnter={()=>ChangeIconSidebar(true)}
      onMouseLeave={()=>ChangeIconSidebar(false)}
      className={`deznav  border-right ${iconHover} ${
        sidebarposition.value === "fixed" &&
        sidebarLayout.value === "horizontal" &&
        headerposition.value === "static"
          ? hideOnScroll > 120
            ? "fixed"
            : ""
          : ""
      }`}
    >   
      
      <PerfectScrollbar className="deznav-scroll">
          <ul className="metismenu" id="menu">              
              {MenuList.map((data, index)=>{
                let menuClass = data.classsChange;
                  if(menuClass === "menu-title"){
                    return(
                        <li className={menuClass}  key={index} > {data.title}</li>
                    )
                  }else{
                    return(				
                      <li className={` ${ state.active === data.title ? 'mm-active' : ''} ${data.to === path ? 'mm-active' : ''}`}
                        key={index} 
                      >                        
                        {data.content && data.content.length > 0 ?
                            <>
                              <Link to={"#"} 
                                className="has-arrow"
                                onClick={() => {handleMenuActive(data.title)}}
                              >								
                                  {data.iconStyle}{" "}
                                  <span className="nav-text">{data.title}</span>
                              </Link>
                          
                              <Collapse in={state.active === data.title ? true :false}>
                                <ul className={`${menuClass === "mm-collapse" ? "mm-show" : ""}`}>
                                  {data.content && data.content.map((data,subindex) => {									
                                    return(	
                                        <li key={subindex}
                                          className={`${ state.activeSubmenu === data.title ? "mm-active" : ""} ${data.to === path ? 'mm-active' : ''}`}                                    
                                        >
                                          {data.content && data.content.length > 0 ?
                                              <>
                                                <Link to={data.to} className={data.hasMenu ? 'has-arrow' : ''}
                                                  onClick={() => { handleSubmenuActive(data.title)}}
                                                >
                                                  {data.title}
                                                </Link>
                                                <Collapse in={state.activeSubmenu === data.title ? true :false}>
                                                    <ul className={`${menuClass === "mm-collapse" ? "mm-show" : ""}`}>
                                                      {data.content && data.content.map((data,indexSub) => {
                                                        return(	
                                                          
                                                            <li key={indexSub}>
                                                              <Link className={`${path === data.to ? "mm-active" : ""}`} to={data.to}>{data.title}</Link>
                                                            </li>
                                                        
                                                        )
                                                      })}
                                                    </ul>
                                                </Collapse>
                                              </>
                                            :
                                            <Link to={data.to} className={`${data.to === path ? 'mm-active' : ''}`}>
                                              {data.title}
                                            </Link>
                                        }                                    
                                      </li>                                
                                    )
                                  })}
                                </ul>
                              </Collapse>
                            </>
                            :
                            <Link  to={data.to}   className={`${data.to === path ? 'mm-active' : ''}`}>
                                {data.iconStyle}
                                <span className="nav-text">{data.title}</span>
                            </Link>
                          }
                      </li>	
                    )
                }
              })}          
          </ul>		
       
        <div className="plus-box">
          <img src={plus} alt="" />
          <h5 className="fs-18 font-w700">Add Menus</h5>
          <p className="fs-14 font-w400">Manage your food <br />and beverages menus<i className="fas fa-arrow-right ms-3"></i></p>
        </div>
        <div className="copyright">
          <p><strong>Lezato Restaurant Admin</strong> © {d.getFullYear()} All Rights Reserved</p>
          <p className="fs-12">Made with <span className="heart"
            onClick={()=>heartBlast()}
          ></span> by DexignZone</p>
        </div>
      </PerfectScrollbar> 
    </div>
  );
};

export default SideBar;
