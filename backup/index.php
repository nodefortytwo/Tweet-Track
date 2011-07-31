<?php

/**
 * @author Rick Burgess
 * @copyright 2011
 */ 

$sql = 'SELECT * FROM reports';

 
$data = get_tweets_by_day('drupal');

save_data($qid, serialize($data));

map_tweets($data);

//timesplit_tweets($data);

function timesplit_tweets($data){
	$hours = array();
	for($i = 0; $i <= 23; $i++){
		$hours[$i] = 0;
	}
	foreach($data['results'] as $result){
		$timestamp = strtotime($result->created_at);
		$hours[date('G', $timestamp)]++;
		
	}
	
	$js = '';
	$i = 0;
	foreach ($hours as $key=>$hour){
		$js .= "data.setValue(".$key.", 0, '".$key."');" . "\n";
		$js .= "data.setValue(".$key.", 1, ".$hour.");" . "\n\n";
	}
		
	$tokens = array(
		'{js}' => $js,
		'{results_count}' => $data['total_records'],
	);
	
	$template = file_get_contents('line-chart.html');
	$template = str_replace(array_keys($tokens), array_values($tokens), $template);
	
	print($template);
}


function map_tweets($data){
	$template = file_get_contents('map.html');
	$points = array();
	foreach($data['results'] as $result){
		if (!is_null($result->geo)){
			$points[] = array(
				'user' => $result->from_user,
				'geo' => implode(',', $result->geo->coordinates)
			);		
		}
	}
	
	if (count($points) == 0){print('<h1>' . 'Sorry No tweets with Geo Data Avaliable' . '</h1>'); exit();}
	
	$marker_js = '';
	foreach($points as $key=>$point){
		$marker_js .= 'var latlng'.$key.' = new google.maps.LatLng('.$point['geo'].');
		';
		$marker_js .= 'var marker'.$key.' = new google.maps.Marker({
					      position: latlng'.$key.', 
					      map: map, 
					      title:"'.$point['user'].'"
				 	  }); ';
	}	
	
	
	$tokens = array(
		'{centre_coords}' => $points[0]['geo'],
		'{results_count}' => $data['total_records'],
		'{results_geocoded}' => count($points),
		'{marker_js}' => $marker_js
	);
	
	$template = str_replace(array_keys($tokens), array_values($tokens), $template);
	
	print($template);
}

function get_tweets_by_day($query, $timestamp = null){
	//if no date is supplied default to yesterday
	if (is_null($timestamp)){$timestamp = mktime(0,0,0,date('m'), date('d')-1, date('Y'));}
	//sanitize their timestamp {not really required}
	$date = mktime(0,0,0,date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)); 
	$end = mktime(0,0,0,date('m', $date), date('d', $date)+1, date('Y', $date)); 
	
	$options = array(
		'q' => $query,
		'result_type' => 'recent',
		'since' => date('Y-m-d', $date),
		'until' => date('Y-m-d', $end),
	);
	
	$page = 1;
	$rpp = 100;
	$response['p'.$page] = send_query($options, $page, $rpp);
	while (count($response['p'.$page]->results) == $rpp){
		$last_id = (array_pop($response['p'.$page]->results)->id_str);
		$options['max_id'] = $last_id;
		$page++;
		$response['p'.$page] = send_query($options, 1, $rpp);
		if (isset($response['p'.$page]->error)){
			unset($response['p'.$page]);
			break;
		}
	}
	
	$results = array();
	$total_query_time = 0;
	foreach($response as $page){
		$total_query_time += $page->completed_in;
		foreach($page->results as $res){
			array_push($results, $res);
		}
		
	}
	
	//take some data from last 
	$last_page = array_pop($response);
	return array(
		'query_time' => $total_query_time,
		'last_id' => $last_page->max_id_str,
		'total_records' => count($results),
		'results' => $results
	);	
}



function send_query($options, $page = 1, $rpp = 10){
	$options['page'] = $page;
	$options['rpp'] = $rpp;
	$trCurl = curl_init(); 
	$merged = array();
	foreach ($options as $key=>$option){
		$merged[] = $key . '=' . urlencode($option);
	}
	
	$url = "http://search.twitter.com/search.json?" . implode('&', $merged);
	
	curl_setopt($trCurl, CURLOPT_URL, $url); 
	curl_setopt($trCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($trCurl, CURLOPT_HEADER, FALSE); 
	$response = curl_exec($trCurl);
	$code = curl_getinfo($trCurl, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($trCurl);
    curl_close($trCurl);
	if ($code != 200){
		die($code);
	}
	return(json_decode($response)); 
}


?>