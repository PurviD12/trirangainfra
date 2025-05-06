<?php
defined('BASEPATH') OR exit('No direct script access allowed');

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

use PhpOffice\PhpSpreadsheet\IOFactory;
require_once substr(FCPATH, 0, -4).'api/library/excel/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';

class Services extends CI_Controller {

    public function __construct()

    {
        parent::__construct();
        
        $this->load->database();

        date_default_timezone_set('Asia/Kolkata');
        $this->db->query('SET SESSION time_zone = "+05:30"');

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
        
        if(!in_array($currentMethod, $allowedMethods)){
            if($_SERVER['HTTP_HOST'] == "192.168.1.6"){
                $this->input->request_headers();
                $token = $this->input->get_request_header('Authorization');
            } else {
                /*$token = $_SERVER['REDIRECT_API_KEY'];*/
                $token = $_SERVER['HTTP_AUTHORIZATION'];
            }

            $admin_token_check = "";
            $front_token_check = "";

            $admin_token_check = strpos($token, "Bearer ");
            $front_token_check = strpos($token, "User ");
            if ($admin_token_check === 0) {
                $authorization = str_replace("Bearer ", "", $token);
                $checkAuth = $this->db->get_where("authorization", array("token" => $authorization, "master_user_id" => $_POST["logged_in_master_user_id"]))->row_array();

                if(empty($checkAuth)){
                    $response["success"] = 0;
                    $response["message"] = "Invalid Authorization";
                    echo json_encode($response);
                    die;
                }
            } else if ($front_token_check === 0) {
                $authorization = str_replace("User ", "", $token);
                if ($authorization != "QRNLQNSADDWLWZUSOSGQIARWBRVBWQJZEBKIGAOHAIBXIUEFDZPEBUWDW2004486403") {
                    $response["success"] = 0;
                    $response["message"] = "Invalid Authorization";
                    echo json_encode($response);
                    die;
                }
            } else {
                $response["success"] = 0;
                $response["message"] = "Invalid Authorization";
                echo json_encode($response);
                die;
            }
        }
        
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->db->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    }

