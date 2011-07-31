<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		print('reports');
	}
	
	public function view_report($report_id, $result_id = null, $view_mode = 'tweets_per_hour'){
		$this->load->helper('url');
		
		if (!is_null($result_id) && !is_numeric($result_id)){
			$view_mode = $result_id;
			$result_id = null;
		}
		$url = '//' . site_url('');
		$data['home_link'] = '<li><a href="'.$url.'" >Home</a></li>';
		$url = '//' . site_url('reports/view_report/'. $report_id . '/' . $view_mode);
		$data['report_link'] = '<li><a href="'.$url.'" >Report Page</a></li>';
		$url = '//' . site_url('reports/run_report/'. $report_id);
		$data['run_report_link'] = '<li><a href="'.$url.'" >Run Report</a></li>';
		$report = $this->load_report($report_id, $result_id);

		$data['report_query'] = ' &gt; ' . $report['query'];
		//template headers
		$this->load->view('head');
		$this->load->view('header', $data);
		
		if (!isset($report['result_sets'])){
			$url = '//' . site_url('reports/run_report/'. $report_id);
			$html = '<strong> This query has never been run before</strong> ' . '<a href="' . $url . '">Click Here</a>';
			echo $html;
		}else{
			$data['report_id'] = $report_id;
			$data['reports_list'] = $this->get_report_list($report_id, $view_mode, $result_id);
			//load all the default views
			$this->load->view('reports_detail', $data);
			if ($result_id){
				switch ($view_mode){
					case 'tweets_per_hour':
							$data['tweets_per_hour'] = $this->_get_tph_tokens($report['result_sets'][$result_id]);
							$this->load->view('tweets_per_hour', $data);
						break;
					default:
						break;
				}
			}	
		}	
		$this->load->view('close');
	}
	
	public function get_report_list($report_id, $view_mode, $result_id){
		
		$sql = "SELECT * FROM results WHERE report_id = ? ORDER BY `from` desc";
		$args[] = $report_id;			
		$query = $this->db->query($sql, $args);
		
		$list = '<div id="report_list"><strong>Select a Report &gt;&gt;</strong>';
		$list .= '<ul>';
		foreach ($query->result() as $row){
			$class = '';
			if ($row->result_id == $result_id ){
				$class = 'active';
			}
			$url = '//' . site_url('reports/view_report/'. $report_id . '/' . $row->result_id . '/' . $view_mode);
			$list .= '<li><a class="'.$class.'" href="'.$url.'" >'. date('d m Y', $row->from) . '</a></li>'; 
		}
		$list .= '</ul></div>';
		return $list;
	}
	
	public function run_report($report_id, $view_mode = 'tweets_per_hour'){
		$this->load->helper('url');
		$url = '//' . site_url('');
		$data['home_link'] = '<li><a href="'.$url.'" >Home</a></li>';
		$url = '//' . site_url('reports/view_report/'. $report_id . '/' . $view_mode);
		$data['report_link'] = '<li><a href="'.$url.'" >Report Page</a></li>';
		$url = '//' . site_url('reports/run_report/'. $report_id);
		$data['run_report_link'] = '<li><a href="'.$url.'" >Run Report</a></li>';
		$message = $this->_run_report($report_id);
		
		$data['report_id'] = $report_id;
		$data['message'] = $message;
		//load all the default views
		$this->load->view('head');
		$this->load->view('header', $data);
		$this->load->view('close');
	}
	
	private function load_report($report_id, $result_id){
		$this->load->database();
		$args = array();
		$sql = "SELECT reports.report_id, reports.query, results.result_id, results.`from`, results.data AS result_data, tweets.data AS tweet_data  FROM reports 
				LEFT JOIN results ON reports.report_id = results.report_id
				LEFT JOIN tweets ON tweets.result_id = results.result_id
				WHERE reports.report_id = ?";
		$args[] = $report_id;		
		if (!is_null($result_id) && is_numeric($result_id)){
			$sql .= ' AND results.result_id = ?';
			$args[] = $result_id; 
		}		
		$query = $this->db->query($sql, $args);
		$report = array();
		foreach ($query->result() as $row)
   		{
   			$report['id'] = $row->report_id;
   			$report['query'] = $row->query;
   			if(is_null($row->result_id)){break;}
   			if (!isset($report['result_sets'][$row->result_id])){
   				$report['result_sets'][$row->result_id] = unserialize($row->result_data);
   			}
   			$report['result_sets'][$row->result_id]['from'] = $row->from;
   			$tweet_data = unserialize($row->tweet_data);
   			$report['result_sets'][$row->result_id]['results'][$tweet_data->id_str] = $tweet_data;			
		}

		return $report;
	}
	
	private function _run_report($report_id){
		$this->load->helper('url');
		$this->load->database();
		$query = $this->db->query('SELECT report_id, query FROM reports rep WHERE rep.report_id = ?', array($report_id));
		$report = $query->row_array();

		$last_result = $this->_get_last_result($report_id);
		if (!is_null($last_result)){
			//check the From date isn't yesterday or today
			$yesterday = mktime(0,0,0,date('m'), date('d')-1, date('Y'));
	
			if ($last_result['from'] >= $yesterday){
				$url = '//' . site_url('reports/view_report/'. $report_id . '/' . $last_result['result_id']);
				$message = 'This query already has the latest record set';
				$message .= '<br/>' . '<a href="'.$url.'">Click here to view results</a>';
				return $message;
			}			
		}
		//No data for this yet so we can go ahead and execute query
		$data = $this->_execute_query($report['query']);
		$results = $data['results'];
		unset($data['results']);
		$this->db->query('INSERT INTO results (report_id, `from`, data) VALUES (?,?,?)', array($report_id, $data['from'], (serialize($data))));
		$result_id = $this->db->insert_id();
		foreach($results as $result){
			$this->db->query('INSERT INTO tweets (result_id, data) VALUES (?, ?)', array($result_id, (serialize($result))));	
		}
		$url = '//' . site_url('reports/view_report/'. $report_id . '/' . $result_id);
		$message = 'Report ran in ' . $data['query_time'] . ' seconds and has been saved to the database';
		$message .= '<br/>' . '<a href="'.$url.'">Click here to view results</a>';
		return $message;
	}
	
	private function _get_last_result($report_id){
		
		$this->load->database();
		$query = $this->db->query('SELECT * FROM results res WHERE res.report_id = ? ORDER BY `from` desc', array($report_id));
		if ($query->num_rows() > 0){
			$row = $query->row_array();
			$row['data'] = unserialize($row['data']);
			return $row;
		}else{
			return null;
		}
	}
	
	private function _execute_query($query, $timestamp = null){
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
			$response['p'.$page] = $this->send_twitter_query($options, $page, $rpp);
			while (count($response['p'.$page]->results) == $rpp){
				$last_id = (array_pop($response['p'.$page]->results)->id_str);
				$options['max_id'] = $last_id;
				$page++;
				$response['p'.$page] = $this->send_twitter_query($options, 1, $rpp);
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
				'from' => $date,
				'results' => $results
			);	
	}
	
	private function send_twitter_query($options, $page = 1, $rpp = 10){
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
	
	private function _get_tph_tokens($data){
		
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
			
		return array(
			'js' => $js,
			'results_count' => $data['total_records'],
		);
		
	}
		
}

/* End of file reports.php */
/* Location: ./application/controllers/reports.php */