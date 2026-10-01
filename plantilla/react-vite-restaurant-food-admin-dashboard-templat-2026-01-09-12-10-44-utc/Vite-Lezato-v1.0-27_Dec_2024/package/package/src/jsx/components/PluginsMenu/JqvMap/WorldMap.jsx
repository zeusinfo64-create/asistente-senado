import React, { Fragment } from "react";
import { ComposableMap, Geographies, Geography } from "react-simple-maps";

// const geoUrl = "https://raw.githubusercontent.com/d3/d3-geo/master/test/data/world-110m.json";

const WorldMap = () => {
	
  return (
    <Fragment>
      <ComposableMap>
          <Geographies geography="./features.json">
            {({ geographies }) =>
              geographies.map((geo) => (
                <Geography key={geo.rsmKey} geography={geo} />
              ))
            }
          </Geographies>
      </ComposableMap>
    </Fragment>
	
  );
};

export default WorldMap;
