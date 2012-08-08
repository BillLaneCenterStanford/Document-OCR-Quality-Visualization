<!DOCTYPE html>
<?php include("backend.php"); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Texas Newspaper Collection</title>


<!-- Dependencies --> 


<script type="text/javascript" src="http://cufon.shoqolate.com/js/cufon-yui.js?v=1.09i"></script>
<script type="text/javascript" src="./Glarendon_500.font.js"></script>

		<script type="text/javascript">
			Cufon.replace('h1'); // Works without a selector engine
			Cufon.replace('#cityname'); // Requires a selector engine for IE 6-7, see above
		</script>
		
		<script type="text/javascript"> Cufon.now(); </script>

<script type="text/javascript" src="./config.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>
<script type="text/javascript" src="http://api.simile-widgets.org/timeline/2.3.1/timeline-api.js"></script> 
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="http://vis.stanford.edu/protovis/protovis-r3.2.js"></script>

<link rel="stylesheet" type="text/css" href="./commonFromSimile.css"/> 

<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/themes/base/jquery-ui.css">

<link rel="stylesheet" type="text/css" href="style.css"/>

<script type="text/javascript" src="mousehold.js"></script>
<script type="text/javascript" src="./jquery.qtip.min.js"></script> 




<script type="text/javascript">

/*****************************************************************************/
// global data section
/*****************************************************************************/
var statsByPub  = <?php getStatsByPub(); ?>;
var statsByCity = <?php getStatsByCity(); ?>;
var statsByYear = <?php getStatsByYear(); ?>;


var minYear = config.yearRange.min;
var maxYear = config.yearRange.max;

var colorRamp = config.colorRamp;
var colorRampThreshold = config.colorRampThreshold;

// global state variables wrapped together
var currentState = {
    // update city through update function below
    city: [config.defaultCity],
    // never updated
    state: config.defaultState,
    // update year range through google timeline
    yearRangeMin: minYear,
    yearRangeMax: maxYear,
    // update color range through color legend
    colorRangeMin: 0,
    colorRangeMax: colorRampThreshold.length - 1,
    // update marker size scale through <select>
    markerSizeScale: 'log',
    // current map type, default to TERRAIN
    mapTypeId: google.maps.MapTypeId.TERRAIN,
};

var isInteger = {
    city: false,
    state: false,
    yearRangeMin: true,
    yearRangeMax: true,
    colorRangeMin: true,
    colorRangeMax: true,
    markerSizeScale: false,
};

// global widgets and structures
var map = null;
var markers = [];
var timeline = null;
var simile_timeline;    // for simile timeline
var simile_resizeTimerID = null;
var shifted; // If shifted is pressed


var dateFormat = pv.Format.date("%y");
var w = 250;
var h = 170;
var x = pv.Scale.linear(dateFormat.parse("1829"),dateFormat.parse("2008")).range(0, w);
var y = pv.Scale.linear(0, 1).range(0, h);
var vis = new pv.Panel().width(w).height(h).bottom(20).left(30).right(10).top(5).canvas('area');
var vistype = "zoomed"; // What visualization method are they using?
var pubTrendByYear = getTrendByYear(statsByPub, minYear, maxYear);

var bestfirst;
var bestlast;
/*****************************************************************************/
// js method section
/*****************************************************************************/
// include google visualization widgets
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});

$(document).ready(function () {
    URLToCurrentState();
    drawTitleBlock();
    drawLegend();
    drawMap();
    drawTimeline();
    drawSimileTimeline();
    
  $('a[tooltip]').each(function()
   {
      $(this).qtip({
         content: name($(this).attr('tooltip')), // Use the tooltip attribute of the element for the content
         position: {
		      corner: {
		         target: 'rightMiddle',
		         tooltip: 'leftMiddle'
		      }
		   },
		       show: { 
            solo: true // Only show one tooltip at a time
         },
         style: {
         	name: 'cream', // Give it a crea mstyle to make it stand out
         	tip: 'leftMiddle',
         },
           
           hide: 'unfocus'
           
      });
   });
   
   
    $(".help").qtip(
      {
         content: {
            // Set the text to an image HTML string with the correct src URL to the loading image you want to use
            //text: '<img class="throbber" src="throbber.gif" alt="Loading..." />',
            url: 'help.html', // Use the rel attribute of each element for the url to load
            title: {
               text: 'Help', // Give the tooltip a title using each elements text
               button: 'Close' // Show a close link in the title
            }
         },
         position: {
            corner: {
               target: 'bottomMiddle', // Position the tooltip above the link
               tooltip: 'topMiddle'
            },
            adjust: {
               screen: true // Keep the tooltip on-screen at all times
            }
         },
       
         hide: 'unfocus',
         style: {
            tip: true, // Apply a speech bubble tip to the tooltip at the designated tooltip corner
            border: {
               width: 0,
               radius: 4
            },
            name: 'light', // Use the default light style
            width: 300 // Set the tooltip width
         }
      })
});

