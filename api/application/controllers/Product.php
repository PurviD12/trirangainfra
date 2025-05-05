<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

use PhpOffice\PhpSpreadsheet\IOFactory;
require_once 'library/excel/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
require 'library/excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Product extends CI_Controller {

    public function __construct()

    {
        parent::__construct();
        
        $this->load->database();

        date_default_timezone_set('Asia/Kolkata');
        $this->db->query('SET SESSION time_zone = "+05:30"');

        $this->load->model("email_model");
        $this->load->model("front_model");

        // if(!empty($_POST) && $_POST['CodePost'] == true || $_POST['call_app'] == "true"){
            
        // }
        // else{
        //     $_POST = json_decode(file_get_contents("php://input"), true);
        // }

        $postJson = file_get_contents("php://input");

        if ($this->checkjson($postJson)) {
            /** Skipping Json Decode Request if call_app is true **/
            if ($_POST['call_app'] == "true") {

            } else {
                $_POST = json_decode(file_get_contents("php://input"), true);
            }
        }

        $this->front_model->token_verify($_POST["logged_in_user_id"]);
        
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        /*$this->db->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");*/
    }

    public function checkjson(&$json) {
        $json = json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    public function products ($action) {
        $actions = array("list");
        $post = $this->input->post();

        if (!in_array($action, $actions)) {
            $response["success"] = 0;
            $response["message"] = "Request not found.";
        } else {
            if ($action == "list") {
                $and_condition = "";

                $pagelimit = "";
                $post["limit"] = $post["limit"] ? $post["limit"] : 1000;
                
                if(!empty($post["page"])){
                    $pagelimit .= " limit ".(($post["page"]-1)*$post["limit"]).", ".$post["limit"];
                } else {
                    $post["limit"] = 1000;
                    $pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
                }

                $result  = $this->db->query("select SQL_CALC_FOUND_ROWS p.*, c.category_name from products as p LEFT JOIN product_category as pc ON(p.id = pc.product_id) LEFT JOIN category as c ON(pc.category_id = c.id) where 1=1 ".$and_condition." order by id asc ".$pagelimit)->result_array();

                if (!empty($result)) {
                    $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                    $total_records = $queryNew->row()->myCounter;

                    foreach ($result as $key => $value) {
                        $response["data"][$key] = $this->front_model->product_details($value);
                    }
                    $response["success"] = 1;
                    $response["message"] = "Records found.";
                    $response["total_records"] = intval($total_records);
                } else {
                    $response["data"] = array();
                    $response["success"] = 0;
                    $response["message"] = "Records not found.";
                    $response["total_records"] = 0;
                }
            } else {
                $response["success"] = 0;
                $response["message"] = "Request not found.";
            }
        }

        echo json_encode($response);
        die;
    }
}