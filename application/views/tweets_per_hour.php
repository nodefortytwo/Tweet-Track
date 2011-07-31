    
	
	
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Hour');
        data.addColumn('number', 'Tweets');
        data.addRows(24);
        
        <?php echo $tweets_per_hour['js'];?>


        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, {width: 970, height: 500, title: 'Tweets by Hour', legend:'none',  chartArea:{left:50,top:50, right:50, width:"100%",height:"75%"}});
      }
    </script>
	
	<div id="chart_div"></div>