/*****************************************************************************/
// functions used to draw parts on the screen
/*****************************************************************************/
function drawTitleBlock() {
    // read contents from config.js
    // add generate title block accordingly
    var title_div = $("#title_block");

    title_div.append($('<div class="content"><h1>' + config.title + '</h1></div>'));
    title_div.append($('<div class="content"><h3>' + config.subTitle + '</h3></div>'));
    title_div.append($('<div class="content"><p>' + config.introText + '</p></div>'))
}

function drawTimeline() {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'Date');
    data.addColumn('number', 'Total Words Scanned');
    data.addColumn('string', 'title1');
    data.addColumn('string', 'text1');
    data.addColumn('number', 'Correct Words Scanned');
    data.addColumn('string', 'title2');
    data.addColumn('string', 'text2');

    data.addRows(statsByYear.length);
    for (var i = 0; i < statsByYear.length; i++) {
        var year = parseInt(statsByYear[i]["year"]);
        var good = parseInt(statsByYear[i]["good"]);
        var total = parseInt(statsByYear[i]["total"]);
        data.setValue(i, 0, new Date(year, 1, 1));
        data.setValue(i, 1, total);
        data.setValue(i, 4, good);
    }

    timeline = new google.visualization.AnnotatedTimeLine(
        document.getElementById('timeline_vis'));
    timeline.draw(data, {
                      'displayAnnotations': true,
                      'zoomStartTime': new Date(currentState.yearRangeMin, 1, 1),
                      'zoomEndTime': new Date(currentState.yearRangeMax, 1, 1),
                  });
    google.visualization.events.addListener(
        timeline,
        'rangechange',
        onYearRangechange);
}

function drawMap() {
    var myLatlng = new google.maps.LatLng(
        config.map.center.lat,
        config.map.center.lng);

    var myMapTypeId = "Dark";
    var myMapTypeStyle = [
        {
            featureType: "all",
            elementType: "all",
            stylers: [
                {
                    invert_lightness: true,
                },
            ],
        },
        {
            featureType: "administrative",
            elementType: "all",
            stylers: [
                {
                    visibility: 'off',
                },
            ],
        },
        {
            featureType: "road",
            elementType: "all",
            stylers: [
                {
                    visibility: 'off',
                },
            ],
        },

    ];

    var myOptions = {
      zoom: config.map.initialZoom,
      center: myLatlng,
      streetViewControl: false,
      panControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
      },
      zoomControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
      },
      mapTypeControlOptions: {
          mapTypeIds: [google.maps.MapTypeId.TERRAIN,
                       google.maps.MapTypeId.ROADMAP,
                       myMapTypeId],
      },
      mapTypeId: currentState.mapTypeId,
    };

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    map.mapTypes.set(myMapTypeId, new google.maps.StyledMapType(myMapTypeStyle, {name: myMapTypeId}));

    google.maps.event.addListener(map, 'maptypeid_changed', function() {
        onMapTypeChange();
    });

    drawContour(map);

    updateCity(currentState.city);
    drawMarkers(statsByCity);
}

function drawLegend() {
    drawColorLegend();
    drawSizeLegend();
}

function drawColorLegend() {
    var canvas = document.getElementById('legend_color_canvas');
    if (canvas.getContext) {
        var ctx = canvas.getContext('2d');
 
        var x = 0;
        var y = 0;
        var s = Math.round(canvas.width / colorRamp.length) - 1;
        for (var i = 0; i < colorRamp.length; i++) {
            ctx.fillStyle = colorRamp[i];
            ctx.fillRect(x + i * s, y, s, s);
        }
            
        $("#legend_slider").slider({
            orientation: "horizontal",
            max: colorRamp.length - 1,
            range: true,
            values: [currentState.colorRangeMin, currentState.colorRangeMax],
            step: 1,
            change: onColorRangeChange,
        });

        onColorRangeChange();
    }
    else {
        alert('need better browser');
    }
}

function drawSizeLegend() {
    // check corresponding scale selector
    var scale = currentState.markerSizeScale;
    $('input[name=scale_select][value='+scale+']').attr('checked', true);

    // draw size legend chart, according to currentState.markerSizeScale
    drawSizeLegendChart();

    // add event listener
    $('input[name=scale_select]').change(function () {
        currentState.markerSizeScale = $('input[name=scale_select]:checked').val();
        $('#legend_size').fadeOut('slow', function() {
            onMarkerSizeScaleChange();
        });
        $('#legend_size').fadeIn('slow');
    });
}

