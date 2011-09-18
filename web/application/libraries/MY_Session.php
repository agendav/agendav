<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------------
 * CI Session Class Extension.
 * ------------------------------------------------------------------------
 *
 *
 */

class MY_Session extends CI_Session {

	public function __construct($params = array()) {
		parent::__construct($params);
	}

	/*
	 * Do not update an existing session on ajax calls
	 *
	 * @access    public
	 * @return    void
	 */

	function sess_update()
	{
		if ( ! $this->CI->input->is_ajax_request()) {
			parent::sess_update();
		} else {
			// Update last activity (only after sess_time_to_update)
			if (($this->userdata['last_activity'] +
						$this->sess_time_to_update) < $this->now) {

				$this->userdata['last_activity'] = $this->now;
				$cookie_data = array();
				
				foreach (array('session_id','ip_address','user_agent','last_activity') as $val)
				{
					$cookie_data[$val] = $this->userdata[$val];
				}

				$this->CI->db->query($this->CI->db->update_string(
							$this->sess_table_name,
							array('last_activity' => $this->now), 
							array('session_id' =>
								$this->userdata['session_id'])
							));

				$this->_set_cookie($cookie_data);
			} // Update every 'sess_to_update' seconds
		} // AJAX request
	}
}

// ------------------------------------------------------------------------
/* End of file MY_Session.php */
/* Location: ./application/libraries/MY_Session.php */ 
