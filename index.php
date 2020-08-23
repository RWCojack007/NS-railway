<!DOCTYPE html>
<html lang="nl">
	<head>
		<meta charset="utf-8">
		<link rel="icon" href="https://beagle-consulting.nl/wp-content/uploads/2020/06/favicon_klein-150x150.png">

		<title>NS spoorwegen</title>
		<style type="text/css">

      @font-face {
        font-family: "NS Sans";
        src: url("fonts/nssans__-webfont.ttf");
      }

			svg {
				background-color: white;
			}

			h1, h2 {
				color: #003082; /* NS dark blue */
				font-family: "NS Sans", sans-serif;
			}

			h1 {
				font-size: 32px;
				margin: 0;
				padding-bottom: 10px;
			}

      h3 {
        color: rgb(115, 115, 115);
        font-size: 12px;
        font-family: sans-serif;
        font-weight: normal;
        margin: 0;
        padding-bottom: 10px;
      }

      #container {
  				width: 800px;
  				margin-left: auto;
  				margin-right: auto;
  				margin-top: 20px;
  				padding: 20px;
			}


      .tooltip {
        position: absolute;
        text-align: center;
        padding: 15px;
        font: 12px sans-serif;
        background: white;
        border: 0px;
        border-radius: 4px;
        pointer-events: none;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
      }

      .tooltip > h1 {
				color: #FFFFFF; /* White */
				padding: 5px 15px;
				margin-bottom: 15px;
        font-size: 16px;
        font-family: "NS Sans", sans-serif;
				background-color: #003082; /* NS dark blue */
				border-radius: 4px;
      }

			.tooltip > h2 {
				text-align: left;
			}

      .tooltip > img {
        max-width: 200px;
        height: auto;
				border:1px solid #e5e5e5;
      }

      .gemeente {
        fill: #e5e5e5;
        stroke-width: .2;
        stroke: #fff;
      }

      .line {
        fill: none;
        stroke-width: 2;
        stroke: #003082; /* NS dark blue */
      }

      .station {
        fill: #003082; /* NS dark blue */
      }

      .data {
        opacity: 0.3;
      }

		</style>
	</head>
	<body>
    <script src="https://d3js.org/d3.v4.min.js"></script>

  	<div id="container">
  		<h1>NS spoorwegen</h1>
      <h3>Onderstaande kaart geeft het treinennet weer van Nederland. Klik op een station voor extra gegevens.</h3>
      <h3>De data is geactualiseerd op: <span id='actualized'></span></h3>

			<br>

			<div id="map"></div>
  	</div>


		<script type="text/javascript">

			//width and height
			var width = 800;
			var height = 600;

      svg = d3.select("#map")
          .append("svg")
          .attr('width', width)
          .attr('height', height);

      var tooltip = d3.select('body').append('div')
          .style('opacity',0)
          .attr('class', 'tooltip');

			var localeTime = d3.timeFormatLocale({
		      dateTime: "%a %b %e %X %Y",
		      date: "%d-%m-%Y",
		      time: "%H:%M:%S",
		      periods: ["AM", "PM"],
		      days: ["zondag", "maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag"],
		      shortDays: ["zo", "ma", "di", "wo", "do", "vr", "za"],
		      months: ["januari", "februari", "maart", "april", "mei", "juni", "juli", "augustus", "september", "oktober", "november", "december"],
		      shortMonths: ["jan", "feb", "mar", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec"]
	    });

      // get train data from csv (created by Python)
      var traindata = [];
      d3.csv("data/NLrailway.csv", function(error, data){
          if (error) throw error;

          // reformat data types from csv (default: all values are strings)
          var parseDate = d3.timeParse("%Y-%m-%d %H:%M:%S");
          data.forEach(function(d) {
            d.TimeStamp = parseDate(d.TimeStamp);
            d[""] = Number(d[""]);
						d.travelers_num === NaN ? d.travelers_num = 100 : d.travelers_num = Number(d.travelers_num);
            d.id = Number(d.id);
            d.geo_lat = Number(d.geo_lat);
            d.geo_lng = Number(d.geo_lng);
            d.uic = Number(d.uic);
          });

          traindata = data;
					max_trav = d3.max(data, d => d.travelers_num);
					min_trav = d3.min(data, d => d.travelers_num);

      });

      d3.json("data/nld.geojson", function(error, data) {
          if (error) throw error;

          projection = d3.geoMercator()
                .fitSize([width, height], data);

          path = d3.geoPath()
                .projection(projection);

          svg.append("g")
              .selectAll("path")
              .data(data.features).enter()
              .append("path")
                .attr("class","gemeente")
                .attr("d", path);

					// update "actualized"-section
					d3.select('#actualized')
							.text(localeTime.format("%A %e %B %Y om %H:%M:%S")(traindata[0].TimeStamp));

          // group the data: I want to draw one line per group
          var dataLine = d3.nest() // nest function allows to group the calculation per level of a factor
            .key(function(d) { return d.line;})
            .entries(traindata);

          // console.log(traindata);

          // graph_ytd_line
          var line = svg.selectAll(".line")
            .data(dataLine)
            .enter()
              .append("g")
                .attr('class', function(d) { return 'data ' + d.key.replace(" ", "").toLowerCase()})

                .on('mouseover', function(d) {
                  d3.select(this).transition().duration(200)
                    .style('opacity', 1)
                })
                .on('mouseout', function(d) {
                  d3.select(this).transition().duration(200)
                    .style('opacity', 0.3)
                })

								.on('click', function(d) {
									tooltip.transition().duration(200)
										.style('opacity', 1)

									tooltip.html('<h2>Traject '+d.key+'</h2>')
	                  .style('left', (d3.event.pageX) - 75 + 'px')
	                  .style('top', (d3.event.pageY) + 50 + 'px')
								})
								;

          // draw the line
          line.append("path")
            .attr("class", function(d) { return "line line_" + d.key.replace(" ", "").toLowerCase() ; })
            .attr("d", function(d){
              return d3.line()
                .x(function(d) { return projection([d.geo_lng,d.geo_lat])[0]; })
                .y(function(d) { return projection([d.geo_lng,d.geo_lat])[1]; })
                (d.values)
              });

          line.selectAll('.station')
            .data(function(d){return d.values}).enter()
            .append("circle")
              .attr("class", function(d) { return "station station_" + this.parentNode.__data__.key.replace(" ", "").toLowerCase() ; })
              .attr("cx", function (d) { return projection([d.geo_lng,d.geo_lat])[0]; })
              .attr("cy", function (d) { return projection([d.geo_lng,d.geo_lat])[1]; })
							.attr("r", function (d) { return Math.round(2 + (1-(max_trav-d.travelers_num)/(max_trav-min_trav))*4); })

              .on('mouseover', function(d) {
                tooltip.transition().duration(200)
                  .style('opacity', 1)

                tooltip.html('<h1>' + d.name_long + '</h1><img src="data/pictures/' + d.slug + '.jpg">')
                  .style('left', (d3.event.pageX) - 75 + 'px')
                  .style('top', (d3.event.pageY) + 50 + 'px')

                d3.select(this).transition().duration(200)
                  .style('r', 4)
              })
              .on('mouseout', function(d) {
                tooltip.transition().duration(500)
                  .style('opacity', 0)

                d3.select(this).transition().duration(200)
                  .style('r', 2)
              });

      });

		</script>
	</body>
</html>