function drawSizeLegendChart() {
    if ($('#legend_size').children().length > 0) {
        $('#legend_size').children().remove();
    }

    var element = $('<div></div>');
    $('#legend_size').append(element);
    var canvasId = 'legend_size_canvas';
    element.append($('<canvas id="' + canvasId + '"></canvas>'));
    var canvas = document.getElementById(canvasId);
    if (canvas.getContext) {
        var ns = 1000000;
        var nl = 100000000;
        var rs = getMarkerSize(ns, currentState.markerSizeScale);
        var rl = getMarkerSize(nl, currentState.markerSizeScale);
        var m = 1;  // margin
        canvas.width  = (rl + m) * 4;
        canvas.height = (rl + m) * 2;

        var ctx = canvas.getContext('2d');
        ctx.beginPath();
        ctx.arc(rl, rl, rl, 0, 2 * Math.PI, true);
        //ctx.fillStyle = colorRamp[colorRamp.length - 1];
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.stroke();

        ctx.beginPath();
        ctx.arc(rl, 2 * rl - rs, rs, 0, 2 * Math.PI, true);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(rl, m);
        ctx.lineTo(2.5 * rl, m);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(rl, 2 * rl - 2 * rs);
        ctx.lineTo(2.5 * rl, 2 * rl - 2 * rs);
        ctx.stroke();

        ctx.strokeText(shorterNumber(nl), 2.5 * rl, 8);
        ctx.strokeText(shorterNumber(ns), 2.5 * rl, 2 * rl - 2 * rs + 8);
    }
}

function drawContour(map) {
    var c = [];
    for (var i = 0; i < config.contour.length; i++) {
        c.push(new google.maps.LatLng(config.contour[i][0], config.contour[i][1]));
    }

    new google.maps.Polygon({
        paths: c,
        strokeColor: "#666666",
        strokeOpacity: 0.6,
        strokeWeight: 4,
        fillColor: "#000000",
        fillOpacity: 0,
    }).setMap(map);
}

function drawSimileTimeline() {
    var eventSource = new Timeline.DefaultEventSource(0);

    var theme = Timeline.ClassicTheme.create();
    theme.event.bubble.width = 420;
    theme.event.bubble.height = 120;
    theme.event.instant.icon = "dull-brown-circle.png";
    var d = Timeline.DateTime.parseGregorianDateTime("1870")
    var bandInfos = [
        Timeline.createBandInfo({
            width:          "10%", 
            intervalUnit:   Timeline.DateTime.DECADE, 
            intervalPixels: 200,
            date:           d,
            showEventText:  false,
            theme:          theme
        }),
        Timeline.createBandInfo({
            width:          "90%", 
            intervalUnit:   Timeline.DateTime.DECADE, 
            intervalPixels: 200,
            eventSource:    eventSource,
            date:           d,
            theme:          theme
        })
    ];

    bandInfos[0].syncWith = 1;
    bandInfos[0].highlight = false;

    simile_timeline = Timeline.create(
        document.getElementById("simile_timeline"),
        bandInfos,
        Timeline.HORIZONTAL);
    simile_timeline.loadXML("timeline_data.xml", function(xml, url) {
        eventSource.loadXML(xml, url);
    });
}
   


