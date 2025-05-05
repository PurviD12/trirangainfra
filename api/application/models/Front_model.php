<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once 'library/excel/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
require 'library/excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Front_model extends CI_Model {

    function token_verify ($user_id = "") {
        $allowedMethods = array("cron_job", "test");
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
                $checkAuth = $this->db->get_where("authorization", array("token" => $authorization, "user_id" => $user_id))->row_array();

                if(empty($checkAuth)){
                    $response["success"] = 0;
                    $response["message"] = "Invalid Authorization";
                    echo json_encode($response);
                    die;
                }
            } else if ($front_token_check === 0) {
                $authorization = str_replace("User ", "", $token);
                if ($authorization != "CNT24RIFM16VGTHRT6PSRTBQIN4AGT78Y456455GR3FU8778FGH8TYDU4648534FV75") {
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
    }

    function contact_no_dummy() {
        return array(1234567890,9999999999,1231111111,7894561230,1234567890,8888888888,7777777777,6666666666,5555555555,4444444444,3333333333,2222222222,1111111111,0000000000,1212121212,7878787878,2323232323,8989898989,5656565656,7979797979);
    }

    public function validate_email ($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validate_mobile ($mobile) {
        return filter_var($mobile, FILTER_SANITIZE_NUMBER_INT);
    }

    public function product_details ($product_data) {
        $result["product_id"] = $product_data["id"];
        $result["product_name"] = $product_data["product_name"] ? $product_data["product_name"] : "";
        $result["cas_no"] = $product_data["cas_no"] ? $product_data["cas_no"] : "";
        $result["therapeutic_use"] = $product_data["therapeutic_use"] ? $product_data["therapeutic_use"] : "";
        $result["category_name"] = $product_data["category_name"] ? $product_data["category_name"] : "";
        return $result;
    }

}