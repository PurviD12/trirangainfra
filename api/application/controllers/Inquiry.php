<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

class Inquiry extends CI_Controller {

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

        $allowedMethods = array("cron_job");
        $currentMethod = $this->router->fetch_method();
        
        
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

    public function inquiry ($action) {
        $actions = array("save");
        $post = $this->input->post();
        
        if (!in_array($action, $actions)) {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        } else {
            if ($action == "save") {
                $numlength = 0;
                $mobile_no_validate = "";
                
                if (!empty($post["contact_no"])) {
                   $numlength = strlen((string)$post["contact_no"]);
                   $mobile_no_validate = $this->front_model->validate_mobile($post["contact_no"]);
                }


                if (empty($post["name"]) || empty($post["email_address"]) || empty($post["contact_no"]) || empty($post["subject"]) || empty($post["inquiry_type"])){
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                }  else if (!empty($post["email_address"]) && !$this->front_model->validate_email($post["email_address"])) {
                    $response["success"] = 0;
                    $response["message"] = "Invalid Email Address.";
                }   else if (in_array($post["contact_no"], $this->front_model->contact_no_dummy())) {
                    $response["success"] = 0;
                    $response["message"] = "Invalid Mobile number.";
                }  else {
                    $time = time();

                    $data["name"] = $post["name"];
                    $data["inquiry_type"] = $post["inquiry_type"];
                    $data["email_address"] = $post["email_address"] ? $post["email_address"] : null;
                    $data["contact_no"] = $post["contact_no"] ? $post["contact_no"] : null;
                    $data["subject"] = $post["subject"] ? $post["subject"] : null;
                    $data["comments"] = $post["comments"] ? $post["comments"] : null;
                    $data["created_at"] = $time;

                    $this->db->insert("inquiries", $data);
                    $inquiry_id = $this->db->insert_id();

                    if ($this->db->affected_rows() > 0) {
                        $response["success"] = 1;
                        $response["message"] = "Inquiry has been sent successfully.";

                        $subject = "Contact Inquiry";
                        // $email_to = "purvid@saptez.com";
                        // $bcc = array("purvid@saptez.com");
                        $email_to = "Kevalpatel789@gmail.com";
                        $bcc = array("vrushik@saptez.com", "hemang@saptez.com", "maharshi@saptez.com");
                        $this->email_model->inquiry_email($data, $subject, $bcc, $email_to);
                    } else {
                        $response["success"] = 0;
                        $response["message"] = "Oops... Something went wrong. Please try again.";
                    }
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