function drawCityChart() {
    // remove previous charts
    bestlast = 1829;
    bestfirst = 2008;
    var pub_chart = document.getElementById('pub_chart');
    while (pub_chart.childNodes.length > 0) {
        pub_chart.removeChild(pub_chart.firstChild);
    }
 
    // preparing data
	var jsonObj = {};

    var numYears = maxYear - minYear + 1; 
    for (var k in pubTrendByYear) {
        if (!isValueInArray(currentState.city, pubTrendByYear[k]['city'])) {
            continue;
        }
		jsonObj[k] =  new Array();
 
        var goodPercent = pubTrendByYear[k]["goodPercent"];
        var first = null;
        var second = null;
        var zerocounter = 0;

        for (var i = 0; i < numYears; i++) {
            if (isNaN(goodPercent[i])) {
                goodPercent[i] = 0;
            }
            var strYear = "" + (i + minYear);
            if (goodPercent[i] == 0) {
            	zerocounter++;	
            }
            if (goodPercent[i] != 0 && first == null) {
            	first = (strYear); 	
            	zerocounter = 0;
            }
            if (goodPercent[i] == 0 && zerocounter > 30 && first != null && second == null) {
            	second = (strYear); 	
            }
            jsonObj[k].push({year: strYear, percentGood: goodPercent[i]});
        }
       	if (second == null) {
       		second = 2008;
       	}
        if (first <= bestfirst) {
        	bestfirst = first;
        }
        if (second >= bestlast) {
        	bestlast = second;	
        }
    }
    

 
    // add new DIV element for chart
    var chart_div = document.createElement('div');
    chart_div.id = 'area';
    $("#pub_char").html("");
    pub_chart.appendChild(chart_div);
 
        // begin draw of chart with Protovis
		minyear = 1829;
		dateFormat = pv.Format.date("%y");
		for (newspaper in jsonObj) {
			jsonObj[newspaper].forEach(function(d) {
				var mySplitResult = d.year.toString().split(" ");
				var year = d.year;
				
				if (mySplitResult.length > 1) {
					year = mySplitResult[3]
				} 
				return d.year = dateFormat.parse(year);
			});
 
		}
		var counter = 0;
		if (vistype == "zoomed") {
	 		x = pv.Scale.linear(dateFormat.parse((bestfirst - 20).toString()),dateFormat.parse((bestlast).toString())).range(0, w);
		} else if (vistype == "all") {
			x = pv.Scale.linear(dateFormat.parse("1829"),dateFormat.parse("2008")).range(0, w);	
		}	
		/* The root panel. */
		vis = new pv.Panel().width(w).height(h).bottom(20).left(30).right(10).top(5).canvas('area');
		
		/* Y-axis and ticks. */
		vis.add(pv.Rule).data(y.ticks(5)).bottom(y).strokeStyle(function (d) { if (d) { return "#eee"; } else { return "#000"; } }).anchor("left").add(pv.Label).text(function(d) { return Math.round(d*100) + "%";  });
		
		/* X-axis and ticks. */
		var ticks = vis.add(pv.Rule).data(function(d) { return x.ticks(); }).visible(function (d) { return d; }).left(x).bottom(-5).height(5).anchor("bottom").add(pv.Label).text(x.tickFormat).textStyle(function(d) { if ((d.toString().split(" ")[3] < currentState.yearRangeMin) || (d.toString().split(" ")[3] > currentState.yearRangeMax)) { return "#aaa" } else { return "#333"} });
		
		vis.add(pv.Panel)
    .events("all")
    .event("mousedown", pv.Behavior.pan())
    .event("mousewheel", pv.Behavior.zoom())
    .event("pan", transform)
    .event("zoom", transform);
 
/** Update the x- and y-scale domains per the new transform. */
function transform() {
  var t = this.transform().invert();
  var mx = x.invert(vis.mouse().x);
  var y = mx.toString().split(" ")[3];
  var timerange  = (parseInt((t.k-1)*5*1000));
  $(".manual").attr('checked', true);	
  if (vistype == "zoomed") {

	  x.domain(dateFormat.parse(((bestfirst - 20)+ (t.x/10) - timerange).toString()), dateFormat.parse((parseInt(bestlast) + (t.x/10) + timerange).toString()));
  } else {
  	 x.domain(dateFormat.parse((currentState.yearRangeMin + (t.x/10) - timerange).toString()), dateFormat.parse((currentState.yearRangeMax + (t.x/10) + timerange).toString()));
  }
  vis.render();
}
 
 
update = function(counter) {
	forced = "true";
	if ($("."+counter).is(':checked')){
	  eval("panel"+counter+".i(10)");
	} else {
	   eval("panel"+counter+".i(-1)");
	}
  console.log(panel0.i());
  vis.render();
}
check = function(counter, method) {
	$(".newspaperlist").attr('checked', false);
	counters = 0;
	if (method == "check") {
		counters++;
		$("."+counter).attr('checked', true);
		if (counters == 1) {
			$("#newspaperlist").animate({ scrollTop: counter*16 }, 100);
		}
	} else {
		$("."+counter).attr('checked', false);
	}
}
 
var tx = 0;
$("#newspaperlist").html("");
 
for (newspapert in jsonObj) {
			$("#newspaperlist").append("<input type='checkbox' name='display' class='newspaperlist "+counter+"' onchange='update("+counter+");' id='"+newspapert.replace(/\s/g, "")+"' checked/><a class='newspaperitem' tooltip='" + newspapert + "'>" + newspapert + "</a><br />");
			eval("var panel"+ counter + " = vis.add(pv.Panel).def('i', -1);");
		
			eval("panel"+counter+".add(pv.Area).data(jsonObj[newspapert]).visible(function() { return true; }).bottom(1).left(function (d) { return x(d.year); }).height(function (d) { return y(d.percentGood); }).event('mouseover', function () { check("+counter+", 'check');panel"+counter+".i(10); selected = "+counter+"; newspaperselected = newspapert;this.render(); }).event('mouseout', function () {  check("+counter+", 'uncheck'); panel"+counter+".i(-1); this.render(); 	}).fillStyle(function (d, p) { if (panel"+counter+".i() < 0) { return 'rgba(238, 238, 238, 0.00001)'; } else { return '"+config.pvcolorRamp[counter]+"'; } }).anchor('top').add(pv.Line).strokeStyle(function() { return '" + config.pvcolorRamp[counter]+ "'; }).lineWidth(function (d, p) { if (panel"+counter+".i() < 0) { return 0.5; } else { return 1; }});");
		counter++;
				
}
$("#remove").html("");
$("#newspaperlist").after("<div id='remove'><strong>Zoom level</strong><br /><div style='width:30px;display:inline;float:left;'><input type='button' name='zoom' class='zoomin' value='+' /><input type='button' name='zoom' class='zoomout' value='-' /></div><div style='width:90px;font-size:10px;display:inline;float:left;'><input type='radio' name='zoomyears' class='zoomyears' class='' /> "+bestfirst+"-"+bestlast+"<br /><input type='radio' name='zoomyears' class='allyears' value='All years' /> All years<br /><input type='radio' name='zoomyears' class='manual' value='Manual' /> Manual</div></div></div>");
		
		$(".zoomin").mousehold(function() {
			tx = tx + 10;
			x.domain(dateFormat.parse((parseInt(bestfirst) + tx).toString()), dateFormat.parse((parseInt(bestlast) - tx).toString()));
  			vis.render();
  			$(".manual").attr('checked', true);	
			
		});
		// This could be made more streamlined
		if (vistype == "manual") {
			$(".manual").attr('checked', true);	
		}
		if (vistype == "zoomed") {
			$(".zoomyears").attr('checked', true);	
		}
		if (vistype == "all") {
			$(".allyears").attr('checked', true);	
		}
		$(".manual").click(function() {
			
			vistype = "manual";
			
		});
		$(".zoomyears").click(function() {
			redraw(bestfirst, bestlast);
			vistype = "zoomed";
		});
		$(".allyears").click(function() {
			redraw(1829,2008);
			vistype = "all";
			
		});

		$(".zoomout").mousehold(function() {
				tx = tx - 10;
			  x.domain(dateFormat.parse((parseInt(bestfirst) + tx).toString()), dateFormat.parse((parseInt(bestlast) - tx).toString()));
  vis.render();			
		});
 
		vis.render();
 
 
}
function redraw(fromyear, toyear) {
  x.domain(dateFormat.parse(fromyear.toString()), dateFormat.parse(toyear.toString()));
  vis.render();
}

