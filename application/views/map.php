<style type="text/css">
  html { height: 100% }
  body { height: 100%; margin: 0; padding: 0 }
  #map_canvas { height: 100% }
  #stats{
  	position: absolute;
	  top:30px;
	  right:5px;
	  z-index: 1000;
	  text-align:right;	
  }
</style>
<script type="text/javascript"
    src="http://maps.googleapis.com/maps/api/js?sensor=true">
</script>
<script type="text/javascript">
  function initialize() {
    var latlng = new google.maps.LatLng(<?php echo $map['centre_coords'];?>);
    var myOptions = {
      zoom: 2,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var map = new google.maps.Map(document.getElementById("map_canvas"),
        myOptions);   
        
   
   	<?php echo $map['marker_js'];?> 
  }
	
</script>

  <div id="stats">
  	<strong>Total Tweets</strong> : <?php echo $map['results_count'];?> <br />
  	<strong>Total Tweets /w geo</strong> : <?php echo $map['results_geocoded'];?> <br />
  </div>
  <div id="map_canvas" style="width:970px; height:600px"></div>

<script></script>