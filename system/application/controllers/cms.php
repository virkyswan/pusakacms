<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cms
 *
 * Simple tool for making simple sites.
 *
 * @package		Pusaka
 * @author		Toni Haryanto (@toharyan)
 * @copyright	Copyright (c) 2011-2012, Nyankod
 * @license		http://nyankod.com/license
 * @link		http://nyankod.com/pusakacms
 */
class CMS extends MY_Controller {

	/**
	 * Main CMS Function
	 *
	 * Routes and processes all page requests
	 *
	 * @access	public
	 * @return	void
	 */
	public function _remap($method, $params = array())
	{
		// run the main method first if available
		if (method_exists($this, $method))
			return call_user_func_array(array($this, $method), $params);

		$segments = $this->uri->segment_array();

		$is_home = FALSE;

		// Blank mean it's the home page, ya hurd?
		if (empty($segments))
		{
			$is_home = TRUE;
			$segments = array('index');

			if($this->config->item('post_as_home'))
				return call_user_func_array(array($this, 'post'), $params);
		}

		// reset index to 0
		$segments = array_values($segments);
		
		// if it is STREAM POST
		if($segments[0] == $this->config->item('post_term'))
		{
			return call_user_func_array(array($this, 'post'), $params);
		}
		// if it is a PAGE
		else 
		{
			$file_path = PAGE_FOLDER.'/'.implode('/', $segments);
			
			// check if there is a custom layout for this page
			if($this->template->layout_exists('pages/'.implode("/",$segments)))
				$this->template->set_layout('pages/'.implode("/",$segments));
			// check if there is a custom layout for this page and its children
			elseif($this->template->layout_exists('pages/'.$segments[0]))
				$this->template->set_layout('pages/'.$segments[0]);

			$this->template->view_content($file_path, $this->data);
		}
	}

	function sync_nav($prefix = null)
	{
		header("Content-Type:text/plain");
		echo $this->pusaka->sync_nav($prefix);
	}

	function sync_post()
	{
		header("Content-Type:text/plain");
		echo $this->pusaka->sync_nav(POST_TERM)."\n";
		echo $this->pusaka->sync_label();
	}

	function post()
	{
		$this->template->set_layout(null);
		$this->data['label'] = false;

		$segments = $this->uri->segment_array();
		$segments = array_values($segments);

		// it is a post list
		if(! isset($segments[1])){
			$this->config->set_item('page_title', $this->config->item('post_term').' - '.$this->config->item('page_title'));

			$this->data['posts'] = $this->pusaka->get_posts();

			$this->template->view('layouts/posts', $this->data);
		}
		else {
			// if it is a post list with page number
			if($segments[1] == 'p'){
				$this->config->set_item('page_title', $this->config->item('post_term').' - '.$this->config->item('page_title'));

				$this->data['posts'] = $this->pusaka->get_posts(null, isset($segments[2]) ? $segments[2] : 1);
				$this->template->view('layouts/posts', $this->data);
			}

			// if it is a blog label
			elseif($segments[1] == 'label'){
				if(! isset($segments[2])) show_404();

				$this->config->set_item('page_title', $segments[2].' - '.$this->config->item('page_title'));

				$this->data['label'] = $segments[2];
				$this->data['posts'] = $this->pusaka->get_posts($segments[2], isset($segments[3]) ? $segments[3] : 1);
				$this->template->view('layouts/posts', $this->data);
			}
			
			// then it is a detail post
			else {
				$uri = $this->uri->uri_string();
				$this->data['post'] = $this->pusaka->get_post($uri);
				if(! $this->data['post']) show_404();

				// set meta title
				$this->config->set_item('page_title', $this->data['post']['title'].' - '.$this->config->item('page_title'));

				$this->template->view('layouts/post', $this->data);
				
			}
		}
	}

	/*
	 * param string $site 	site_slug
	 */
	function update_domain($site = null)
	{
		if(!$site) show_error('which site domain must be update?');

		if(file_exists('sites/'.$site.'/CNAME')){
			$domain = @file_get_contents('sites/'.$site.'/CNAME');
			if(write_file('sites/_domain/'.$domain.'.conf', $site)){
				header("Content-Type:text/plain");
				echo "Domain setting for site $site updated.";
			}
			else
				show_error('Writing domain configuration file failed. /sites/_domain/ folder must be writable.');
		} else
			show_error('CNAME file not found');
	}

}