function drawCityInfo() {
    // update city info in right column
    var stats = {};

    for (var i = 0; i < statsByCity.length; i++) {
		//console.log(currentState.city);
		//console.log(statsByCity);
    	//console.log(isValueInArray(currentState.city,statsByCity[i]["city"]));
        if (isValueInArray(currentState.city,statsByCity[i]["city"]) &&
            yearInRange(statsByCity[i]["year"])) {

            if (stats[statsByCity[i]["city"]] == null) {
                stats[statsByCity[i]["city"]] = {mGood: 0, mTotal: 0};
            }
            stats[statsByCity[i]["city"]]["mGood"] += parseInt(statsByCity[i]["mGood"]);
            stats[statsByCity[i]["city"]]["mTotal"] += parseInt(statsByCity[i]["mTotal"]);
        }
    }
    //console.log(stats);
    if (stats != null) {
    	//console.log("hitting here");
    	$('#city_info').html("");
    	for (var city in stats) {
    		//console.log(city);
    		//console.log(stats);
	        //$('#city_info').hide('slow', function() {
	            var nGood = stats[city]["mGood"];
	            var nTotal  = stats[city]["mTotal"];
	            
	            $('#city_info').append(
	                "<span id='cityname'>" + city + ", " + currentState.state + "</span>, " +
	                currentState.yearRangeMin + " - " +
	                currentState.yearRangeMax + "<br/>" +
	                "<span style=\"color:green;float:left;\">Good words: " + addCommas(nGood) + "</span>" +
	                "<span style=\"color:gray;float:right;\">Total words: " + addCommas(nTotal.toString()) + "</span>");
	
	            // draw bar chart and append to city_info
	            var w = $('#city_info').parent().innerWidth() - 20;
	            var h = 20;
	            var bar = document.createElement('canvas');
	            bar.width = w;
	            bar.height = h;
	            if (bar.getContext) {
	                var ctx = bar.getContext('2d');
	
	                // compute ratio and draw bars
	                var r = stats[city]["mGood"] / stats[city]["mTotal"];
	                ctx.fillStyle = 'green';
	                ctx.fillRect(0, 0, w * r, h);
	                ctx.fillStyle = 'gray';
	                ctx.fillRect(w * r, 0, w * (1-r), h);
	
	                // show text for ratios
	                ctx.strokeStyle = 'white';
	                ctx.lineWidth = 1.5;
	                var txt = '' + Math.round(r * 100) + '%';
	                var txtWidth = ctx.measureText(txt).width;
	                var txtHeight = 6;
	                ctx.strokeText(txt, w * r / 2 - txtWidth / 2, h / 2 + txtHeight / 2);
	            }
	        

            $('#city_info').append(bar);
        //});
    	}
        redraw(currentState.yearRangeMin, currentState.yearRangeMax);
        $('#city_info').show('slow');
    }
}

