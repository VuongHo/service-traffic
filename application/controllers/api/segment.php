<?php defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class Segment extends REST_Controller
{
    const CELL_D_X = 0.003141; // Lon
    const CELL_D_Y = 0.003764; // Lat

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->load->model("segment_model");
    }

    // GET: http://localhost/server/index.php/api/segment/speed?radius=0.001&lat=10.777725&lon=106.656064
    public function speed_get(){
        if(!$this->get('lat') || !$this->get('lon'))
        {
            $this->response(NULL, 400);
        }

        $longitude = $this->get('lon');
        $latitude = $this->get('lat');
        $radius = $this->get('radius');

        $cell_root = $this->segment_model->get_cell_root();
        $cell_x0 = $cell_root[1]; // Lon
        $cell_y0 = $cell_root[0]; // Lat

        $min_x = (int) abs(($longitude - $radius - $cell_x0)/self::CELL_D_X);
        $max_x = (int) abs(($longitude + $radius - $cell_x0)/self::CELL_D_X);
                    
        $min_y = (int) abs(($latitude - $radius - $cell_y0)/self::CELL_D_Y);
        $max_y = (int) abs(($latitude + $radius - $cell_y0)/self::CELL_D_Y);

        // Search all cell_ids from cell table
        $cell_ids = $this->segment_model->select_cell(array(array($min_x, $min_y), array($max_x, $max_y)));

        // Find all street_id from segmentcell table
        $street_ids = $this->segment_model->find_street_id($cell_ids);

        // Find all segment_id from table's segmentcell
        $segment_ids = $this->segment_model->find_segment_id($cell_ids);

        $segments = $this->segment_model->find_speed_segment($street_ids, $segment_ids);

        if($segments)
        {
            $this->response($segments, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'User could not be found'), 404);
        }
    }

    public function speeds_get(){

        if(!$this->get('lat') || !$this->get('lon'))
        {
            $this->response(NULL, 400);
        }

        $longitude = $this->get('lon');
        $latitude = $this->get('lat');
        $radius = $this->get('radius');

        $cell_root = $this->segment_model->get_cell_root();
        $cell_x0 = $cell_root[1]; // Lon
        $cell_y0 = $cell_root[0]; // Lat

        $min_x = (int) abs(($longitude - $radius - $cell_x0)/self::CELL_D_X);
        $max_x = (int) abs(($longitude + $radius - $cell_x0)/self::CELL_D_X);
                    
        $min_y = (int) abs(($latitude - $radius - $cell_y0)/self::CELL_D_Y);
        $max_y = (int) abs(($latitude + $radius - $cell_y0)/self::CELL_D_Y);

        // Search all cell_ids from cell table
        $cell_ids = $this->segment_model->select_cell(array(array($min_x, $min_y), array($max_x, $max_y)));

        $segments = $this->segment_model->get_all_segments_of_the_each_cells_from_db($cell_ids);

        if($segments)
        {
            $this->response($segments, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'User could not be found'), 404);
        }
    }  

	public function send_post()
	{
		var_dump($this->request->body);
	}


	public function send_put()
	{
		var_dump($this->put('foo'));
	}
}