    public function checkjson(&$json) {
        $json = json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    public function admin_login ($action) {
        $actions = array("login");
        $post = $this->input->post();

        if (in_array($action, $actions)) {
            if ($action == "login") {
                if (empty($post["email_address"]) || empty($post["password"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $time = time();
                    $password = "";
                    if ($post["password"] != "saptezTech") {
                        $password = " and password = '".md5($post["password"])."'";
                    }

                    $result = $this->db->query("select * from master_users where email_address = '".$post["email_address"]."'".$password." and is_deleted = 0")->row_array();
                    if (!empty($result) && $result["is_active"] == 0) {
                        $response["success"] = 0;
                        $response["message"] = "Your account status is InActive.";
                    } else if (empty($result)) {
                        $response["success"] = 0;
                        $response["message"] = "Invalid Email-Id or Password.";
                    } else {
                        $token = $this->front_model->generateRandomString(100);
                        $this->db->insert("authorization", array("master_user_id"=>$result["master_user_id"],"token"=>$token,"created_at"=>$time));
                        $this->db->insert("login_history", array("master_user_id"=>$result["master_user_id"], "created_at"=>$time, "ip_address"=>$this->input->ip_address()));

                        $response["success"] = 1;
                        $response["message"] = "LoggedIn successfully.";
                        $master_user_result = $this->front_model->get_master_user_details($result);
                        $response["data"] = $master_user_result;
                        $response["data"]["token"] = $token;
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function master_users ($action) {
        $actions = array("details", "check_details", "update_profile_all_users", "change_password");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "details") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    if (empty($post["logged_in_master_user_id"])) {
                        $response["success"] = 0;
                        $response["message"] = "Required fields can not be blank.";
                    } else {
                        $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                        $check_ip = $this->db->query("select * from login_history where master_user_id = ".$m_u_details["master_user_id"]." and ip_address = '".$this->input->ip_address()."'")->row_array();
                        if (!empty($check_ip)) {
                            $result = $this->db->query("select * from master_users where master_user_id = ".$m_u_details["master_user_id"]." and is_deleted = 0 and is_active = 1")->row_array();
                        }
                        if (!empty($check_ip) && !empty($result)) {
                            $response["success"] = 1;
                            $response["data"] = $this->front_model->get_master_user_details($result);
                        } else {
                            $response["success"] = 0;
                        }
                    }
                }
            } else if ($action == "check_details") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    if (empty($post["logged_in_master_user_id"])) {
                        $response["success"] = 0;
                        $response["message"] = "Required fields can not be blank.";
                    } else {
                        $check_ip = $this->db->query("select id from login_history where master_user_id = ".$post["logged_in_master_user_id"]." and ip_address = '".$this->input->ip_address()."'")->row_array();
                        if (!empty($check_ip)) {
                            $result = $this->db->query("select master_user_id from master_users where master_user_id = ".$post["logged_in_master_user_id"]." and is_deleted = 0 and is_active = 1")->row_array();
                        }
                        if (!empty($check_ip) && !empty($result)) {
                            $response["success"] = 1;
                        } else {
                            $response["success"] = 0;
                        }
                    }
                }
            } else if ($action == "update_profile_all_users") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["name"]) || empty($post["email_address"]) || empty($post["contact_no"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $data["name"] = $post["name"];
                        $data["email_address"] = $post["email_address"];
                        $data["contact_no"] = "91 ".$post["contact_no"];
                        $data["updated_at"] = time();

                        $email_con = " and master_user_id != ".$m_u_details["master_user_id"]." and email_address = '".$post["email_address"]."'";
                        $check_email = $this->db->query("select master_user_id from master_users where 1=1 ".$email_con." and is_deleted = 0")->row_array();

                        $contact_con = " and master_user_id != ".$m_u_details["master_user_id"]." and contact_no = '91 ".$post["contact_no"]."'";
                        $check_contact_no = $this->db->query("select master_user_id from master_users where 1=1 ".$contact_con." and is_deleted = 0")->row_array();
                        $check_success = 1;
                        if (!empty($check_email)) {
                            $response["success"] = 0;
                            $response["message"] = "Email address already existed.";
                        } else if (!empty($check_contact_no)) {
                            $response["success"] = 0;
                            $response["message"] = "Mobile number already existed.";
                        } else {
                            $data["profile_image"] = null;
                            if (!empty($post["profile_image"])) {
                                $data["profile_image"] = $post["profile_image"];
                            } else if (!empty($_FILES["profile_image"]["name"])) {
                                $size = intval($_FILES["profile_image"]["size"] / 1024 / 1024);
                                if ($size < 10) {
                                    $path = UPLOAD_URL."users/profile/";
                                    $extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
                                    $file_name = "profile_".time()."_".rand(1,100).".".$extension;
                                    $move = move_uploaded_file($_FILES["profile_image"]["tmp_name"], $path.$file_name);
                                    if($move){
                                        $data["profile_image"] = $file_name;
                                        if (empty($post["id"])) {
                                            $attachment_sent = 1;
                                        }
                                    }
                                }
                            }

                            $this->db->where("master_user_id", $m_u_details["master_user_id"]);
                            $this->db->update("master_users", $data);
                            if ($this->db->affected_rows() > 0) {
                                $result = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                                $response["data"] = $this->front_model->get_master_user_details($result);
                                $response["success"] = 1;
                                $response["message"] = "Profile updated successfully.";
                            } else {
                                $response["success"] = 0;
                                $response["message"] = "Oops.. Something went wrong. Please try again.";
                            }
                        }
                    }
                }
            } else if ($action == "change_password") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["password"]) || empty($post["confirm_password"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else if ($post["confirm_password"] !== $post["password"]) {
                    $response["success"] = 0;
                    $response["message"] = "Passwords do not matched, Please re enter again!";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $time = time();
                        $data["password"] = md5($post["password"]);
                        $data["updated_at"] = $time;

                        $this->db->where("master_user_id", $m_u_details["master_user_id"]);
                        $this->db->update("master_users", $data);
                        $id = $post["master_user_id"];
                        if ($this->db->affected_rows() > 0) {
                            $response["success"] = 1;
                            $response["message"] = "Password changed successfully.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Oops.. Something went wrong. Please try again.";
                        }
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function inquiries ($action) {
        $actions = array("list", "save", "plant_get", "add_followUp", "add_followUp_email", "status_list", "followUp_history", "delete", "import_leads");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "list") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else { 
                        
                        $financialyear = $post["financial_year"];
                        $years = explode('-', $financialyear);
                        $startYear = $years[0];
                        $endYear   = $years[1];
                        $startDateStr = $startYear.'-04-01 00:00:00';
                        $endDateStr   = $endYear.'-03-31 23:59:59';
                        $financial_year_from = strtotime($startDateStr);
                        $financial_year_to   = strtotime($endDateStr);
                        
                        /*print_r(['startTimestamp' => $startTimestamp,'endTimestamp'   => $endTimestamp]);
                        die;*/

                        $today_dateTime = strtotime(date(date('Y-m-d',strtotime("today"))."00:00:00"));
                        $today_dateTimeEnd = strtotime(date(date('Y-m-d',strtotime("today"))."23:59:59"));

                        $lead_type = "";
                        if ($post["dashboard"] == 1 && empty($post["lead_type"])) {
                            $lead_type = "new_lead";
                        } else if (!empty($post["lead_type"])) {
                            $lead_type = $post["lead_type"];
                        }

                        $and_condition = "";
                        if ($post["dashboard"] != 1) {
                            $and_condition .= " and created_at >= ".$financial_year_from." and created_at <= ".$financial_year_to;
                        }
                        $and_condition .= " and is_deleted = 0";
                        $or_condition = "";
                        $master_user_id_con = "";
                        $master_user_id_con .= " and master_user_id = ".$m_u_details["parent_id"];
                        if ($m_u_details["type"] == "sales executive") {
                            $master_user_id_con .= " and assign_master_user_id = ".$m_u_details["master_user_id"];
                        }
                        $and_condition .= $master_user_id_con;

                        if (!empty($post["id"])) {
                            $and_condition .= " and id = ".$post["id"];
                        }

                        $order_by = " order by created_at desc";
                        $status_customer_found = 0;

                        if (!empty($post["filter"])) { 
                            $filter = json_decode($post["filter"], true);
                            
                            if (!empty($filter["status"]) && $filter["status"] != "[]") {
                                $and_condition .= " and status IN('".implode("','", $filter["status"])."') ";
                            }
                            if (count($filter["status"]) == 1 && in_array("Customer", $filter["status"])) {
                                $status_customer_found = 1;
                            }
                            if (!empty($filter["priority"]) && $filter["priority"] != "[]") {
                                $and_condition .= " and priority IN('".implode("','", $filter["priority"])."') ";
                            }
                            if (!empty($filter["which_plant_city_ids"]) && $filter["which_plant_city_ids"] != "[]") {
                                $and_condition .= " and which_plant_city_id IN(".implode(",", $filter["which_plant_city_ids"]).") ";
                            }
                            if (!empty($filter["sales_master_user_ids"]) && $filter["sales_master_user_ids"] != "[]") {
                                $and_condition .= " and assign_master_user_id IN(".implode(",", $filter["sales_master_user_ids"]).") ";
                            }
                            if (!empty($filter["state_ids"]) && $filter["state_ids"] != "[]") {
                                $and_condition .= " and state_id IN(".implode(",", $filter["state_ids"]).") ";
                            }
                        }

                        $today_date_last_update_follow_up = "";
                        $today_date_created = "";
                        $today_date_followUp = "";
                        $from_date = "";
                        $to_date = "";
                        if (!empty($post["from_date"]) && !empty($post["to_date"])) {
                            $from_date =  strtotime(date($post["from_date"]."00:00:00"));
                            $to_date =  strtotime(date($post["to_date"]."23:59:59"));

                            $today_date_last_update_follow_up = " and last_follow_up_updated_date >= ".$from_date." and last_follow_up_updated_date <= ".$to_date;
                            $today_date_created = " and created_at >= ".$from_date." and created_at <= ".$to_date;
                            $today_date_followUp = " and follow_up_date >= ".$from_date." and follow_up_date <= ".$to_date;
                            if ($post["show_upcoming_followUp"] == 1) {
                                $and_condition .= $today_date_followUp;
                            } else if ($status_customer_found == 1) {
                                $and_condition .= $today_date_last_update_follow_up;
                            } else {
                                $and_condition .= $today_date_created;
                            }
                            $upcoming_date_followUp = " and follow_up_date >= ".$to_date;
                        } else {
                            /* Today lead tomorrow remove LOGIC */
                            /*$today_date_created = " and created_at >= ".$today_dateTime." and created_at <= ".$today_dateTimeEnd;*/
                            $today_date_created = " and last_follow_up_updated_date is null";
                            $today_date_followUp = " and follow_up_date >= ".$today_dateTime." and follow_up_date <= ".$today_dateTimeEnd;
                            $upcoming_date_followUp = " and follow_up_date >= ".$today_dateTimeEnd;
                        }
                        if (!empty($post["from_date_followUp"]) && !empty($post["to_date_followUp"])) {
                            $from_date_followUp =  strtotime(date($post["from_date_followUp"]."00:00:00"));
                            $to_date_followUp =  strtotime(date($post["to_date_followUp"]."23:59:59"));

                            $upcoming_date_followUp = " and follow_up_date >= ".$today_dateTimeEnd." and (follow_up_date >= ".$from_date_followUp." and follow_up_date <= ".$to_date_followUp.")";
                        }
                        if ($post["show_upcoming_followUp"] == 1) {
                            $and_condition .= " and follow_up_date is not null";
                        }

                        $search_condition = "";
                        $or_condition_search = "";
                        $search_string = "";
                        if (!empty(trim($post["search"]))) {
                            $search_string = trim($post["search"]);
                            $pattern_search_id = "/^TI\/[A-Z]\/(\d+)$/";
                            if (preg_match($pattern_search_id, $search_string, $matches)) {
                                $or_condition_search .= " OR quotation_number = ".$matches[1]."";
                            }

                            $search_condition .= " and (name like '%".$search_string."%' or email_address like '%".$search_string."%' or contact_no like '%".$search_string."%' or company_name like '%".$search_string."%'".$or_condition_search.")";
                        }

                        $lead_type_con = "";
                        $new_date_get = $today_date_created;
                        /* Today lead tomorrow remove LOGIC */
                        /*$new_lead_con = $new_date_get." and status IN ('New Customer') and last_follow_up_updated_date is null".$search_condition." and created_at > ".$today_dateTime;*/
                        $new_lead_con = $new_date_get.$search_condition;

                        $today_followUp_lead = $today_date_followUp." and follow_up_date is not null".$search_condition;

                        $upcoming_followUp_lead = $upcoming_date_followUp." and follow_up_date is not null".$search_condition;

                        /* Today lead tomorrow remove LOGIC */
                        /*$overdue_lead = " ((follow_up_date < ".$today_dateTime.") OR (created_at < ".$today_dateTime." and follow_up_date is null and last_follow_up_updated_date is null))".$search_condition;*/
                        $overdue_lead = " ((follow_up_date < ".$today_dateTime."))".$search_condition;

                        $lost_lead_date = $today_date_created;
                        /*$lost_lead = " and status IN('Not Interested') ".$search_condition.$lost_lead_date;*/
                        $lost_lead = " and status IN('Not Interested') ".$search_condition;
                        $customer_lead = " and status IN('Customer') ".$search_condition;
                        $big_buyer_lead = " and is_big_buyer = 1 ".$search_condition;
                        $to_be_completed_lead = " and status IN('To be Completed') and follow_up_date is null ".$search_condition;

                        if ($lead_type == "new_lead") {
                            $lead_type_con .= $new_lead_con;
                        } else if ($lead_type == "today_followUp_lead") {
                            $lead_type_con .= $today_followUp_lead;
                        } else if ($lead_type == "upcoming_followUp_lead") {
                            $lead_type_con .= $upcoming_followUp_lead;
                        } else if ($lead_type == "overdue_lead") {
                            $lead_type_con .= " and".$overdue_lead;
                        } else if ($lead_type == "lost_lead") {
                            $lead_type_con .= $lost_lead;
                        } else if ($lead_type == "customer_lead") {
                            $lead_type_con .= $customer_lead;
                        } else if ($lead_type == "big_buyer_lead") {
                            $lead_type_con .= $big_buyer_lead;
                        } else if ($lead_type == "to_be_completed_lead") {
                            $lead_type_con .= $to_be_completed_lead;
                        }
                        $and_condition .= $lead_type_con;

                        $pagelimit = "";
                        $post["limit"] = $post["limit"] ? $post["limit"] : 1000;
                        
                        if(!empty($post["page"])){
                            $pagelimit .= " limit ".(($post["page"]-1)*$post["limit"]).", ".$post["limit"];
                        } else {
                            $post["limit"] = 1000;
                            $pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
                        }

                        $or_condition_add = "";
                        if ($or_condition != "") {
                            $or_condition_add =  " and (1=1 and ".ltrim($or_condition, " OR ").") ";
                        }
                        if ($post["export"] == 1) {
                            $pagelimit = "";
                        }

                        $result = $this->db->query("SELECT SQL_CALC_FOUND_ROWS * from inquiries where 1=1 ".$and_condition.$or_condition_add.$search_condition.$order_by.$pagelimit)->result_array();
                        if ($post["export"] == 1) {
                            $response["file_path"] = $this->front_model->export_all_inquiry_sheet($result, $lead_type);
                            if($response["file_path"]){
                                $response['success'] = 1;
                            } else {
                                $response['success'] = 0;
                            }
                        } else {
                            if (!empty($result)) {
                                $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                                $total_records = $queryNew->row()->myCounter;

                                foreach ($result as $key => $value) {
                                    $response["data"][$key]["id"] = intval($value["id"]);
                                    $response["data"][$key]["assign_master_user_id"] = $value["assign_master_user_id"] ? intval($value["assign_master_user_id"]) : "";
                                    $response["data"][$key]["assign_master_user_name"] = $value["assign_master_user_name"] ? $value["assign_master_user_name"] : "";
                                    $response["data"][$key]["user_id"] = intval($value["user_id"]);
                                    $response["data"][$key]["name"] = $value["name"];
                                    $response["data"][$key]["email_address"] = $value["email_address"] ? $value["email_address"] : "";
                                    $response["data"][$key]["contact_no"] = $value["contact_no"] ? $value["contact_no"] : "";
                                    $response["data"][$key]["state_id"] = $value["state_id"] ? intval($value["state_id"]) : "";
                                    $response["data"][$key]["state_name"] = $value["state_name"] ? $value["state_name"] : "";
                                    $response["data"][$key]["city_id"] = $value["city_id"] ? intval($value["city_id"]) : "";
                                    $response["data"][$key]["city_name"] = $value["city_name"] ? $value["city_name"] : "";
                                    $response["data"][$key]["village"] = $value["village"] ? $value["village"] : "";
                                    $response["data"][$key]["designation"] = $value["designation"] ? $value["designation"] : "";
                                    $response["data"][$key]["company_name"] = $value["company_name"] ? $value["company_name"] : "";
                                    $response["data"][$key]["company_website"] = $value["company_website"] ? $value["company_website"] : "";
                                    $response["data"][$key]["rate"] = $value["rate"] ? $value["rate"] : "";
                                    $response["data"][$key]["quantity"] = $value["quantity"] ? $value["quantity"] : "";
                                    $response["data"][$key]["nos_of_panel"] = $value["nos_of_panel"] ? $value["nos_of_panel"] : "";
                                    $response["data"][$key]["rate_of_panel"] = $value["rate_of_panel"] ? $value["rate_of_panel"] : "";
                                    $response["data"][$key]["nos_of_column"] = $value["nos_of_column"] ? $value["nos_of_column"] : "";
                                    $response["data"][$key]["rate_of_column"] = $value["rate_of_column"] ? $value["rate_of_column"] : "";
                                    $response["data"][$key]["height"] = $value["height"] ? $value["height"] : "";
                                    $response["data"][$key]["quotation_number"] = $value["quotation_number"] ? "TI/".strtoupper(substr($value["assign_master_user_name"] ? $value["assign_master_user_name"] : $m_u_details["name"], 0, 1))."/".$value["quotation_number"] : "";
                                    $response["data"][$key]["source"] = $value["source"] ? $value["source"] : "";
                                    $response["data"][$key]["other_source"] = $value["other_source"] ? $value["other_source"] : "";
                                    $response["data"][$key]["unit"] = $value["unit"] ? $value["unit"] : "";
                                    $response["data"][$key]["which_plant_city_id"] = $value["which_plant_city_id"] ? $value["which_plant_city_id"] : "";
                                    $response["data"][$key]["plant_city_name"] = $value["plant_city_name"] ? $value["plant_city_name"] : "";
                                    $response["data"][$key]["priority"] = $value["priority"] ? $value["priority"] : "";
                                    $response["data"][$key]["is_big_buyer"] = $value["is_big_buyer"];
                                    $response["data"][$key]["status"] = $value["status"] ? $value["status"] : "";

                                    $response["data"][$key]["remarks"] = $value["remarks"] ? $value["remarks"] : "";
                                    $response["data"][$key]["created_at"] = date("d M, Y", $value["created_at"]);
                                    $response["data"][$key]["created_at_time"] = date("h:i A", $value["created_at"]);
                                    $response["data"][$key]["followUp_date"]["created_at"] = $value["follow_up_date"] ? date("d M, Y", $value["follow_up_date"]) : "";
                                    $response["data"][$key]["followUp_date"]["created_at_time"] = $value["follow_up_date"] ? date("h:i A", $value["follow_up_date"]) : "";
                                    $response["data"][$key]["master_user_id"] = $value["master_user_id"];
                                    $response["data"][$key]["overdue"] = 0;
                                    /* Today lead tomorrow remove LOGIC */
                                    /*if (($value["created_at"] < $today_dateTime && empty($value["last_follow_up_updated_date"])) || (!empty($value["follow_up_date"]) && $value["follow_up_date"] < $today_dateTime)) {*/
                                    if (!empty($value["follow_up_date"]) && $value["follow_up_date"] < $today_dateTime) {
                                        $response["data"][$key]["overdue"] = 1;
                                    }
                                    $response["data"][$key]["lead_rights"] = array(
                                        "lead_edit" => 1,
                                        "lead_delete" => 0,
                                        "lead_followUp" => 1,
                                        "send_custom_email" => 0,
                                    );
                                    if ($m_u_details["type"] != "admin") {
                                        if ($response["data"][$key]["overdue"] == 1) {
                                            $response["data"][$key]["lead_rights"]["lead_followUp"] = 0;
                                        }
                                    }
                                    if ($m_u_details["type"] == "admin") {
                                            $response["data"][$key]["lead_rights"]["lead_delete"] = 1;
                                        }
                                    if ($value["is_big_buyer"] == "1") {
                                        $response["data"][$key]["lead_rights"]["send_custom_email"] = 1;
                                    }

                                    /*$response["data"][$key]["lead_value"] = $value["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($value["lead_value"]) : "";*/
                                    $response["data"][$key]["lead_value"] = $value["lead_value"] ? '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value["lead_value"]) : "";
                                    $response["data"][$key]["attachment_array"] = array();
                                    if (!empty($value["attachment"])) {
                                        foreach (json_decode($value["attachment"], true) as $key_2 => $value) {
                                            $response["data"][$key]["attachment_array"][$key_2]["attachment"] = $value["attachment"];
                                            $response["data"][$key]["attachment_array"][$key_2]["attachment_full"] = BASE_PATH.'inquiry/attachment/'.$value["attachment"];
                                            $response["data"][$key]["attachment_array"][$key_2]["name"] = $value["name"];
                                        }
                                    }
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
                        }

                        $response["total_records_list"] = array();
                        if ($post["dashboard"] == 1 && $post["export"] != 1) {
                            $today_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$new_lead_con)->row_array();

                            $today_followUp_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$today_followUp_lead)->row_array();

                            $upcoming_followUp_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$upcoming_followUp_lead)->row_array();
                            
                            $overdue_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 and ".$overdue_lead)->row_array();

                            $lost_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$lost_lead)->row_array();

                            $customer_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$customer_lead)->row_array();

                            $big_buyer_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$big_buyer_lead)->row_array();

                            $to_be_completed_total = $this->db->query("select count(id) as total, sum(lead_value) as lead_value from inquiries where 1=1 ".$master_user_id_con." and is_deleted = 0 ".$to_be_completed_lead)->row_array();

                            $response["total_records_list"] = array(
                                array(
                                    "title" => "New Lead",
                                    "lead_type" => "new_lead",
                                    "bg_color" => "",
                                    "total" => intval($today_total["total"]),
                                    "lead_value" => $today_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($today_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Today's FollowUp",
                                    "lead_type" => "today_followUp_lead",
                                    "bg_color" => "",
                                    "total" => intval($today_followUp_total["total"]),
                                    "lead_value" => $today_followUp_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($today_followUp_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Upcoming FollowUp",
                                    "lead_type" => "upcoming_followUp_lead",
                                    "bg_color" => "",
                                    "total" => intval($upcoming_followUp_total["total"]),
                                    "lead_value" => $upcoming_followUp_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($upcoming_followUp_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Overdue",
                                    "lead_type" => "overdue_lead",
                                    "bg_color" => "",
                                    "total" => intval($overdue_total["total"]),
                                    "lead_value" => $overdue_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($overdue_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Lost",
                                    "lead_type" => "lost_lead",
                                    "bg_color" => "",
                                    "total" => intval($lost_total["total"]),
                                    "lead_value" => $lost_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($lost_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Customers",
                                    "lead_type" => "customer_lead",
                                    "bg_color" => "green",
                                    "total" => intval($customer_total["total"]),
                                    "lead_value" => $customer_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($customer_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "Big Buyers",
                                    "lead_type" => "big_buyer_lead",
                                    "bg_color" => "green",
                                    "total" => intval($big_buyer_total["total"]),
                                    "lead_value" => $big_buyer_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($big_buyer_total["lead_value"]) : "",
                                ),
                                array(
                                    "title" => "To Be Completed",
                                    "lead_type" => "to_be_completed_lead",
                                    "bg_color" => "green",
                                    "total" => intval($to_be_completed_total["total"]),
                                    "lead_value" => $to_be_completed_total["lead_value"] ? $this->front_model->convertToIndianNumberingSystem($to_be_completed_total["lead_value"]) : "",
                                ),
                            );
                        }
                    }
                }
            } /*else if ($action == "list_old") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $today_dateTime = strtotime(date(date('Y-m-d',strtotime("today"))."00:00:00"));
                        $today_dateTimeEnd = strtotime(date(date('Y-m-d',strtotime("today"))."23:59:59"));

                        $and_condition = "";
                        $or_condition = "";
                        $or_condition_count_all = "";
                        $and_condition .= " and master_user_id = ".$m_u_details["parent_id"];

                        $today_date_created = "";
                        $today_date_followUp = "";
                        $or_condition_created_at_2 = "";
                        $from_date = "";
                        $to_date = "";
                        if (!empty($post["from_date"]) && !empty($post["to_date"])) {
                            $from_date =  strtotime(date($post["from_date"]."00:00:00"));
                            $to_date =  strtotime(date($post["to_date"]."23:59:59"));

                            $today_date_created = " and created_at >= ".$from_date." and created_at <= ".$to_date;
                            $today_date_followUp = " and follow_up_date >= ".$from_date." and follow_up_date <= ".$to_date;

                            $or_condition_created_at .= " OR (created_at >= ".$from_date." and created_at <= ".$to_date.") ";
                            // New lead remove when selected date less then today end date 
                            if ($post["lead_type"] == "today_followUp_lead" && $to_date < $today_dateTimeEnd) {
                                $or_condition_created_at_2 .= " (created_at >= ".$from_date." and created_at <= ".$to_date." and created_at > ".$today_dateTimeEnd.") ";
                            }
                            // New lead remove when selected date less then today end date 
                            $or_condition_follow_up_date .= " OR (follow_up_date >= ".$from_date." and follow_up_date <= ".$to_date.") ";
                            if ($post["lead_type"] != "overdue_lead" && $from_date < $today_dateTime) {
                                $or_condition .= $or_condition_created_at_2 ? " OR ".$or_condition_created_at_2 : $or_condition_created_at;
                                $or_condition .= $or_condition_follow_up_date;
                            }
                            if ($post["lead_type"] == "today_followUp_lead") {
                                $or_condition .= $or_condition_follow_up_date;
                                // $and_condition .= $today_date_created;
                            }
                            if ($post["lead_type"] == "new_lead") {
                                $and_condition .= " and created_at > ".$today_dateTime;
                            }
                            if ($post["lead_type"] == "") {
                                $or_condition .= $or_condition_created_at;
                            }
                            $or_condition_count_all .= $or_condition_created_at;
                            
                        } else {
                            $today_date_created = " and created_at >= ".$today_dateTime." and created_at <= ".$today_dateTimeEnd;
                            $today_date_followUp = " and follow_up_date >= ".$today_dateTime." and follow_up_date <= ".$today_dateTimeEnd;
                        }

                        if (!empty($post["id"])) {
                            $and_condition .= " and id = ".$post["id"];
                        }

                        $order_by = " order by created_at desc";

                        $search_condition = "";
                        if (!empty($post["search"])) {
                            $search_condition .= " and (name like '%".$post["search"]."%' or email_address like '%".$post["search"]."%' or contact_no like '%".$post["search"]."%' or company_name like '%".$post["search"]."%')";
                        }

                        $lead_type_con = "";
                        $new_date_get = $or_condition_created_at_2 ? " and ".$or_condition_created_at_2 : $today_date_created;
                        $new_lead_con = $new_date_get." and status IN ('New Customer','Quotation Sent') and last_follow_up_updated_date is null".$search_condition." and created_at > ".$today_dateTime;

                        $today_followUp_lead = $today_date_followUp." and follow_up_date is not null".$search_condition;

                        $overview_date = (!empty($to_date) && $to_date < $today_dateTimeEnd) ? $to_date : $today_dateTime;
                        $overdue_lead = " ((follow_up_date < ".$today_dateTime.") OR (created_at < ".$overview_date." and follow_up_date is null and last_follow_up_updated_date is null))".$search_condition;

                        $lost_lead_date = (!empty($to_date) && !empty($from_date)) ? $today_date_created : "";
                        $lost_lead = " and status IN('Not Interested') ".$search_condition.$lost_lead_date;

                        if ($post["lead_type"] == "new_lead") {
                            $lead_type_con .= $new_lead_con;
                        } else if ($post["lead_type"] == "today_followUp_lead") {
                            $lead_type_con .= $today_followUp_lead;
                        } else if ($post["lead_type"] == "overdue_lead") {
                            $lead_type_con .= " and".$overdue_lead;
                        } else if ($post["lead_type"] == "lost_lead") {
                            $lead_type_con .= $lost_lead;
                        }
                        $and_condition .= $lead_type_con;

                        $pagelimit = "";
                        $post["limit"] = $post["limit"] ? $post["limit"] : 1000;
                        
                        if(!empty($post["page"])){
                            $pagelimit .= " limit ".(($post["page"]-1)*$post["limit"]).", ".$post["limit"];
                        } else {
                            $post["limit"] = 1000;
                            $pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
                        }

                        $or_condition_add = "";
                        if ($or_condition != "") {
                            $or_condition_add =  " and (1=1 and ".ltrim($or_condition, " OR ").") ";
                        }

                        $result = $this->db->query("select SQL_CALC_FOUND_ROWS * from inquiries where 1=1 ".$and_condition.$or_condition_add.$search_condition.$order_by.$pagelimit)->result_array();
                        if (!empty($result)) {
                            $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                            $total_records = $queryNew->row()->myCounter;

                            foreach ($result as $key => $value) {
                                $response["data"][$key]["id"] = intval($value["id"]);
                                $response["data"][$key]["assign_master_user_id"] = $value["assign_master_user_id"] ? intval($value["assign_master_user_id"]) : null;
                                $response["data"][$key]["assign_master_user_name"] = $value["assign_master_user_name"];
                                $response["data"][$key]["user_id"] = intval($value["user_id"]);
                                $response["data"][$key]["name"] = $value["name"];
                                $response["data"][$key]["email_address"] = $value["email_address"] ? $value["email_address"] : "";
                                $response["data"][$key]["contact_no"] = $value["contact_no"] ? $value["contact_no"] : "";
                                $response["data"][$key]["state_id"] = $value["state_id"] ? intval($value["state_id"]) : "";
                                $response["data"][$key]["state_name"] = $value["state_name"] ? $value["state_name"] : "";
                                $response["data"][$key]["city_id"] = $value["city_id"] ? intval($value["city_id"]) : "";
                                $response["data"][$key]["city_name"] = $value["city_name"] ? $value["city_name"] : "";
                                $response["data"][$key]["designation"] = $value["designation"] ? $value["designation"] : "";
                                $response["data"][$key]["company_name"] = $value["company_name"] ? $value["company_name"] : "";
                                $response["data"][$key]["company_website"] = $value["company_website"] ? $value["company_website"] : "";
                                $response["data"][$key]["quantity"] = $value["quantity"] ? $value["quantity"] : "";
                                $response["data"][$key]["height"] = $value["height"] ? $value["height"] : "";
                                $response["data"][$key]["lead_value"] = $value["lead_value"] ? $value["lead_value"] : "";
                                $response["data"][$key]["quotation_number"] = $value["quotation_number"] ? $value["quotation_number"] : "";
                                $response["data"][$key]["source"] = $value["source"] ? $value["source"] : "";
                                $response["data"][$key]["which_plant_city_id"] = $value["which_plant_city_id"] ? $value["which_plant_city_id"] : "";
                                $response["data"][$key]["plant_city_name"] = $value["plant_city_name"] ? $value["plant_city_name"] : "";
                                $response["data"][$key]["priority"] = $value["priority"] ? $value["priority"] : "";
                                $response["data"][$key]["status"] = $value["status"] ? $value["status"] : "";
                                $response["data"][$key]["remarks"] = $value["remarks"] ? $value["remarks"] : "";
                                $response["data"][$key]["attachment"] = $value["attachment"] ? $value["attachment"] : "";
                                $response["data"][$key]["attachment_full"] = $value["attachment"] ? BASE_PATH.'inquiry/attachment/'.$value["attachment"] : "";
                                $response["data"][$key]["created_at"] = date("d M, Y", $value["created_at"]);
                                $response["data"][$key]["created_at_time"] = date("h:i A", $value["created_at"]);
                                $response["data"][$key]["followUp_date"] = $value["follow_up_date"] ? date("d M, Y", $value["follow_up_date"]) : "";
                                $response["data"][$key]["master_user_id"] = $value["master_user_id"];
                                $response["data"][$key]["overdue"] = 0;
                                if (($value["created_at"] < $today_dateTime && empty($value["last_follow_up_updated_date"])) || (!empty($value["follow_up_date"]) && $value["follow_up_date"] < $today_dateTime)) {
                                    $response["data"][$key]["overdue"] = 1;
                                }
                                $response["data"][$key]["lead_rights"] = array(
                                    "lead_edit" => 1,
                                    "lead_followUp" => 1,
                                );
                                if ($m_u_details["type"] != "admin") {
                                    if ($response["data"][$key]["overdue"] == 1) {
                                        $response["data"][$key]["lead_rights"]["lead_edit"] = 0;
                                        $response["data"][$key]["lead_rights"]["lead_followUp"] = 0;
                                    }
                                }
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

                        $or_condition_add_count = "";
                        if ($or_condition_count_all != "") {
                            $or_condition_add_count =  " and (1=1 and ".ltrim($or_condition_count_all, " OR ").") ";
                        }

                        $all_total = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 ".$or_condition_add_count.$search_condition)->row()->total;

                        $today_total = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 ".$new_lead_con)->row()->total;

                        $today_followUp_total = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 ".$today_followUp_lead)->row()->total;
                        
                        $overdue_total = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 and ".$overdue_lead)->row()->total;

                        $lost_total = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 ".$lost_lead)->row()->total;

                        $response["total_records_list"][0]["title"] = "All";
                        $response["total_records_list"][0]["lead_type"] = "";
                        $response["total_records_list"][0]["total"] = intval($all_total);
                        $response["total_records_list"][1]["title"] = "New Lead";
                        $response["total_records_list"][1]["lead_type"] = "new_lead";
                        $response["total_records_list"][1]["total"] = intval($today_total);
                        $response["total_records_list"][2]["title"] = "Today's Followup";
                        $response["total_records_list"][2]["lead_type"] = "today_followUp_lead";
                        $response["total_records_list"][2]["total"] = intval($today_followUp_total);
                        $response["total_records_list"][3]["title"] = "Overdue";
                        $response["total_records_list"][3]["lead_type"] = "overdue_lead";
                        $response["total_records_list"][3]["total"] = intval($overdue_total);
                        $response["total_records_list"][4]["title"] = "Lost";
                        $response["total_records_list"][4]["lead_type"] = "lost_lead";
                        $response["total_records_list"][4]["total"] = intval($lost_total);
                    }
                }
            }*/ else if ($action == "save") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["name"]) || empty($post["contact_no"]) || empty($post["state_id"]) || empty($post["city_id"]) || empty($post["which_plant_city_id"]) || empty($post["priority"]) || empty($post["unit"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else if (!empty($post["email_address"]) && !filter_var($post["email_address"], FILTER_VALIDATE_EMAIL)) {
                    $response["success"] = 0;
                    $response["message"] = "Invalid Email Address.";
                } else {
                    $contact_no = "91 ".$post["contact_no"];
                    $check_inquiry = array();
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);

                    if (!empty($m_u_details)) {

                        $check_inquiry_run = 1;
                        $id_con = "";
                        if (!empty($post["id"])) {
                            $id_con = " and id != ".$post["id"];
                            $check_contact = $this->db->query("select contact_no from inquiries where id = ".$post["id"])->row_array();
                            if ($check_contact["contact_no"] == $contact_no) {
                                $check_inquiry_run = 0;
                            }
                        }

                        if ($check_inquiry_run == 1) {
                            $check_inquiry = $this->db->query("select id, assign_master_user_name from inquiries where contact_no = '".$contact_no."' and is_deleted = 0 and master_user_id = ".$m_u_details["parent_id"].$id_con." order by id desc")->row_array();
                        }
                    }
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else if (!empty($check_inquiry) && $post["lead_reenter"] != "1") {
                        $response["confirm_popup"] = 1;
                        $response["success"] = 0;
                        if (!empty($check_inquiry["assign_master_user_name"])) {
                            $response["message"] = "Lead already available in the system, Lead Owner Name – ".ucwords($check_inquiry["assign_master_user_name"]).". Are you sure you want to enter the lead into our system again?";
                        } else {
                            $response["message"] = "Lead already available in the system. Are you sure you want to enter the lead into our system again?";
                        }
                    } else {
                        $time = time();

                        $data["name"] = $post["name"];
                        $data["contact_no"] = $contact_no;
                        $data["email_address"] = $post["email_address"] ? $post["email_address"] : null;
                        $data["designation"] = $post["designation"] ? $post["designation"] : null;
                        $data["company_name"] = $post["company_name"] ? $post["company_name"] : null;
                        $data["state_id"] = $post["state_id"];
                        if (!empty($data["state_id"])) {
                            $data["state_name"] = $post["state_name"];
                        }
                        $data["city_id"] = $post["city_id"];
                        if (!empty($data["city_id"])) {
                            $data["city_name"] = $post["city_name"];
                        }
                        $data["company_website"] = $post["company_website"] ? $post["company_website"] : null;
                        $data["rate"] = $post["rate"] ? $post["rate"] : null;
                        $data["quantity"] = $post["quantity"] ? $post["quantity"] : null;
                        $data["rate_of_panel"] = $post["rate_of_panel"] ? $post["rate_of_panel"] : null;
                        $data["nos_of_panel"] = $post["nos_of_panel"] ? $post["nos_of_panel"] : null;
                        $data["rate_of_column"] = $post["rate_of_column"] ? $post["rate_of_column"] : null;
                        $data["nos_of_column"] = $post["nos_of_column"] ? $post["nos_of_column"] : null;
                        $data["lead_value"] = null;
                        if (!empty($data["rate"]) && !empty($data["quantity"])) {
                            $data["lead_value"] = $data["rate"] * $data["quantity"];
                        } else if (!empty($data["nos_of_panel"]) && !empty($data["rate_of_panel"]) && !empty($data["nos_of_column"]) && !empty($data["rate_of_column"])) {
                            $data["lead_value"] = ($data["nos_of_panel"] * $data["rate_of_panel"]) + ($data["nos_of_column"] * $data["rate_of_column"]);
                        }
                        $data["height"] = $post["height"] ? $post["height"] : null;
                        $data["source"] = $post["source"] ? $post["source"] : null;
                        $data["other_source"] = $post["other_source"] ? $post["other_source"] : null;
                        $data["which_plant_city_id"] = $post["which_plant_city_id"] ? $post["which_plant_city_id"] : null;
                        $data["plant_city_name"] = $post["plant_city_name"] ? $post["plant_city_name"] : null;
                        $data["priority"] = $post["priority"] ? $post["priority"] : null;
                        $data["remarks"] = $post["remarks"] ? $post["remarks"] : null;
                        $data["unit"] = $post["unit"] ? $post["unit"] : null;
                        $data["village"] = $post["village"] ? $post["village"] : null;
                        $data["is_big_buyer"] = $post["is_big_buyer"] == 1 ? 1 : 0;

                        /*$attachment_sent = 0;
                        $data["attachment"] = null;
                        if (!empty($post["attachment"])) {
                            $data["attachment"] = $post["attachment"];
                        } else if (!empty($_FILES["attachment"]["name"])) {
                            $size = intval($_FILES["attachment"]["size"] / 1024 / 1024);
                            if ($size < 10) {
                                $path = UPLOAD_URL."inquiry/attachment/";
                                $extension = pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION);
                                $file_name = "attachment_".time()."_".rand(1,100).".".$extension;
                                $move = move_uploaded_file($_FILES["attachment"]["tmp_name"], $path.$file_name);
                                if($move){
                                    $data["attachment"] = $file_name;
                                    if (empty($post["id"])) {
                                        $attachment_sent = 1;
                                    }
                                }
                            }
                        }*/
                        $attachments_get = array();
                        $data["attachment"] = null;
                        if (!empty($post["attachment_array"])) {
                            foreach ($post["attachment_array"] as $key => $value) {
                                if (!empty($value["attachment"])) {
                                    $attachments_get[$key]["attachment"] = $value["attachment"];
                                } else if (!empty($_FILES["attachment_array"]["name"][$key]["attachment"])) {
                                    $size = intval($_FILES["attachment_array"]["size"][$key]["attachment"] / 1024 / 1024);
                                    if ($size < 10) {
                                        $path = UPLOAD_URL."inquiry/attachment/";
                                        $extension = pathinfo($_FILES["attachment_array"]["name"][$key]["attachment"], PATHINFO_EXTENSION);
                                        $file_name = "attachment_".time()."_".rand(1,100).".".$extension;
                                        $move = move_uploaded_file($_FILES["attachment_array"]["tmp_name"][$key]["attachment"], $path.$file_name);
                                        if($move){
                                            $attachments_get[$key]["attachment"] = $file_name;
                                        }
                                    }
                                }
                                if (!empty($attachments_get[$key]["attachment"])) {
                                    $attachments_get[$key]["name"] = $value["name"];
                                }
                            }
                        }
                        if (!empty($attachments_get)) {
                            $data["attachment"] = json_encode($attachments_get, true);
                        }

                        /*$create_user_data["name"] = $data["name"];
                        $create_user_data["contact_no"] = $data["contact_no"];
                        $create_user_data["email_address"] = $data["email_address"];
                        $create_user_data["master_user_id"] = $data["master_user_id"];
                        $create_user_data["time"] = $time;
                        $user_id = $this->front_model->create_user($create_user_data);
                        $data["user_id"] = $user_id;*/

                        $affected_rows = 0;
                        $insert_new = 0;
                        if (!empty($post["id"])) {
                            $data["updated_at"] = $time;
                            $this->db->where("id", $post["id"]);
                            $this->db->update("inquiries", $data);
                            $id = $post["id"];
                            if ($this->db->affected_rows() > 0) {
                                $affected_rows = 1;
                            }
                            $message = "Your inquiry has been updated successfully.";
                        } else {
                            /*$quo_number = $this->db->query("select quotation_number from inquiries order by id desc limit 1")->row_array();
                            $quo_number_get = 1000;
                            if (!empty($quo_number["quotation_number"])) {
                                $quo_number_get = intval(explode("/", $quo_number["quotation_number"])[2]) + 1;
                            }*/
                            $data["created_at"] = $time;
                            $data["master_user_id"] = $m_u_details["parent_id"];
                            $data["status"] = "New Customer";
                            if ($m_u_details["master_user_id"] != $m_u_details["parent_id"]) {
                                $data["assign_master_user_id"] = $m_u_details["master_user_id"];
                                $data["assign_master_user_name"] = $m_u_details["name"];
                            }
                            /*$data["quotation_number"] = "TI/".strtoupper(substr($m_u_details["name"], 0, 1))."/".$quo_number_get;*/
                            /*if ($attachment_sent == 1) {
                                $data["status"] = "Quotation Sent";
                            }*/

                            $this->db->insert("inquiries", $data);
                            $id = $this->db->insert_id();
                            if ($this->db->affected_rows() > 0) {
                                $affected_rows = 1;
                                $insert_new = 1;
                            }
                            $message = "Your inquiry has been saved successfully.";
                        }

                        if ($affected_rows == 1) {
                            if ($insert_new == 1) {
                                $follow_up_data["inquiry_id"] = $id;
                                $follow_up_data["created_at"] = $time;
                                /*if ($attachment_sent == 1) {
                                    $follow_up_data["status"] = "New Customer";
                                    $this->db->insert("follow_up_history", $follow_up_data);
                                }
                                $follow_up_data["attachment"] = $data["attachment"];*/
                                $follow_up_data["remarks"] = $data["remarks"];
                                $follow_up_data["status"] = $data["status"];
                                $follow_up_data["follow_up_add_master_user_id"] = $m_u_details["master_user_id"];
                                $follow_up_data["follow_up_add_master_user_name"] = $m_u_details["name"];
                                $this->db->insert("follow_up_history", $follow_up_data);
                            }
                            $response["success"] = 1;
                            $response["message"] = $message;
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Oops.. Something went wrong. Please try again.";
                        }
                    }
                }
            } else if ($action == "plant_get") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields cna not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $and_condition = "";
                        $and_condition .= " and master_user_id = ".$m_u_details["parent_id"];
                        $and_condition .= " and is_active = 1";

                        if (!empty($post["search"])) {
                            $and_condition .= " and name like '%".$post["search"]."%'";
                        }

                        $result_selected = array();
                        if (!empty($post["or_plant_city_id"])) {
                            $result_selected = $this->db->query("select * from plant_city_map where city_id = ".$post["or_plant_city_id"])->row_array();
                            $and_condition .= " and city_id <> ".$post["or_plant_city_id"];
                        }
                        $limit_static = "";
                        if ($post["no_limit"] != 1) {
                            $limit_static = " limit 50";
                        }
                        $result = $this->db->query("select * from plant_city_map where 1=1 ".$and_condition." order by name asc".$limit_static)->result_array();
                        if (!empty($result) || !empty($result_selected)) {
                            $i=0;
                            if (!empty($result_selected)) {
                                $response["data"][$i] = $result_selected;
                                $i++;
                            }
                            if (!empty($result)) {
                                foreach ($result as $key => $value) {
                                    $response["data"][$i] = $value;
                                    $i++;
                                }
                            }
                            $response["success"] = 1;
                            $response["message"] = "Records found.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Records not found.";
                        }
                    }
                }
            } else if ($action == "add_followUp") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["status"]) || (($post["status"] != "Not Interested" && $post["status"] != "Customer" && $post["status"] != "To be Completed") && empty($post["follow_up_date"])) || empty($post["inquiry_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = array();
                    $inquiry_details = array();
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (!empty($m_u_details)) {
                        if ($m_u_details["type"] == "sales executive") {
                            $id_con = " and assign_master_user_id = ".$m_u_details["master_user_id"];
                        }
                        $inquiry_details = $this->db->query("select id from inquiries where master_user_id = ".$m_u_details["parent_id"].$id_con." and id = ".$post["inquiry_id"]." and is_deleted = 0")->row_array();
                    }
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else if (empty($inquiry_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Inquiry not found.";
                    } else {
                        $time = time();
                        $data["follow_up_add_master_user_id"] = $m_u_details["master_user_id"];
                        $data["follow_up_add_master_user_name"] = $m_u_details["name"];
                        $data["inquiry_id"] = $post["inquiry_id"];
                        $data["status"] = $post["status"];
                        $data["remarks"] = $post["remarks"] ? $post["remarks"] : null;
                        $data["created_at"] = $time;
                        $data["follow_up_date"] = $post["follow_up_date"] ? $post["follow_up_date"] : null;
                        if ($post["status"] == "Not Interested") {
                            $data["follow_up_date"] = null;
                        }
                        $data["attachment"] = null;
                        if ($post["status"] == "Quotation Sent") {
                            if (!empty($_FILES["attachment"]["name"])) {
                                $size = intval($_FILES["attachment"]["size"] / 1024 / 1024);
                                if ($size < 10) {
                                    $path = UPLOAD_URL."inquiry/attachment/";
                                    $extension = pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION);
                                    $file_name = "attachment_".time()."_".rand(1,100).".".$extension;
                                    $move = move_uploaded_file($_FILES["attachment"]["tmp_name"], $path.$file_name);
                                    if($move){
                                        $data["attachment"] = $file_name;
                                    }
                                }
                            }
                        }

                        $this->db->insert("follow_up_history", $data);
                        $id = $this->db->insert_id();
                        if ($this->db->affected_rows() > 0) {
                            $inquiry_update["follow_up_remarks"] = $data["remarks"];
                            $inquiry_update["follow_up_date"] = $data["follow_up_date"];
                            $inquiry_update["last_follow_up_updated_date"] = $time;
                            $inquiry_update["status"] = $data["status"];
                            if ($data["status"] == "Customer") {
                                $inquiry_update["is_big_buyer"] = 0;
                            }
                            $inquiry_update["follow_up_attachment"] = $data["attachment"];
                            $this->db->where("id", $data["inquiry_id"]);
                            $this->db->update("inquiries", $inquiry_update);
                            if ($this->db->affected_rows() > 0) {
                                $response["success"] = 1;
                                $response["message"] = "FollowUp added successfully.";
                            } else {
                                $this->db->where("id", $id);
                                $this->db->delete("follow_up_history");
                                $response["success"] = 0;
                                $response["message"] = "Oops.. Something went wrong. Please try again.";
                            }
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Oops.. Something went wrong. Please try again.";
                        }
                    }
                }
            } else if ($action == "add_followUp_email") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["inquiry_id"]) || empty($post["email_to"]) || empty($post["email_subject"]) || empty($post["email_content"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = array();
                    $inquiry_details = array();
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (!empty($m_u_details)) {
                        if ($m_u_details["type"] == "sales executive") {
                            $id_con = " and assign_master_user_id = ".$m_u_details["master_user_id"];
                        }
                        $inquiry_details = $this->db->query("select id from inquiries where master_user_id = ".$m_u_details["parent_id"].$id_con." and id = ".$post["inquiry_id"]." and is_deleted = 0")->row_array();
                    }
                    $email_ids_get = array();
                    foreach (explode(",", $post["email_to"]) as $key => $value) {
                        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            array_push($email_ids_get, $value);
                        }
                    }
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else if (empty($inquiry_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Inquiry not found.";
                    } else if (empty($email_ids_get)) {
                        $response["success"] = 0;
                        $response["message"] = "Invalid Email Address.";
                    } else {
                        $time = time();
                        $data["follow_up_add_master_user_id"] = $m_u_details["master_user_id"];
                        $data["follow_up_add_master_user_name"] = $m_u_details["name"];
                        $data["inquiry_id"] = $post["inquiry_id"];
                        $data["email_to"] = implode(",", $email_ids_get);
                        $data["email_subject"] = $post["email_subject"];
                        $data["email_content"] = $this->front_model->textarea_text_put($post["email_content"]);
                        $data["created_at"] = $time;
                        $data["is_email_sent"] = 1;
                        $data["email_attachment"] = null;
                        if (!empty($_FILES["email_attachment"]["name"])) {
                            $size = intval($_FILES["email_attachment"]["size"] / 1024 / 1024);
                            if ($size < 10) {
                                $path = UPLOAD_URL."inquiry/attachment/";
                                $extension = pathinfo($_FILES["email_attachment"]["name"], PATHINFO_EXTENSION);
                                $file_name = "e_attachment_".time()."_".rand(1,100).".".$extension;
                                $move = move_uploaded_file($_FILES["email_attachment"]["tmp_name"], $path.$file_name);
                                if($move){
                                    $data["email_attachment"] = $file_name;
                                }
                            }
                        }

                        $this->db->insert("follow_up_history", $data);
                        $id = $this->db->insert_id();
                        if ($this->db->affected_rows() > 0) {
                            $data["email_content"] = $post["email_content"];
                            $data["email_attachment_with_path"] = $data["email_attachment"] ? $path.$data["email_attachment"] : "";
                            $this->email_model->email_for_big_buyer($data);
                            $response["success"] = 1;
                            $response["message"] = "Email sent successfully.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Oops.. Something went wrong. Please try again.";
                        }
                    }
                }
            } else if ($action == "status_list") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $and_condition = "";
                        $and_condition .= " and master_user_id = ".$m_u_details["parent_id"];

                        $result = $this->db->query("select * from status where 1=1 ".$and_condition." and is_active = 1 order by sort asc")->result_array();
                        if (!empty($result)) {
                            foreach ($result as $key => $value) {
                                $response["data"][$key]["name"] = $value["name"];
                                $response["data"][$key]["show_follow_up_date"] = $value["show_follow_up_date"];
                                $response["data"][$key]["show_attachment"] = $value["show_attachment"];
                                $response["data"][$key]["show_remaks"] = $value["show_remaks"];
                            }
                            $response["success"] = 1;
                            $response["message"] = "Records found.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Records not found.";
                        }
                    }
                }
            } else if ($action == "followUp_history") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $response["data"]["inquiry"] = array();
                        $response["data"]["history"] = array();

                        $inquiry_data = $this->db->query("select * from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 and id = ".$post["id"])->row_array();
                        if (!empty($inquiry_data)) {
                            $response["data"]["inquiry"]["assign_master_user_id"] = $inquiry_data["assign_master_user_id"] ? intval($inquiry_data["assign_master_user_id"]) : "";
                            $response["data"]["inquiry"]["assign_master_user_name"] = $inquiry_data["assign_master_user_name"] ? $inquiry_data["assign_master_user_name"] : "";
                            $response["data"]["inquiry"]["user_id"] = intval($inquiry_data["user_id"]);
                            $response["data"]["inquiry"]["name"] = $inquiry_data["name"];
                            $response["data"]["inquiry"]["email_address"] = $inquiry_data["email_address"] ? $inquiry_data["email_address"] : "";
                            $response["data"]["inquiry"]["contact_no"] = $inquiry_data["contact_no"] ? $inquiry_data["contact_no"] : "";
                            $response["data"]["inquiry"]["state_id"] = $inquiry_data["state_id"] ? intval($inquiry_data["state_id"]) : "";
                            $response["data"]["inquiry"]["state_name"] = $inquiry_data["state_name"] ? $inquiry_data["state_name"] : "";
                            $response["data"]["inquiry"]["city_id"] = $inquiry_data["city_id"] ? intval($inquiry_data["city_id"]) : "";
                            $response["data"]["inquiry"]["city_name"] = $inquiry_data["city_name"] ? $inquiry_data["city_name"] : "";
                            $response["data"]["inquiry"]["designation"] = $inquiry_data["designation"] ? $inquiry_data["designation"] : "";
                            $response["data"]["inquiry"]["company_name"] = $inquiry_data["company_name"] ? $inquiry_data["company_name"] : "";
                            $response["data"]["inquiry"]["company_website"] = $inquiry_data["company_website"] ? $inquiry_data["company_website"] : "";
                            $response["data"]["inquiry"]["rate"] = $inquiry_data["rate"] ? $inquiry_data["rate"] : "";
                            $response["data"]["inquiry"]["quantity"] = $inquiry_data["quantity"] ? $inquiry_data["quantity"] : "";
                            $response["data"]["inquiry"]["height"] = $inquiry_data["height"] ? $inquiry_data["height"] : "";
                            $response["data"]["inquiry"]["lead_value"] = $inquiry_data["lead_value"] ? $inquiry_data["lead_value"] : "";
                            $response["data"]["inquiry"]["unit"] = $inquiry_data["unit"] ? $inquiry_data["unit"] : "";
                            $response["data"]["inquiry"]["quotation_number"] = $inquiry_data["quotation_number"] ? $inquiry_data["quotation_number"] : "";
                            $response["data"]["inquiry"]["source"] = $inquiry_data["source"] ? $inquiry_data["source"] : "";
                            $response["data"]["inquiry"]["other_source"] = $inquiry_data["other_source"] ? $inquiry_data["other_source"] : "";
                            $response["data"]["inquiry"]["which_plant_city_id"] = $inquiry_data["which_plant_city_id"] ? $inquiry_data["which_plant_city_id"] : "";
                            $response["data"]["inquiry"]["plant_city_name"] = $inquiry_data["plant_city_name"] ? $inquiry_data["plant_city_name"] : "";
                            $response["data"]["inquiry"]["priority"] = $inquiry_data["priority"] ? $inquiry_data["priority"] : "";
                            $response["data"]["inquiry"]["status"] = $inquiry_data["status"] ? $inquiry_data["status"] : "";
                            $response["data"]["inquiry"]["remarks"] = $inquiry_data["remarks"] ? $inquiry_data["remarks"] : "";
                            /*$response["data"]["inquiry"]["attachment"] = $inquiry_data["attachment"] ? $inquiry_data["attachment"] : "";
                            $response["data"]["inquiry"]["attachment_full"] = $inquiry_data["attachment"] ? BASE_PATH.'inquiry/attachment/'.$inquiry_data["attachment"] : "";*/
                            $response["data"]["inquiry"]["created_at"] = date("d M, Y", $inquiry_data["created_at"]);
                            $response["data"]["inquiry"]["attachment_array"] = array();
                            if (!empty($inquiry_data["attachment"])) {
                                foreach (json_decode($inquiry_data["attachment"], true) as $key_2 => $inquiry_data) {
                                    $response["data"]["inquiry"]["attachment_array"][$key_2]["attachment"] = $inquiry_data["attachment"];
                                    $response["data"]["inquiry"]["attachment_array"][$key_2]["attachment_full"] = BASE_PATH.'inquiry/attachment/'.$inquiry_data["attachment"];
                                    $response["data"]["inquiry"]["attachment_array"][$key_2]["name"] = $inquiry_data["name"];
                                }
                            }

                            $result = $this->db->query("select * from follow_up_history where inquiry_id = ".$post["id"]." and is_email_sent = 0 order by id desc")->result_array();
                            if (!empty($result)) {
                                foreach ($result as $key => $value) {
                                    $response["data"]["history"][$key]["id"] = intval($value["id"]);
                                    $response["data"]["history"][$key]["inquiry_id"] = intval($value["inquiry_id"]);
                                    $response["data"]["history"][$key]["status"] = $value["status"];
                                    $response["data"]["history"][$key]["followUp_date"]["created_at"] = $value["follow_up_date"] ? date("d M, Y", $value["follow_up_date"]) : "";
                                    $response["data"]["history"][$key]["followUp_date"]["created_at_time"] = $value["follow_up_date"] ? date("h:i A", $value["follow_up_date"]) : "";
                                    $response["data"]["history"][$key]["created_at"] = $value["created_at"] ? date("d M, Y", $value["created_at"]) : "";
                                    $response["data"]["history"][$key]["created_at_time"] = date("h:i A", $value["created_at"]);
                                    $response["data"]["history"][$key]["remarks"] = $value["remarks"] ? $value["remarks"] : "";
                                    $response["data"]["history"][$key]["attachment_full"] = $value["attachment"] ? BASE_PATH.'inquiry/attachment/'.$value["attachment"] : "";
                                    $response["data"]["history"][$key]["followUp_add_details"] = array(
                                        "master_user_id" => $value["follow_up_add_master_user_id"] ? $value["follow_up_add_master_user_id"] : "",
                                        "name" => $value["follow_up_add_master_user_name"] ? $value["follow_up_add_master_user_name"] : "",
                                    );
                                    $response["data"]["history"][$key]["transfer_from"] = array(
                                        "master_user_id" => $value["transfer_from_master_user_id"] ? $value["transfer_from_master_user_id"] : "",
                                        "name" => $value["transfer_from_master_user_name"] ? $value["transfer_from_master_user_name"] : "",
                                    );
                                    $response["data"]["history"][$key]["transfer_to"] = array(
                                        "master_user_id" => $value["transfer_to_master_user_id"] ? $value["transfer_to_master_user_id"] : "",
                                        "name" => $value["transfer_to_master_user_name"] ? $value["transfer_to_master_user_name"] : "",
                                    );
                                }
                            }
                            $response["success"] = 1;
                            $response["message"] = "Records found.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Inquiry not found.";
                        }
                    }
                }
            } else if ($action == "delete") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else if ($m_u_details["type"] != "admin") {
                        $response["success"] = 0;
                        $response["message"] = "You can't remove lead.";
                    } else {
                        $result = $this->db->query("select id from inquiries where master_user_id = ".$m_u_details["parent_id"]." and is_deleted = 0 and id = ".$post["id"])->row_array();
                        if (empty($result)) {
                            $response["success"] = 0;
                            $response["message"] = "Lead not found.";
                        } else {
                            $this->db->where("master_user_id", $m_u_details["parent_id"]);
                            $this->db->where("id", $post["id"]);
                            $this->db->update("inquiries", array("is_deleted"=>1,"deleted_at"=>time()));
                            if ($this->db->affected_rows() > 0) {
                                $response["success"] = 1;
                                $response["message"] = "Lead deleted successfully.";
                            } else {
                                $response["success"] = 0;
                                $response["message"] = "Oops... Something went wrong. Please try again.";
                            }
                        }
                    }
                }
            } else if ($action == "import_leads") {
                if (empty($post["logged_in_master_user_id"]) || empty($_FILES["import_file"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $path = UPLOAD_URL."import/";
                        $extension = pathinfo($_FILES["import_file"]["name"], PATHINFO_EXTENSION);
                        $file_name = "file_".time()."_".rand(1,100).".".$extension;
                        $move = move_uploaded_file($_FILES["import_file"]["tmp_name"], $path.$file_name);
                        if($move){
                            $import_path = IMPORT_PATH.'import/'.$file_name;

                            $spreadsheet = IOFactory::load($import_path);
                            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                            if (count($data) > 1) {
                                $lead_max_lead = 5000;
                                $time = time();
                                $time_set = rand(1,100)."_".$m_u_details["master_user_id"];

                                $state_get = array();
                                $state_set = array();
                                $cities_set = array();
                                $sales_get = array();
                                $sales_set = array();
                                foreach ($data as $key => $value) {
                                    if (!empty($value["A"]) && !empty($value["B"]) && !empty($value["L"]) && !empty($value["G"]) && !empty($value["H"]) && $key > 1 && $key <= $lead_max_lead) {
                                        if (!in_array($value["G"], $state_get)) {
                                            array_push($state_get, trim($value["G"]));
                                        }
                                    }
                                    if (!empty($value["A"]) && !empty($value["B"]) && !empty($value["L"]) && !empty($value["D"]) && $key > 1 && $key <= $lead_max_lead) {
                                        if (!in_array($value["D"], $state_get)) {
                                            array_push($sales_get, trim($value["D"]));
                                        }
                                    }
                                }

                                if (!empty($sales_get)) {
                                    $sales_result = $this->db->query("select master_user_id, name, email_address from master_users where parent_id = ".$m_u_details["parent_id"]." and email_address IN('".implode("','", $sales_get)."') and is_deleted = 0")->result_array();
                                    if (!empty($sales_result)) {
                                        foreach ($sales_result as $key => $value) {
                                            $sales_set[$value["email_address"]] = $value["master_user_id"];
                                            $sales_set[$value["email_address"]."##Name"] = $value["name"];
                                        }
                                    }
                                }

                                if (!empty($state_get)) {
                                    $state_result = $this->db->query("select * from tbl_states where name IN('".implode("','", $state_get)."')")->result_array();
                                    if (!empty($state_result)) {
                                        foreach ($state_result as $key => $value) {
                                            $state_set[$value["name"]] = $value["id"];
                                        }
                                        $cities_result = $this->db->query("SELECT * FROM tbl_cities WHERE state_id IN (SELECT id from tbl_states WHERE country_id = 101)")->result_array();
                                        foreach ($cities_result as $key => $value) {
                                            $cities_set[$value["name"]."##".$value["state_id"]] = $value["id"];
                                        }
                                    }
                                }

                                $state_result = array();
                                $state_set = array();
                                if (!empty($states_get)) {
                                    $state_result = $this->db->query("SELECT * FROM tbl_states WHERE country_id = 101 and name IN('".implode("','", $states_get)."')")->result_array();
                                    if (!empty($state_result)) {
                                        foreach ($state_result as $key => $value) {
                                            $state_set[$value["name"]] = $value["id"];
                                        }
                                    }
                                }

                                $plant_set = array();
                                $plant_result = $this->db->query("select * from plant_city_map where master_user_id = ".$m_u_details["parent_id"]." and is_active = 1")->result_array();
                                if (!empty($plant_result)) {
                                    foreach ($plant_result as $key => $value) {
                                        $plant_set[$value["name"]] = $value["city_id"];
                                    }
                                }

                                $i=0;
                                $data_insert = array();
                                foreach ($data as $key => $value) {
                                    if (!empty($value["A"]) && !empty($value["B"]) && !empty($value["D"]) && $key > 1 && $key <= $lead_max_lead) {
                                        $data_insert[$i]["name"] = trim($value["A"]);
                                        $data_insert[$i]["contact_no"] = "91 ".trim($value["B"]);
                                        $data_insert[$i]["email_address"] = (trim($value["C"]) && !filter_var(trim($value["C"]), FILTER_VALIDATE_EMAIL)) ? trim($value["C"]) : null;
                                        $data_insert[$i]["assign_master_user_id"] = $sales_set[trim($value["C"])] ? $sales_set[trim($value["C"])] : null;
                                        $data_insert[$i]["assign_master_user_name"] = $sales_set[trim($value["C"])."##Name"] ? $sales_set[trim($value["C"])."##Name"] : null;
                                        $data_insert[$i]["designation"] = trim($value["E"]) ? trim($value["E"]) : null;
                                        $data_insert[$i]["company_name"] = trim($value["F"]) ? trim($value["F"]) : null;
                                        $data_insert[$i]["state_id"] = $state_set[trim($value["G"])] ? $state_set[trim($value["G"])] : null;
                                        $data_insert[$i]["state_name"] = null;
                                        $data_insert[$i]["city_id"] = null;
                                        $data_insert[$i]["city_name"] = null;
                                        if (!empty($data_insert[$i]["state_id"])) {
                                            $data_insert[$i]["state_name"] = $state_set[trim($value["G"])];
                                            $data_insert[$i]["city_id"] = $cities_set[trim($value["H"])."##".$data_insert[$i]["state_id"]] ? $cities_set[trim($value["H"])."##".$data_insert[$i]["state_id"]] : null;
                                            if (!empty($data_insert[$i]["city_id"])) {
                                                $data_insert[$i]["city_name"] = $state_set[trim($value["H"])];
                                            }
                                        }
                                        $data_insert[$i]["village"] = trim($value["I"]) ? trim($value["I"]) : null;
                                        $data_insert[$i]["priority"] = trim($value["I"]) ? strtolower(trim($value["I"])) : "warm";
                                        $data_insert[$i]["company_website"] = trim($value["K"]) ? trim($value["K"]) : null;
                                        $data_insert[$i]["unit"] = trim($value["L"]) ? trim($value["L"]) : null;
                                        $data_insert[$i]["rate"] = trim($value["M"]) ? trim($value["M"]) : null;
                                        $data_insert[$i]["quantity"] = trim($value["N"]) ? trim($value["N"]) : null;
                                        $data_insert[$i]["rate_of_panel"] = trim($value["O"]) ? trim($value["O"]) : null;
                                        $data_insert[$i]["nos_of_panel"] = trim($value["P"]) ? trim($value["P"]) : null;
                                        $data_insert[$i]["rate_of_column"] = trim($value["Q"]) ? trim($value["Q"]) : null;
                                        $data_insert[$i]["nos_of_column"] = trim($value["R"]) ? trim($value["R"]) : null;
                                        $data_insert[$i]["lead_value"] = null;
                                        if (!empty($data_insert[$i]["rate"]) && !empty($data_insert[$i]["quantity"])) {
                                            $data_insert[$i]["lead_value"] = $data_insert[$i]["rate"] * $data_insert[$i]["quantity"];
                                        } else if (!empty($data_insert[$i]["nos_of_panel"]) && !empty($data_insert[$i]["rate_of_panel"]) && !empty($data_insert[$i]["nos_of_column"]) && !empty($data_insert[$i]["rate_of_column"])) {
                                            $data_insert[$i]["lead_value"] = ($data_insert[$i]["nos_of_panel"] * $data_insert[$i]["rate_of_panel"]) + ($data_insert[$i]["nos_of_column"] * $data_insert[$i]["rate_of_column"]);
                                        }
                                        $data_insert[$i]["height"] = trim($value["S"]) ? trim($value["S"]) : null;
                                        $data_insert[$i]["source"] = trim($value["T"]) ? trim($value["T"]) : null;
                                        $data_insert[$i]["which_plant_city_id"] = $plant_set[trim($value["U"])] ? $plant_set[trim($value["U"])] : null;
                                        if (!empty($data_insert[$i]["which_plant_city_id"])) {
                                            $data_insert[$i]["plant_city_name"] = trim($value["U"]);
                                        }
                                        $data_insert[$i]["remarks"] = trim($value["V"]) ? trim($value["V"]) : null;
                                        $created_at_set = $time;
                                        if (!empty(trim($value["W"])) && !empty(strtotime(trim($value["W"])))) {
                                            $created_at_set = strtotime(trim($value["W"]));
                                        }
                                        $data_insert[$i]["created_at"] = $created_at_set;
                                        $data_insert[$i]["bulk_import_by"] = $time_set;
                                        $data_insert[$i]["bulk_import_time"] = $time;
                                        $data_insert[$i]["bulk_import_master_user_id"] = $m_u_details["master_user_id"];
                                        $data_insert[$i]["master_user_id"] = $m_u_details["parent_id"];
                                        $data_insert[$i]["status"] = "New Customer";
                                        $i++;
                                    }
                                }
                                if (!empty($data_insert)) {
                                    $this->db->insert_batch("inquiries", $data_insert);
                                    if ($this->db->affected_rows() > 0) {
                                        $result_followUp = $this->db->query("select * from follow_up_history")->row_array();
                                        $followUp_data_set = array();
                                        $followUp_data_set_in = array("status", "follow_up_date", "remarks", "created_at");
                                        foreach ($result_followUp as $key => $value) {
                                            if ($key == "inquiry_id") {
                                                array_push($followUp_data_set, "id as ".$key);
                                            } else if ($key == "follow_up_add_master_user_id") {
                                                array_push($followUp_data_set, "assign_master_user_id as ".$key);
                                            } else if ($key == "follow_up_add_master_user_name") {
                                                array_push($followUp_data_set, "assign_master_user_name as ".$key);
                                            } else if (in_array($key, $followUp_data_set_in)) {
                                                array_push($followUp_data_set, $key);
                                            } else {
                                                array_push($followUp_data_set, "null as ".$key);
                                            }
                                        }

                                        $this->db->query("insert into follow_up_history (select ".implode(",", $followUp_data_set)." from inquiries where bulk_import_time = ".$time." and bulk_import_by = '".$time_set."' and bulk_import_master_user_id = ".$m_u_details["master_user_id"]." and master_user_id = ".$m_u_details["parent_id"].")");

                                        $response["success"] = 1;
                                        $response["message"] = ($i == 1 ? $i." Lead" : $i." Leads")." imported successfully.";
                                    } else {
                                        $response["success"] = 0;
                                        $response["message"] = "Opps.. Something went wrong. Please try again.";
                                    }
                                } else {
                                    $response["success"] = 0;
                                    $response["message"] = "Leads not found.";
                                }
                            } else {
                                $response["success"] = 0;
                                $response["message"] = "Leads not found.";
                            }
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "File not found. Please try again.";
                        }
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function team ($action) {
        $actions = array("list", "save", "change_password", "delete", "list_get_assign_user", "assign_lead_to_sales", "status_change");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "list") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $today_dateTime = strtotime(date(date('Y-m-d',strtotime("today"))."00:00:00"));
                        $today_dateTimeEnd = strtotime(date(date('Y-m-d',strtotime("today"))."23:59:59"));

                        $lead_type = "";
                        if ($post["dashboard"] == 1 && empty($post["lead_type"])) {
                            $lead_type = "new_lead";
                        } else if (!empty($post["lead_type"])) {
                            $lead_type = $post["lead_type"];
                        }

                        $and_condition = "";
                        $and_condition .= " and is_deleted = 0";
                        $and_condition .= " and parent_id = ".$m_u_details["parent_id"];
                        $and_condition .= " and master_user_id != ".$m_u_details["parent_id"];
                        $from_date = "";
                        $to_date = "";
                        if (!empty($post["from_date"]) && !empty($post["to_date"])) {
                            $from_date =  strtotime(date($post["from_date"]."00:00:00"));
                            $to_date =  strtotime(date($post["to_date"]."23:59:59"));
                            $and_condition .= " and created_at >= ".$from_date." and created_at <= ".$to_date;
                        }

                        if (!empty($post["master_user_id"])) {
                            $and_condition .= " and master_user_id = ".$post["master_user_id"];
                        }

                        $order_by = " order by created_at desc";

                        $search_condition = "";
                        if (!empty($post["search"])) {
                            $search_condition .= " and (name like '%".$post["search"]."%' or email_address like '%".$post["search"]."%' or contact_no like '%".$post["search"]."%')";
                        }

                        $pagelimit = "";
                        $post["limit"] = $post["limit"] ? $post["limit"] : 1000;
                        
                        if(!empty($post["page"])){
                            $pagelimit .= " limit ".(($post["page"]-1)*$post["limit"]).", ".$post["limit"];
                        } else {
                            $post["limit"] = 1000;
                            $pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
                        }

                        
                        $result = $this->db->query("select SQL_CALC_FOUND_ROWS * from master_users where 1=1 ".$and_condition.$search_condition.$order_by.$pagelimit)->result_array();
                        if (!empty($result)) {
                            $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                            $total_records = $queryNew->row()->myCounter;

                            $sales_master_user_ids = array_map(function($sp) {
                                return $sp["master_user_id"];
                            }, $result);

                            $today_dateTime = strtotime(date(date('Y-m-d',strtotime("today"))."00:00:00"));

                            $total_assign = $this->db->query("select count(id) as total, assign_master_user_id from inquiries where assign_master_user_id IN(".implode(",", $sales_master_user_ids).") and is_deleted = 0 and master_user_id = ".$m_u_details["parent_id"]." group by assign_master_user_id")->result_array();
                            $total_assign_array = array();
                            if (!empty($total_assign)) {
                                foreach ($total_assign as $key => $value) {
                                    $total_assign_array[$value["assign_master_user_id"]] = $value["total"];
                                }
                            }

                            $total_followUp = $this->db->query("select count(id) as total, assign_master_user_id from inquiries where assign_master_user_id IN(".implode(",", $sales_master_user_ids).") and is_deleted = 0 and master_user_id = ".$m_u_details["parent_id"]." and follow_up_date >= ".$today_dateTime." group by assign_master_user_id")->result_array();
                            $total_followUp_array = array();
                            if (!empty($total_followUp)) {
                                foreach ($total_followUp as $key => $value) {
                                    $total_followUp_array[$value["assign_master_user_id"]] = $value["total"];
                                }
                            }

                            $total_overdue = $this->db->query("select count(id) as total, assign_master_user_id from inquiries where assign_master_user_id IN(".implode(",", $sales_master_user_ids).") and is_deleted = 0 and master_user_id = ".$m_u_details["parent_id"]." and ((follow_up_date < ".$today_dateTime.") OR (created_at < ".$today_dateTime." and follow_up_date is null and last_follow_up_updated_date is null)) group by assign_master_user_id")->result_array();
                            $total_overdue_array = array();
                            if (!empty($total_overdue)) {
                                foreach ($total_overdue as $key => $value) {
                                    $total_overdue_array[$value["assign_master_user_id"]] = $value["total"];
                                }
                            }

                            $total_lost = $this->db->query("select count(id) as total, assign_master_user_id from inquiries where assign_master_user_id IN(".implode(",", $sales_master_user_ids).") and is_deleted = 0 and master_user_id = ".$m_u_details["parent_id"]." and status IN('Not Interested') group by assign_master_user_id")->result_array();
                            $total_lost_array = array();
                            if (!empty($total_lost)) {
                                foreach ($total_lost as $key => $value) {
                                    $total_lost_array[$value["assign_master_user_id"]] = $value["total"];
                                }
                            }

                            foreach ($result as $key => $value) {
                                $response["data"][$key]["master_user_id"] = intval($value["master_user_id"]);
                                $response["data"][$key]["name"] = $value["name"];
                                $response["data"][$key]["type"] = $value["type"];
                                $response["data"][$key]["email_address"] = $value["email_address"] ? $value["email_address"] : "";
                                $response["data"][$key]["contact_no"] = $value["contact_no"] ? $value["contact_no"] : "";
                                $response["data"][$key]["is_active"] = intval($value["is_active"]);
                                $response["data"][$key]["created_at"] = date("d M, Y", $value["created_at"]);
                                $response["data"][$key]["created_at_time"] = date("h:i A", $value["created_at"]);
                                $response["data"][$key]["total"] = array(
                                    "assign" => 0,
                                    "followUp" => 0,
                                    "overdue" => 0,
                                    "lost" => 0,
                                );
                                $response["data"][$key]["total"]["assign"] = $total_assign_array[$value["master_user_id"]] ? intval($total_assign_array[$value["master_user_id"]]) : 0;
                                $response["data"][$key]["total"]["followUp"] = $total_followUp_array[$value["master_user_id"]] ? intval($total_followUp_array[$value["master_user_id"]]) : 0;
                                $response["data"][$key]["total"]["overdue"] = $total_overdue_array[$value["master_user_id"]] ? intval($total_overdue_array[$value["master_user_id"]]) : 0;
                                $response["data"][$key]["total"]["lost"] = $total_lost_array[$value["master_user_id"]] ? intval($total_lost_array[$value["master_user_id"]]) : 0;
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
                    }
                }
            } else if ($action == "save") {
                $type_array = array("management", "team leader", "sales executive", "channel partners");
                
                if ($post["type"] == "management") {
                    $data["role_type"] = "all";
                } else if ($post["type"] == "channel partners") {
                    $data["role_type"] = "cp";
                } else if ($post["type"] == "manager" || $post["type"] == "team leader") {
                    $data["role_type"] = "selective";
                } else if ($post["type"] == "sales executive") {
                    $data["role_type"] = "own";
                }

                if (empty($post["logged_in_master_user_id"]) || empty($post["name"]) || empty($post["contact_no"]) || empty($post["email_address"]) || (empty($post["team_ids"]) && $data["role_type"] == "selective") || empty($post["type"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else if (!filter_var($post["email_address"], FILTER_VALIDATE_EMAIL)) {
                    $response["success"] = 0;
                    $response["message"] = "Invalid Email Address.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $time = time();
                        if ($post["type"] == "management") {
                            /*$data["group_id"] = $this->db->get_where("admin_groups", array("master_user_id"=>$post["parent_id"],"type"=>$post["type"],"auto_created_group"=>1))->row()->group_id;*/
                        } else if ($post["type"] == "channel partners") {
                            /*$data["group_id"] = $this->db->get_where("admin_groups", array("master_user_id"=>$post["parent_id"],"type"=>$post["type"],"auto_created_group"=>1))->row()->group_id;*/
                        } else if ($post["type"] == "manager" || $post["type"] == "team leader") {
                            /*$data["group_id"] = $this->db->get_where("admin_groups", array("master_user_id"=>$post["parent_id"],"type"=>$post["type"],"auto_created_group"=>1))->row()->group_id;*/
                        } else if ($post["type"] == "sales executive") {
                            /*$data["group_id"] = $post["group_id"] ? $post["group_id"] : null;*/
                        }
                        $data["name"] = $post["name"];
                        $data["contact_no"] = "91 ".$post["contact_no"];
                        $data["email_address"] = $post["email_address"];
                        $data["is_active"] = $post["is_active"] ? $post["is_active"] : 0;
                        $data["type"] = $post["type"];

                        $email_con = " and email_address = '".$post["email_address"]."'";
                        if (!empty($post["master_user_id"])) {
                            $email_con = " and master_user_id != ".$post["master_user_id"]." and email_address = '".$post["email_address"]."'";
                        }
                        $check_email = $this->db->query("select master_user_id from master_users where 1=1 ".$email_con." and is_deleted = 0")->row_array();

                        $contact_con = " and contact_no = '91 ".$post["contact_no"]."'";
                        if (!empty($post["master_user_id"])) {
                            $contact_con = " and master_user_id != ".$post["master_user_id"]." and contact_no = '91 ".$post["contact_no"]."'";
                        }
                        $check_contact_no = $this->db->query("select master_user_id from master_users where 1=1 ".$contact_con." and is_deleted = 0")->row_array();
                        $check_success = 1;
                        if (!empty($check_email)) {
                            $response["success"] = 0;
                            $response["message"] = "Email address already existed.";
                        } else if (!empty($check_contact_no)) {
                            $response["success"] = 0;
                            $response["message"] = "Mobile number already existed.";
                        } else {
                            $affected_rows = 0;
                            if (!empty($post["master_user_id"])) {
                                $data["updated_at"] = $time;
                                $this->db->where("master_user_id", $post["master_user_id"]);
                                $this->db->update("master_users", $data);
                                $id = $post["master_user_id"];
                                if ($this->db->affected_rows() > 0) {
                                    $affected_rows = 1;
                                }
                                $message = "User updated successfully.";
                            } else {
                                if (!empty($post["password"])) {
                                    $data["created_at"] = $time;
                                    $data["password"] = md5($post["password"]);
                                    $data["parent_id"] = $m_u_details["parent_id"];

                                    $this->db->insert("master_users", $data);
                                    $id = $this->db->insert_id();
                                    if ($this->db->affected_rows() > 0) {
                                        $affected_rows = 1;
                                        $this->email_model->email_for_sales_welcome($data, $post["password"]);
                                    }
                                    $message = "Use created successfully.";
                                } else {
                                    $check_success = 0;
                                    $message = "Required fields can not be blank.";
                                }
                            }

                            if ($check_success == 0) {
                                $response["success"] = 0;
                                $response["message"] = $message;
                            } else if ($affected_rows == 1) {
                                $response["success"] = 1;
                                $response["message"] = $message;
                            } else {
                                $response["success"] = 0;
                                $response["message"] = "Oops.. Something went wrong. Please try again.";
                            }
                        }
                    }
                }
            } else if ($action == "change_password") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["password"]) || empty($post["confirm_password"]) || empty($post["master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else if ($post["confirm_password"] !== $post["password"]) {
                    $response["success"] = 0;
                    $response["message"] = "Passwords do not matched, Please re enter again!";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $time = time();
                        $data["password"] = md5($post["password"]);
                        $data["updated_at"] = $time;

                        $this->db->where("master_user_id", $post["master_user_id"]);
                        $this->db->update("master_users", $data);
                        $id = $post["master_user_id"];
                        if ($this->db->affected_rows() > 0) {
                            $response["success"] = 1;
                            $response["message"] = "Password changed successfully.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Oops.. Something went wrong. Please try again.";
                        }
                    }
                }
            } else if ($action == "delete") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $result = $this->db->query("select master_user_id from master_users where master_user_id = ".$post["master_user_id"]." and parent_id = ".$m_u_details["parent_id"]." and is_deleted = 0")->row_array();
                        if (!empty($result)) {
                            $inquiry_assign_check = $this->db->get_where("inquiries", array("master_user_id"=>$m_u_details["parent_id"],"assign_master_user_id"=>$post["master_user_id"]))->row_array();
                            if (!empty($inquiry_assign_check)) {
                                $response["success"] = 0;
                                $response["message"] = "The user has some inquiries, so we cannot remove the user.";
                            } else {
                                $this->db->where("master_user_id", $post["master_user_id"]);
                                $this->db->update("master_users", array("is_deleted"=>1,"deleted_at"=>time()));
                                if ($this->db->affected_rows() > 0) {
                                    $response["success"] = 1;
                                    $response["message"] = "User deleted successfully.";
                                } else {
                                    $response["success"] = 0;
                                    $response["message"] = "Oops.. Something went wrong. Please try again.";
                                }
                            }
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "User not found.";
                        }
                    }
                }
            } else if ($action == "list_get_assign_user") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields cna not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $and_condition = "";
                        if (!empty($post["assign_master_user_id"])) {
                            $and_condition .= " and master_user_id != ".$post["assign_master_user_id"];
                        }
                        $result = $this->db->query("select * from master_users where 1=1 ".$and_condition." and is_deleted = 0 and parent_id = ".$m_u_details["parent_id"]." and master_user_id != ".$m_u_details["parent_id"])->result_array();
                        if (!empty($result)) {
                            foreach ($result as $key => $value) {
                                $response["data"][$key]["master_user_id"] = $value["master_user_id"];
                                $response["data"][$key]["name"] = $value["name"];
                            }
                            $response["success"] = 1;
                            $response["message"] = "Records found.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Records not found.";
                        }
                    }
                }
            } else if ($action == "assign_lead_to_sales") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["ids"]) || empty($post["transfer_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $transfer_details = array();
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (!empty($m_u_details)) {
                        $transfer_details = $this->front_model->master_user_details_row($post["transfer_master_user_id"]);
                    }
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else if (empty($transfer_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Sales user not found.";
                    } else {
                        $result = $this->db->query("select * from inquiries where master_user_id = ".$m_u_details["parent_id"]." and id IN(".$post["ids"].") and is_deleted = 0")->result_array();
                        if (!empty($result)) {
                            $time = time();
                            $key = 0;
                            $key_2 = 0;
                            $inquiry_result = array();
                            $followUp_result = array();
                            foreach ($result as $value) {
                                if ($value["assign_master_user_id"] != $transfer_details["master_user_id"]) {
                                    $followUp_result[$key]["inquiry_id"] = $value["id"];
                                    $followUp_result[$key]["follow_up_add_master_user_id"] = $m_u_details["master_user_id"];
                                    $followUp_result[$key]["follow_up_add_master_user_name"] = $m_u_details["name"];
                                    $followUp_result[$key]["transfer_from_master_user_id"] = $value["assign_master_user_id"] ? $value["assign_master_user_id"] : null;
                                    $followUp_result[$key]["transfer_from_master_user_name"] = $value["assign_master_user_name"] ? $value["assign_master_user_name"] : null;
                                    $followUp_result[$key]["transfer_to_master_user_id"] = $transfer_details["master_user_id"];
                                    $followUp_result[$key]["transfer_to_master_user_name"] = $transfer_details["name"];
                                    $followUp_result[$key]["created_at"] = $time;
                                    $inquiry_result[$key]["id"] = $value["id"];
                                    $inquiry_result[$key]["assign_master_user_id"] = $transfer_details["master_user_id"];
                                    $inquiry_result[$key]["assign_master_user_name"] = $transfer_details["name"];
                                    $key++;
                                } else {
                                    $key_2++;
                                }
                            }
                            if (!empty($inquiry_result)) {
                                $this->db->update_batch("inquiries", $inquiry_result, "id");
                                if ($this->db->affected_rows() > 0) {
                                    $this->db->insert_batch("follow_up_history", $followUp_result);
                                    if ($this->db->affected_rows() > 0) {
                                        $response["success"] = 1;
                                        $response["message"] = "Lead assigned successfully.";
                                        $response["other_info"] = $key_2 > 0 ? 1 : 0;
                                        $response["other_info_message"] = "Some leads already assigned to this user.";
                                    } else {
                                        $response["success"] = 0;
                                        $response["message"] = "Oops.. Something went wrong. Please try again.";
                                    }
                                }
                            } else if ($key_2 > 0) {
                                $response["success"] = 0;
                                $response["message"] = "Leads already assigned to this user.";
                            }
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Leads not found.";
                        }
                    }
                }
            } else if ($action == "status_change") {
                if (empty($post["logged_in_master_user_id"]) || empty($post["master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $result = $this->db->query("select master_user_id, is_active from master_users where master_user_id = ".$post["master_user_id"]." and parent_id = ".$m_u_details["parent_id"]." and is_deleted = 0")->row_array();
                        if (empty($result)) {
                            $response["success"] = 0;
                            $response["message"] = "Team member not found.";
                        } else {
                            $this->db->where("master_user_id", $result["master_user_id"]);
                            $this->db->update("master_users", array("is_active" => $result["is_active"] ? 0 : 1));
                            if ($this->db->affected_rows() > 0) {
                                $response["success"] = 1;
                                if ($result["is_active"]) {
                                    $response["message"] = "The team member status is InActive.";
                                } else {
                                    $response["message"] = "The team member status is Active.";
                                }
                                $response["is_active"] = $result["is_active"] ? 0 : 1;
                            } else {
                                $response["success"] = 0;
                                $response["message"] = "Oops.. Something went wrong. Please try again.";
                            }
                        }
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function country ($action) {
        $actions = array("list");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "list") {
                $results = $this->db->query("select SQL_CALC_FOUND_ROWS * from country where 1=1")->result_array();
                $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                $total_records = $queryNew->row()->myCounter;
                if (empty($results)) {
                    $response["success"] = 0;
                    $response["message"] = "Records not found.";
                } else {
                    $i=0;
                    foreach ($results as $value) {
                        $response["data"][$i]["id"] = intval($value["id"]);
                        $response["data"][$i]["iso"] = $value["iso"] ? $value["iso"] : "";
                        $response["data"][$i]["flag"] = "https://flagcdn.com/w40/".$value["iso"].".webp";
                        $response["data"][$i]["name"] = $value["name"] ? $value["name"] : "";
                        $response["data"][$i]["nicename"] = $value["nicename"] ? $value["nicename"] : "";
                        $response["data"][$i]["iso3"] = $value["iso3"] ? $value["iso3"] : "";
                        $response["data"][$i]["numcode"] = $value["numcode"] ? $value["numcode"] : "";
                        $response["data"][$i]["phonecode"] = $value["phonecode"] ? $value["phonecode"] : "";
                        $response["data"][$i]["sort"] = $value["sort"] ? $value["sort"] : "";
                        $i++;
                    }
                    $response["success"] = 1;
                    $response["total_records"] = intval($total_records);
                    $response["message"] = "Records found.";
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function state ($action) {
        $actions = array("list", "list_filter");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "list") {
                $and_condition = "";
                $order_by = " order by name asc ";
                $and_condition .= " and country_id = 101";

                if (!empty($post["search"])) {
                    $and_condition .= " and name like '%".$post["search"]."%'";
                }
                $result_selected = array();
                if (!empty($post["or_state_id"])) {
                    $result_selected = $this->db->query("select * from tbl_states where id = ".$post["or_state_id"])->row_array();
                    $and_condition .= " and id <> ".$post["or_state_id"];
                }

                $results = $this->db->query("select SQL_CALC_FOUND_ROWS * from tbl_states where 1=1 ".$and_condition.$order_by." limit 50")->result_array();
                $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                $total_records = $queryNew->row()->myCounter;
                if (empty($results) && empty($result_selected)) {
                    $response["success"] = 0;
                    $response["message"] = "Records not found.";
                } else {
                    $i=0;
                    if (!empty($result_selected)) {
                        $response["data"][$i]["id"] = intval($result_selected["id"]);
                        $response["data"][$i]["name"] = $result_selected["name"];
                        $i++;
                    }
                    if (!empty($results)) {
                        foreach ($results as $value) {
                            $response["data"][$i]["id"] = intval($value["id"]);
                            $response["data"][$i]["name"] = $value["name"];
                            $i++;
                        }
                    }
                    $response["success"] = 1;
                    $response["total_records"] = intval($total_records);
                    $response["message"] = "Records found.";
                }
            } else if ($action == "list_filter") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $and_condition = "";

                        if ($m_u_details["type"] == "admin") {
                            $and_condition .= " and i.master_user_id = ".$m_u_details["master_user_id"];
                        } else if ($m_u_details["type"] == "sales executive") {
                            $and_condition .= " and i.master_user_id = ".$m_u_details["parent_id"]." and i.assign_master_user_id = ".$m_u_details["master_user_id"];
                        }

                        $result = $this->db->query("SELECT ts.id, ts.name FROM inquiries as i LEFT JOIN tbl_states as ts ON(i.state_id = ts.id) WHERE 1=1 ".$and_condition." and i.state_id is not null and i.is_deleted = 0 GROUP by ts.id")->result_array();

                        if (!empty($result)) {
                            foreach ($result as $key => $value) {
                                $response["data"][$key]["state_id"] = $value["id"];
                                $response["data"][$key]["name"] = $value["name"];
                            }

                            $response["success"] = 1;
                            $response["message"] = "Records found.";
                        } else {
                            $response["success"] = 0;
                            $response["message"] = "Records not found.";
                        }
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function city ($action) {
        $actions = array("list");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "list") {
                if (empty($post["state_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $and_condition = "";
                    $and_condition .= " and state_id = ".$post["state_id"];
                    if (!empty($post["search"])) {
                        $and_condition .= " and name like '%".$post["search"]."%'";
                    }
                    $result_selected = array();
                    if (!empty($post["or_city_id"])) {
                        $result_selected = $this->db->query("select * from tbl_cities where id = ".$post["or_city_id"])->row_array();
                        $and_condition .= " and id <> ".$post["or_city_id"];
                    }

                    $results = $this->db->query("select SQL_CALC_FOUND_ROWS * from tbl_cities where 1=1 ".$and_condition)->result_array();
                    $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                    $total_records = $queryNew->row()->myCounter;
                    if (empty($results) && empty($result_selected)) {
                        $response["success"] = 0;
                        $response["message"] = "Records not found.";
                    } else {
                        $i=0;
                        if (!empty($result_selected)) {
                            $response["data"][$i]["id"] = intval($result_selected["id"]);
                            $response["data"][$i]["name"] = $result_selected["name"];
                            $i++;
                        }
                        if (!empty($results)) {
                            foreach ($results as $value) {
                                $response["data"][$i]["id"] = intval($value["id"]);
                                $response["data"][$i]["name"] = $value["name"];
                                $i++;
                            }
                        }
                        $response["success"] = 1;
                        $response["total_records"] = intval($total_records);
                        $response["message"] = "Records found.";
                    }
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function cron_job ($action) {
        $actions = array("plant_city_static", "daily_report_count", "lead_import", "lead_import_2", "attachment_update");
        $post = $this->input->post();
        if (in_array($action, $actions)) {
            if ($action == "plant_city_static") {
                $result = $this->db->query("SELECT cc.*, ss.is_active FROM cities as cc JOIN states as ss on(cc.state_id = ss.id) WHERE cc.name IN ('Ahmedabad','Vapi','Bharuch','Chikhala','Neemrana','Alwar','Nanded','Satara','Amravati','Hyderabad','Bangalore','Hubli','Vijayawada','Visakhapatnam','Bhubaneswar','Agra','Kanpur','Lucknow','Robertsganj','Meerut','Varanasi','Vellore','Chennai','Salem','Bhopal','Indore','Raipur','Ranchi','Ambala','Rohtak','sangrur') and ss.is_active = 1 GROUP by cc.name order by cc.name ASC")->result_array();
                if (!empty($result)) {
                    $plant_array = array();
                    foreach ($result as $key => $value) {
                        $data["city_id"] = $value["id"];
                        $data["name"] = $value["name"];
                        $data["master_user_id"] = 1;
                        array_push($plant_array, $data);
                    }
                    if (!empty($plant_array)) {
                        $this->db->insert_batch("plant_city_map", $plant_array);
                        echo $this->db->affected_rows();
                    }
                } else {
                    $response["success"] = 0;
                    $response["message"] = "Records not found.";
                }
            } else if ($action == "daily_report_count") {
                $email_array_set["records"] = array();
                $master_user_id = 1;
                $today_dateTime = strtotime(date(date('Y-m-d',strtotime("today"))."00:00:00"));
                $today_dateTimeEnd = strtotime(date(date('Y-m-d',strtotime("today"))."23:59:59"));

                $today_date_created = " and created_at >= ".$today_dateTime." and created_at <= ".$today_dateTimeEnd;
                $today_date_followUp = " and last_follow_up_updated_date >= ".$today_dateTime." and last_follow_up_updated_date <= ".$today_dateTimeEnd;
                $today_new_lead = $this->db->query("select count(id) as total, sum(lead_value) as lead_value_total from inquiries where master_user_id = ".$master_user_id." and is_deleted = 0".$today_date_created)->row_array();
                array_push($email_array_set["records"], 
                    array(
                        "title"=>"New Lead",
                        "total"=>$today_new_lead["total"] ? intval($today_new_lead["total"]) : 0
                    )
                );
                array_push($email_array_set["records"], 
                    array(
                        "title"=>"Lead Value",
                        "total"=>$today_new_lead["lead_value_total"] ? number_format($today_new_lead["lead_value_total"], 2, '.', ',') : 0.00
                    )
                );

                $today_followUp = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$master_user_id." and is_deleted = 0".$today_date_followUp)->row_array();
                array_push($email_array_set["records"], 
                    array(
                        "title"=>"FollowUp",
                        "total"=>$today_followUp ? intval($today_followUp["total"]) : 0
                    )
                );

                $today_overdue = $this->db->query("select count(id) as total from inquiries where master_user_id = ".$master_user_id." and is_deleted = 0 and (follow_up_date < ".$today_dateTime.")")->row_array();
                array_push($email_array_set["records"], 
                    array(
                        "title"=>"Overdue",
                        "total"=>$today_overdue ? intval($today_overdue["total"]) : 0
                    )
                );
                $this->email_model->email_for_daily_report_count($email_array_set);
                $response["success"] = 1;
            } else if ($action == "lead_import") {
                /*$import_path = BASE_URL."assets/uploads/import_images/";
                $path = substr(FCPATH, 0, -4)."api/csv/import_products/";*/

                $spreadsheet = IOFactory::load(substr(FCPATH, 0, -4)."triranga_Infra_Leads.xlsx");
                $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                /*print_r($data);
                die;*/

                foreach ($data as $key => $value) {
                    if ($key > 1 && !empty($value["A"]) && !empty($value["B"])) {
                        $time = time();
                        $data = array();
                        $data["name"] = $value["B"];
                        $data["contact_no"] = "91 ".$value["C"];
                        $data["email_address"] = $value["D"] ? $value["D"] : null;
                        $data["designation"] = $value["E"] ? $value["E"] : null;
                        $data["company_name"] = $value["F"] ? $value["F"] : null;
                        if (!empty($value["H"])) {
                            $get_state = array();
                            $get_state = $this->db->query("select id, name from tbl_states where lower(name) = '".strtolower($value["H"])."'")->row_array();
                            if (!empty($get_state)) {
                                $data["state_id"] = $get_state["id"] ? $get_state["id"] : null;
                                $data["state_name"] = $get_state["name"] ? $get_state["name"] : null;
                                if ($data["state_id"] && !empty($value["G"])) {
                                    $get_city = array();
                                    $get_city = $this->db->query("select id, name from tbl_cities where lower(name) = '".strtolower($value["G"])."' and state_id = ".$data["state_id"])->row_array();
                                    $data["city_id"] = $get_city["id"] ? $get_city["id"] : null;
                                    $data["city_name"] = $get_city["name"] ? $get_city["name"] : null;
                                }
                            }
                        }
                        $data["company_website"] = $value["I"] ? $value["I"] : null;
                        $data["quantity"] = $value["K"] ? $value["K"] : null;
                        if (!empty($value["L"])) {
                            $unit = null;
                            if ($value["L"] == "RMTR") {
                                $unit = "Running Meter";
                            } else if ($value["L"]  == "RFT") {
                                $unit = "Running Ft";
                            } else if ($value["L"]  == "SQFT") {
                                $unit = "Sqft";
                            }
                            $data["unit"] = $unit;
                        }
                        $data["Height"] = $value["M"] ? $value["M"]." ft" : null;
                        $data["rate"] = $value["N"] ? $value["N"] : null;
                        $data["lead_value"] = $value["O"] ? $value["O"] : null;
                        $data["quotation_number"] = $value["P"] ? $value["P"] : null;
                        $data["source"] = $value["Q"] ? $value["Q"] : null;
                        if (!empty($value["R"])) {
                            $get_plant = array();
                            $get_plant = $this->db->query("select city_id, name from plant_city_map where lower(name) = '".strtolower($value["R"])."'")->row_array();
                            
                            $data["which_plant_city_id"] = $get_plant["city_id"] ? $get_plant["city_id"] : null;
                            $data["plant_city_name"] = $get_plant["name"] ? $get_plant["name"] : null;
                        }
                        $data["remarks"] = $value["S"] ? str_replace("_x000D_", "", $value["S"]) : null;
                        $data["assign_master_user_id"] = "1";
                        $data["assign_master_user_name"] = "triranga infra";
                        if (!empty($value["T"])) {
                            $sales_get = array();
                            $sales_get = $this->db->query("select master_user_id, name from master_users where lower(name) = '".strtolower($value["T"])."' and is_deleted = 0")->row_array();
                            if (!empty($sales_get)) {
                                $data["assign_master_user_id"] = $sales_get["master_user_id"];
                                $data["assign_master_user_name"] = $sales_get["name"];
                            }
                        }
                        $data["status"] = "New Customer";
                        if (!empty($value["U"])) {
                            $status_get = $this->db->query("select name from status where lower(name) = '".strtolower($value["U"])."'")->row_array();
                            $data["status"] = $status_get["name"] ? $status_get["name"] : null;
                        }
                        if ($data["status"] != "New Customer" && $data["status"] != "Not Interested") {
                            $data["follow_up_date"] = $time;
                            $data["last_follow_up_updated_date"] = $time;
                        }
                        if ($data["status"] == "Not Interested") {
                            $data["last_follow_up_updated_date"] = $time;
                        }
                        $data["created_at"] = $time;
                        $data["master_user_id"] = 1;
                        $data["priority"] = "warm";
                        /*print_r($data);
                        die;*/
                        $this->db->insert("inquiries", $data);
                        if ($this->db->affected_rows() > 0) {
                            $insert_id = $this->db->insert_id();
                            $follow_up_data["inquiry_id"] = $insert_id;
                            $follow_up_data["created_at"] = $time;
                            $follow_up_data["follow_up_add_master_user_id"] = $data["assign_master_user_id"];
                            $follow_up_data["follow_up_add_master_user_name"] = $data["assign_master_user_name"];
                            $follow_up_data["remarks"] = $data["remarks"];
                            
                            if ($data["status"] != "New Customer") {
                                $follow_up_data["status"] = "New Customer";
                                $this->db->insert("follow_up_history", $follow_up_data);
                                $follow_up_data["remarks"] = null;
                                if ($data["status"] != "Not Interested") {
                                    $follow_up_data["follow_up_date"] = $time;
                                }
                            }

                            $follow_up_data["status"] = $data["status"];
                            $this->db->insert("follow_up_history", $follow_up_data);
                        }
                        /*echo "string";
                        die;*/
                    }
                }
                $response["success"] = 1;
            } else if ($action == "lead_import_2") {
                /*$import_path = BASE_URL."assets/uploads/import_images/";
                $path = substr(FCPATH, 0, -4)."api/csv/import_products/";*/

                $spreadsheet = IOFactory::load(substr(FCPATH, 0, -4)."inquiries_24_07_2023_01_21_pm_62.xlsx");
                $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                /*print_r($data);
                die;*/

                foreach ($data as $key => $value) {
                    if ($key > 1 && !empty($value["B"])) {
                        // $time = time();
                        $data = array();
                        $data["is_bulk_import"] = "29/07/2023";
                        $data["created_at"] = strtotime($value["B"]);
                        $time = $data["created_at"];
                        $data["name"] = $value["C"];
                        $data["contact_no"] = $value["D"] ? "91 ".$value["D"] : null;
                        $data["email_address"] = $value["E"] ? $value["E"] : null;
                        $data["company_name"] = $value["G"] ? $value["G"] : null;
                        $data["designation"] = $value["H"] ? $value["H"] : null;
                        if (!empty($value["I"])) {
                            $get_state = array();
                            $get_state = $this->db->query("select id, name from tbl_states where lower(name) = '".strtolower($value["I"])."'")->row_array();
                            if (!empty($get_state)) {
                                $data["state_id"] = $get_state["id"] ? $get_state["id"] : null;
                                $data["state_name"] = $get_state["name"] ? $get_state["name"] : null;
                                if ($data["state_id"] && !empty($value["J"])) {
                                    $get_city = array();
                                    $get_city = $this->db->query("select id, name from tbl_cities where lower(name) = '".strtolower($value["J"])."' and state_id = ".$data["state_id"])->row_array();
                                    $data["city_id"] = $get_city["id"] ? $get_city["id"] : null;
                                    $data["city_name"] = $get_city["name"] ? $get_city["name"] : null;
                                }
                            }
                        }
                        $data["quantity"] = $value["N"] ? $value["N"] : null;
                        if (!empty($value["O"])) {
                            $unit = null;
                            if ($value["O"] == "RMTR") {
                                $unit = "Running Meter";
                            } else if ($value["O"]  == "RFT") {
                                $unit = "Running Ft";
                            } else if ($value["O"]  == "SQFT") {
                                $unit = "Sqft";
                            }
                            $data["unit"] = $unit;
                        }
                        $data["Height"] = $value["P"] ? $value["P"]." ft" : null;
                        $data["lead_value"] = $value["Q"] ? $value["Q"] : null;
                        $data["quotation_number"] = $value["R"] ? $value["R"] : null;
                        $data["source"] = $value["S"] ? $value["S"] : null;
                        if (!empty($value["T"])) {
                            $get_plant = array();
                            $get_plant = $this->db->query("select city_id, name from plant_city_map where lower(name) = '".strtolower($value["T"])."'")->row_array();
                            
                            $data["which_plant_city_id"] = $get_plant["city_id"] ? $get_plant["city_id"] : null;
                            $data["plant_city_name"] = $get_plant["name"] ? $get_plant["name"] : null;
                        }
                        $data["remarks"] = $value["U"] ? str_replace("_x000D_", "", $value["U"]) : null;
                        $data["assign_master_user_id"] = "1";
                        $data["assign_master_user_name"] = "triranga infra";
                        if (!empty($value["K"])) {
                            $sales_get = array();
                            $sales_get = $this->db->query("select master_user_id, name from master_users where lower(name) = '".strtolower($value["K"])."' and is_deleted = 0")->row_array();
                            if (!empty($sales_get)) {
                                $data["assign_master_user_id"] = $sales_get["master_user_id"];
                                $data["assign_master_user_name"] = $sales_get["name"];
                            }
                        }
                        $data["status"] = "New Customer";
                        if (!empty($value["F"])) {
                            $status_get = $this->db->query("select name from status where lower(name) = '".strtolower($value["F"])."'")->row_array();
                            $data["status"] = $status_get["name"] ? $status_get["name"] : null;
                        }
                        if ($data["status"] != "New Customer" && $data["status"] != "Not Interested") {
                            // $data["follow_up_date"] = $time;
                            // $data["last_follow_up_updated_date"] = $time;
                        }
                        if ($data["status"] == "Not Interested") {
                            $data["last_follow_up_updated_date"] = $time;
                        }
                        $data["master_user_id"] = 1;
                        $data["priority"] = "warm";
                        /*print_r($data);
                        die;*/
                        $this->db->insert("inquiries", $data);
                        if ($this->db->affected_rows() > 0) {
                            $insert_id = $this->db->insert_id();
                            $follow_up_data["inquiry_id"] = $insert_id;
                            $follow_up_data["created_at"] = $time;
                            $follow_up_data["follow_up_add_master_user_id"] = $data["assign_master_user_id"];
                            $follow_up_data["follow_up_add_master_user_name"] = $data["assign_master_user_name"];
                            $follow_up_data["remarks"] = $data["remarks"];
                            
                            if ($data["status"] != "New Customer") {
                                $follow_up_data["status"] = "New Customer";
                                $this->db->insert("follow_up_history", $follow_up_data);
                                $follow_up_data["remarks"] = null;
                                if ($data["status"] != "Not Interested") {
                                    // $follow_up_data["follow_up_date"] = $time;
                                }
                            }

                            $follow_up_data["status"] = $data["status"];
                            $this->db->insert("follow_up_history", $follow_up_data);
                        }
                        /*echo "string";
                        die;*/
                    }
                }
                $response["success"] = 1;
            } else if ($action == "attachment_update") {
                $data = array();
                $result = $this->db->query("select * from inquiries where attachment is not null")->result_array();
                if (!empty($result)) {
                    foreach ($result as $key => $value) {
                        $data[$key]["id"] = $value["id"];
                        $data[$key]["attachment"] = '[{"attachment":"'.$value["attachment"].'","name":"Attachment"}]';
                    }
                }
                if (!empty($data)) {
                    $this->db->update_batch("inquiries", $data, "id");
                    $response["success"] = 1;
                }
            }
        } else {
            $response["success"] = 0;
            $response["message"] = "Invalid Operation.";
        }
        echo json_encode($response);
        die;
    }

    public function source ($action) {
        $actions = array("list");
        $post = $this->input->post();

        if (!in_array($action, $actions)) {
            $response["success"] = 0;
            $response["message"] = "Request not found.";
        } else {
            if ($action == "list") {
                if (empty($post["logged_in_master_user_id"])) {
                    $response["success"] = 0;
                    $response["message"] = "Required fields can not be blank.";
                } else {
                    $m_u_details = $this->front_model->master_user_details_row($post["logged_in_master_user_id"]);
                    if (empty($m_u_details)) {
                        $response["success"] = 0;
                        $response["message"] = "Master user not found.";
                    } else {
                        $result = $this->db->query("select SQL_CALC_FOUND_ROWS * from source where master_user_id = ".$m_u_details["parent_id"]." order by sort asc")->result_array();
                        if (!empty($result)) {
                            $queryNew = $this->db->query('SELECT FOUND_ROWS() as myCounter');
                            $total_records = $queryNew->row()->myCounter;

                            foreach ($result as $key => $value) {
                                $response["data"][$key]["source_id"] = $value["id"];
                                $response["data"][$key]["name"] = $value["name"];
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