function drawMarkers(statsByCity, shiftselected) {

	// if shift is being pressed

	    // clean up previous markers
	   while (markers.length > 0) {
	        markers.pop().setMap(null);
	   }
	

    // compute data by city, for all years in range
    var data = [];
    for (i in statsByCity) {
        if (!yearInRange(statsByCity[i]["year"])) {
            continue;
        }

        var city = statsByCity[i]["city"];
        var lat = statsByCity[i]["lat"];
        var lng = statsByCity[i]["lng"];

        if (!(city in data)) {
            data[city] = [];
            data[city]["city"]  = city;
            data[city]["lat"]   = lat;
            data[city]["lng"]   = lng;
            data[city]["good"]  = 0;
            data[city]["total"] = 0;
        }

        data[city]["good"] += parseInt(statsByCity[i]["mGood"]);
        data[city]["total"] += parseInt(statsByCity[i]["mTotal"]);
    }

    // add new markers
    for (i in data) {
        var good = parseFloat(data[i]["good"]);
        var total = parseFloat(data[i]["total"]);
        var goodPercent = good / total;

        // keep only markers with color in range
        if (goodPercent < colorRampThreshold[currentState.colorRangeMin] ||
            goodPercent > colorRampThreshold[currentState.colorRangeMax] + 0.1) {
            continue;
        }

        var loc = new google.maps.LatLng(
            parseFloat(data[i]["lat"]),
            parseFloat(data[i]["lng"]));

        // determine color
        var bin = 0;
        for (; bin < colorRamp.length; bin++) {
            if (goodPercent <= colorRampThreshold[bin]) {
                break;
            }
        }
        var color = colorRamp[bin];

        // generate 2 images, for mouseover and mouseout, default to mouseout
        var highlight = isValueInArray(currentState.city, data[i]["city"]);
        var imageMouseOver = createMapMarkerImage(total, color, highlight, true);
        var imageMouseOut  = createMapMarkerImage(total, color, highlight, false);

        // generate the marker
        marker = new google.maps.Marker({
            position: loc,
            map: map,
            icon: imageMouseOut,
            city: data[i]["city"],
            imageMouseOver: imageMouseOver,
            imageMouseOut: imageMouseOut,
        });

        addMarkerListener(marker);
        markers.push(marker);
    }
}

function drawColorRangeDisplay() {
    var percentageMin = colorRampThreshold[currentState.colorRangeMin];
    var percentageMax = colorRampThreshold[currentState.colorRangeMax] + 0.1;

    percentageMin = (percentageMin * 100).toPrecision(3) + '%';
    percentageMax = (percentageMax * 100).toPrecision(3) + '%';

    $("#color_range_left").html(percentageMin);
    $("#color_range_right").html(percentageMax);
}

/*****************************************************************************/
// state transition and control functions
/*****************************************************************************/

// the update of year range and color range are done through sliders
// so only here we need explicit update function

function updateCity(city) {
    // record newly updated city
    // always comes in as array
    currentState.city = city;
    onCityChange();
}

function onCityChange() {
    drawCityInfo();
    drawCityChart();

    currentStateToURL();
}

function onYearRangechange() {
    currentState.yearRangeMin = timeline.getVisibleChartRange().start.getFullYear();
    currentState.yearRangeMax = timeline.getVisibleChartRange().end.getFullYear();

    drawCityInfo();
    drawMarkers(statsByCity);
    setSimileCenterYear('' +
        Math.round((currentState.yearRangeMin +
                    currentState.yearRangeMax) / 2));

    currentStateToURL();
}

function onColorRangeChange() {
    // record updated color range
    var range = $( "#legend_slider" ).slider("values");
    currentState.colorRangeMin = range[0];
    currentState.colorRangeMax = range[1];

    drawColorRangeDisplay();

    // update markers, keeping only those with color in range
    drawMarkers(statsByCity);

    currentStateToURL();
}

function onMarkerSizeScaleChange() {
    drawMarkers(statsByCity);
    drawSizeLegendChart();

    currentStateToURL();
}

function onMapTypeChange() {
    currentState.mapTypeId = map.getMapTypeId();
    currentStateToURL();
}


/****************************************************************************/
// utility functions
/****************************************************************************/

function name(newspaper) { 	
 	return newspaper + "<br />" + "For more details, click <strong><a href='http://texashistory.unt.edu/search/?q=" + newspaper + "&t=fulltext&fq=dc_type%3Atext_newspaper' target='_blank'>here</a></strong>";
 }

function isValueInArray(arr2, val) {
	inArray = false;
	if (arr2.constructor.toString().indexOf("Array") == -1) {
		arr2 = [arr2];	
	}
	for (var ix = 0;ix < arr2.length;ix++) {
		if (val == arr2[ix]) {
			inArray = true;
		}
	}
	return inArray;
}

