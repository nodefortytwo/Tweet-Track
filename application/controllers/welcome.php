<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

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
		$this->load->helper('url');
		
		
		//Get a list of reports
		
    	$this->load->database();
    	
    	$query = $this->db->query('SELECT report_id, query FROM reports rep');
		$url = 'reports/view_report/';
		$this->load->library('table');
		$this->table->set_heading('ID', 'Name');
		foreach ($query->result() as $row)
		{
			$link = '<a href="//' . site_url($url . $row->report_id) .'">' . $row->query . '</a>';		
		    $this->table->add_row($row->report_id, $link);
		}

    	$data['reports'] = $this->table->generate();
		
		//load all the default views
		$this->load->view('head');
		$this->load->view('header');
		$this->load->view('welcome_message');
		$this->load->view('reports_list', $data);
		$this->load->view('close');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */