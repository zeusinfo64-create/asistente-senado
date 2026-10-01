import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";

import logo from "../../assets/images/logo-full.png";

const LockScreen = () => {
  const navigate = useNavigate();
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);

  const handleChange = (e) => {
    setPassword(e.target.value);
  };

  const submitHandler = (e) => {
    e.preventDefault(); 
    navigate("/dashboard");
  };

  return (
    <div className="authincation">
      <div className="container">
        <div className="row justify-content-center h-100 align-items-center">
          <div className="col-md-6">
            <div className="authincation-content">
              <div className="row no-gutters">
                <div className="col-xl-12">
                  <div className="auth-form">
                    <div className="text-center mb-3">
                      <Link to="/dashboard">
                        <img src={logo} alt="Logo" />
                      </Link>
                    </div>
                    <h4 className="text-center mb-4">Account Locked</h4>
                    <form onSubmit={submitHandler}>
                      <div className="form-group mb-3">
                        <label>
                          <strong>Password<span className="required">*</span></strong>
                        </label>
                        <div className="input-group transparent-append mb-2">
                          <input
                            type={showPassword ? "text" : "password"}
                            className="form-control"
                            id="val-password1"
                            name="password"
                            value={password}
                            onChange={handleChange}
                            placeholder="Enter your password"
                          />
                          <div className="input-group-text show-validate bg-primary" onClick={() => setShowPassword(!showPassword)} >
                               {" "}
                                {showPassword === false ? (<i className="fa fa-eye-slash text-white" />) : (<i className="fa fa-eye text-white" />)}
                          </div>
                        </div>
                      </div>
                      <div className="text-center">
                        <button type="submit" className="btn btn-primary btn-block">
                          Unlock
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LockScreen;