function yearInRange(year) {
    // currently only work on SIMILE timeline
    var inRange = false;
    if (parseInt(year) >= parseInt(currentState.yearRangeMin) &&
        parseInt(year) <= parseInt(currentState.yearRangeMax)) {
        inRange = true;
    }
    return inRange;
}

function addMarkerListener(marker) {
	document.onkeydown = function(evt) {
    evt = evt || window.event;
    if (evt.keyCode == 16) {
       	shifted = "true";
    	}
	};
	document.onkeyup = function(evt) {
    evt = evt || window.event;
    if (evt.keyCode == 16) {
       	shifted = null;
       	currentState.city = config.defaultCity;
    	}
	};
	
    google.maps.event.addListener(marker, "click", function() {
        allcities = new Array();
        if (shifted) {
        	allcities = allcities.concat(currentState.city);
        	allcities.push(marker.city);
        	updateCity(allcities);	
        } else {
        	allcities.push(marker.city);
        	updateCity(allcities);	
        }
        drawMarkers(statsByCity, shifted);
    });

    google.maps.event.addListener(marker, "mouseover", function() {
        marker.setIcon(marker.imageMouseOver);
    });

    google.maps.event.addListener(marker, "mouseout", function() {
        marker.setIcon(marker.imageMouseOut);
    });
}

function createMapMarkerImage(total, color, highlight, withCenterText) {
    // determine radius
    var r = getMarkerSize(total, currentState.markerSizeScale);

    // create canvas element as marker
    var canvas = document.createElement('canvas');
    if (canvas.getContext) {
        rs = r - 3;
        canvas.width = r * 2;
        canvas.height = r * 2;

        // draw circle according to size and color from data
        var ctx = canvas.getContext('2d');
        ctx.globalAlpha = 0.5;
        ctx.fillStyle = color;
        ctx.arc(r, r, rs, 0, Math.PI * 2, false);
        ctx.fill();

        // highlight current city
        if (highlight) {
            ctx.strokeStyle="#ffff00";
            ctx.lineWidth = 3;
            ctx.arc(r, r, rs, 0, Math.PI * 2, false);
            ctx.stroke();
        }

        // draw center text
        if (withCenterText) {
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 1;
            var txt = shorterNumber(total);
            var txtWidth = ctx.measureText(txt).width;  // draw at center, accurately
            ctx.strokeText(txt, r - txtWidth / 2, r + 4);
        }
    }

    // create the marker image
    var image = new google.maps.MarkerImage(
        canvas.toDataURL(),  // url to the canvas image
        new google.maps.Size(2*r, 2*r), // size
        new google.maps.Point(0, 0),    // origin
        new google.maps.Point(r, r));   // anchor
    return image;
}


function onResize() {

}

function setSimileCenterYear(date) {
    simile_timeline.getBand(0).setCenterVisibleDate(new Date(date, 0, 1));
}

function getSimileCenterYear() {
    alert(simile_timeline.getBand(0).getCenterVisibleDate());
}

function switchSimileTheme(){
    var timeline = document.getElementById('simile_timeline');
    timeline.className = (timeline.className.indexOf('dark-theme') != -1) ?
                         timeline.className.replace('dark-theme', '') :
                         timeline.className += ' dark-theme';
}

function getTrendByYear(statsByPub, minYear, maxYear) {
    //  convert data from   pub, city, year, ...
    //  to                  pub, city, trend[year], ...
    var result = {};
    for (var i = 0; i < statsByPub.length; i++) {
        var pubName = statsByPub[i]["pub"];
        if (result[pubName] == null) {
            result[pubName] = {};
            result[pubName]["pub"] = statsByPub[i]["pub"];
            result[pubName]["city"] = statsByPub[i]["city"];
            result[pubName]["lat"] = statsByPub[i]["lat"];
            result[pubName]["lng"] = statsByPub[i]["lng"];
            result[pubName]["goodPercent"] = new Array();
            // force length to the entire year range
            result[pubName]["goodPercent"][maxYear-minYear] = null;
        }

        // record mGood / mTotal to the year
        var yearOffset = statsByPub[i]["year"] - minYear;
        result[pubName]["goodPercent"][yearOffset] =
            parseFloat(statsByPub[i]["mGood"]) /
            parseFloat(statsByPub[i]["mTotal"]);
    }
    return result;
}

function getMarkerSize(total, scaleMethod) {
    var radius = Math.log(total);
    if (scaleMethod == 'linear') {
        radius = Math.ceil(total / 2000000);
    }
    return Math.max(5, radius);  // assign minimum
}

