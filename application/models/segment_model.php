<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Segment_model extends CI_Model {

	public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->load->database();
        $this->load->driver('cache');
    }

	public function get_cell_root(){
		$query = $this->db->query('SELECT cell_lat, cell_lon FROM cell WHERE cell_id = 0 LIMIT 1');
		return array($query->result()[0]->cell_lat, $query->result()[0]->cell_lon);
	}

	public function select_cell($coordinates){
		$min_x = $coordinates[0][0];
    	$min_y = $coordinates[0][1];
    	$max_x = $coordinates[1][0];
    	$max_y = $coordinates[1][1];

    	for ($i = $min_x; $i <= $max_x; $i++) {
			for ($j = $min_y; $j <= $max_y; $j++) {
				$query = $this->db->query("SELECT cell_id FROM cell WHERE cell_x = $j AND cell_y = $i LIMIT 1");
				$cell_id[] = $query->result()[0]->cell_id;
			}
		}
		return $cell_id;
	}

	public function find_street_id($c_ids){
		$cell_ids = join(',', $c_ids);
		$query = $this->db->query("SELECT DISTINCT street_id FROM segmentcell
							JOIN segment ON segmentcell.segment_id = segment.segment_id
							WHERE cell_id IN ($cell_ids)");
		foreach ($query->result() as $row) {
			$street_ids[] = $row->street_id;
		}
		return $street_ids;
	}

	public function find_segment_id($c_ids){
		$cell_ids = join(',', $c_ids);
		$query = $this->db->query("SELECT DISTINCT segment.segment_id FROM segmentcell
							JOIN segment ON segmentcell.segment_id = segment.segment_id
							WHERE cell_id IN ($cell_ids)");
		foreach ($query->result() as $row) {
			$segment_ids[] = $row->segment_id;
		}
		return $segment_ids;
	}

	public function find_speed_segment($street_ids, $seg_ids){
		$segment_ids = join(',', $seg_ids);
		foreach($street_ids as $row) {
			$query = $this->db->query("SELECT segment.node_id_start, segment.node_id_end, segmentdetails.speed
								FROM segmentdetails
								JOIN segment ON segment.segment_id = segmentdetails.segment_id
								WHERE segment.segment_id IN ($segment_ids) AND segment.street_id = $row
								GROUP BY segment.node_id_start");
			$rlt = $query->result();
			$seg = array();
			$count_s = count($rlt);
			for ($j=0; $j < $count_s; $j++) { 
				$node_id_start = $rlt[$j]->node_id_start;
				$node_id_end = $rlt[$j]->node_id_end;

				$rlt_node = $this->db->query("SELECT node_lat, node_lon FROM node WHERE node_id = $node_id_start");
				$node_start_lat = $rlt_node->result() ? $rlt_node->result()[0]->node_lat : null;
				$node_start_lon = $rlt_node->result() ? $rlt_node->result()[0]->node_lon : null;

				$rlt_node = $this->db->query("SELECT node_lat, node_lon FROM node WHERE node_id = $node_id_end");
				$node_end_lat = $rlt_node->result() ? $rlt_node->result()[0]->node_lat : null;
				$node_end_lon = $rlt_node->result() ? $rlt_node->result()[0]->node_lon : null;

				if (($node_start_lat == null) || ($node_start_lon == null) || ($node_end_lat == null) || ($node_end_lon == null)) continue;

				$seg[] = array('node_start' => array('id' => $node_id_start, 'lat' => $node_start_lat, 'lon' => $node_start_lon),
								'node_end' => array('id' => $node_id_end, 'lat' => $node_end_lat, 'lon' => $node_end_lon),
								'speed' => $rlt[$j]->speed);
			}
			if($seg) $segment[] = array('street_id' => (int) $row, 'segments' => $seg);
		}
		return $segment;
	}

	public function get_all_segments_of_the_each_cells_from_db($cell_ids){
		$segments = array();
		foreach ($cell_ids as $cell_id) {
			$segment = $this->cache->memcached->get($cell_id);
			if($segment == FALSE){
				// Find all street_id from segmentcell table
	        	$street_ids = $this->find_street_id(array($cell_id));

	        	// Find all segment_id from table's segmentcell
	        	$segment_ids = $this->find_segment_id(array($cell_id));

	        	$segment = $this->find_speed_segment($street_ids, $segment_ids);

	        	$this->cache->memcached->save($cell_id, $segment);
			}
			$segments = array_merge($segments, $segment);
		}
        return $segments;
	}

}