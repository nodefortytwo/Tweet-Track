<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Reports_lib {

    public function get_reports()
    {
    	
    	//$this->load->library('table');
    	$this->load->database();
    	
    	$query = $this->db->query('SELECT report_id, query FROM reports rep');

    	return  $this->table->generate($query);
    }
}

/* End of file Someclass.php */