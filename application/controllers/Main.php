<?
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->config->load('simpleurl', FALSE, TRUE);
        $this->load->library('SimpleUrl');
    }


    public function index()
    {
        $this->simpleurl->set_chars($this->config->item('chars'));
        $this->simpleurl->set_salt($this->config->item('salt'));
        $this->simpleurl->set_padding($this->config->item('padding'));
        $this->simpleurl->run();
    }

}