function addCommas(nStr)
{
	nStr += '';
	x4 = nStr.split('.');
	x1 = x4[0];
	x2 = x4.length > 1 ? '.' + x4[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}
function shorterNumber(n) {
    var s = '';
    if (n >= 1000000000) {
        s = Math.floor(n / 1000000000) + 'B';
    }
    else if (n >= 1000000) {
        s = Math.floor(n / 1000000) + 'M';
    }
    else if (n >= 1000) {
        s = Math.floor(n / 1000) + 'K';
    }
    else {
        s = n;
    }
    return s;
}

function URLToCurrentState() {
    var hash = window.location.hash;
    hash = decodeURI(hash);
    for (var k in currentState) {
        var v = decodeValueFromURL(hash, k);
        if (v.length > 0) {
            if (v.split(",").length > 1) {
            	v = v.split(",");
            	//console.log(v);
            }
            if (isInteger[k]) {
                currentState[k] = parseInt(v);
                currentState[k] = isNaN(currentState[k]) ? 0 : currentState[k];
            }
            else {
                currentState[k] = v;
            }
        }
    }
}

function decodeValueFromURL(hash, key) {
    var valueLocation = hash.indexOf(key + "=") + key.length + 1;
    var nextAmpLocation = hash.indexOf("&", valueLocation);
    if (nextAmpLocation < 0) {
        nextAmpLocation = hash.length;
    }
    var value = hash.substring(valueLocation, nextAmpLocation);
    return value;
}

function currentStateToURL() {
    var hash = "";
    for (var k in currentState) {
        hash += ("&" + k + "=" + currentState[k]);
    }
    hash = encodeURI(hash);
    window.location.hash = "#!" + hash;
}

$(function() {

	var simileshown = "true";
		//$(".wrapper2").hide(); // gm eliminated
		//	var simileshown = "false"; //gm eliminated 3/29/2012s
		//	$(".wrapper2").hide();
		$(".clickexpand").click(function() {
			if (simileshown == "false") {
				$(".wrapper2").slideDown();
				 drawSimileTimeline();
				 simileshown = "true";
			} else {
				$(".wrapper2").slideUp();
				 simileshown = "false";
			}
		});
});
</script>

</head>

<body onresize="onResize();"> 
  <!-- Header Bar-->
  <div class="header_area"><a href="http://mappingtexts.org"><img src="mappingtexts_header_title.png" /></a></div>
  
  <!-- Title Bar -->
  
  
  <div class="wrapper">
      <div id="title_block">
      </div>
      <!-- timeline control -->
     <div class="widget_header">
      <h3>Time</h3>&nbsp;
      <p>Quantity of Recognized and Unrecognized Text, 1828-2008</p>
      </div>
      <!-- google widget -->
      <div id="timeline_vis"> </div>
  </div>

  <div class="wrapper">
  <hr>
    <div class="widget_header">
      <h3>&nbsp;Space</h3>&nbsp;
      <p>Collection Quantity and Quality by Location</p>
    </div>
    <!-- left column -->
    <div id="leftcolumn">
      <div id="city_info" style="display: none"></div>
      <br/>
      <div style="float:left;width:120px">
      <div id="newspaperlist"></div>
      </div>
      <div id="pub_chart"></div>
      <div id="rightarrow"></div>
    </div>
	
    <!-- center column -->
    <div id="centercolumn">
      <!-- canvas for map -->
      <div id="map_canvas"></div>
    </div>

    <!-- right column -->
    <div id="rightcolumn">
    <div class="widget_header">
    <h3>Legend</h3><br />
    <p>Ratio of Good to Bad Words</p>
    </div>
    <!-- color legend -->
      <div width="200">
        <div>
            <canvas id="legend_color_canvas" height="40"></canvas>
            <div id="legend_slider"><div id="ui-slider-range"></div></div>
        </div>
        <br \>
        <br \>
        <br \>
        <div class="legend_text"><i>
          Showing cities having publications with  
          <b><span id="color_range_left"></span>
          -
          <span id="color_range_right"></span></b> correct words.</i>
          </div>
      </div>
      <br/>
      <div class="legend_text">Circle Scaling:</div>
      <!-- scale selector -->
      <div>
        <form>
          <input type="radio" name="scale_select" value="log">Log
          <input type="radio" name="scale_select" value="linear">Linear
          <a href="#" class="help" rel="help.html"><img src="help.png" /></a> 
        </form>
      </div>
      <!-- size legend -->
      <div id="legend_size"></div>
      <div class="legend_text"><i>Circle size is relative to total number of words scanned. Color indicates overall scan quality.</i></div>

      
      
    </div>
  </div>

  <!-- SIMILE timeline -->
  <div class="wrapper2 movable">
  <hr>
    <div class="widget_header">
      <h3>&nbsp;Context</h3>&nbsp;
      <p>Texas History Timeline</p>
    </div>
    <div id="simile_timeline" class="timeline-default" style="height: 400px; width: 100%"></div>
    <script type="text/javascript">
        var timeline = document.getElementById('simile_timeline');
        timeline.className += '';
    </script>
  <div>


</body>
</html>

