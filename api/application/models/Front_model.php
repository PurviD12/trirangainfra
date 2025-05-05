<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once 'library/excel/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
require 'library/excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Front_model extends CI_Model {

    function contact_no_dummy() {
        return array(1234567890,9999999999,1231111111,7894561230,1234567890,8888888888,7777777777,6666666666,5555555555,4444444444,3333333333,2222222222,1111111111,0000000000,1212121212,7878787878,2323232323,8989898989,5656565656,7979797979);
    }

    function timespend_sort($datetime, $full = false) {
        if (!empty($datetime)) {
            $now = new DateTime;
            $ago = new DateTime($datetime);
            $diff = $now->diff($ago);
            $day_diff = $diff->format('%a');

            /*if ((int)($day_diff) <= 2) {*/
                $diff->w = floor($diff->d / 7);
                $diff->d -= $diff->w * 7;

                $string = array(
                    'y' => 'year',
                    'm' => 'month',
                    'w' => 'week',
                    'd' => 'day',
                    'h' => 'hour',
                    'i' => 'min',
                    's' => 'sec',
                );
                
                foreach ($string as $k => &$v) {
                    if ($diff->$k) {
                        /*$v = $diff->$k.$k;*/
                        $v = $diff->$k.' '.$v;
                    } else {
                        unset($string[$k]);
                    }
                }
                if (!$full) $string = array_slice($string, 0, 1);
                $string  =  $string ? implode(', ', $string) : 'now';
                return $string;
            /*}  else {
                $datetimestamp = substr($datetime, 1, strlen($datetime));
                // return date("d-m-Y h:i A", $datetimestamp);
                return date("h:i A", $datetimestamp);
            }*/
        }
    }

    function days_get($time) {
        if (!empty($time)) {
            $datediff = time() - $time;
            $date = round($datediff / (60 * 60 * 24));
            return $date;
        }
    }

    function timespend($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $day_diff = $diff->format('%a');

        if ((int)($day_diff) <= 2) {
            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;

            $string = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
            
            foreach ($string as $k => &$v) {
                if ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }
            if (!$full) $string = array_slice($string, 0, 1);
            $string  =  $string ? implode(', ', $string) . ' ago' : 'just now';
            return ($string == "1 day ago") ? "a day ago" : $string;
        }  else {
            $datetimestamp = substr($datetime, 1, strlen($datetime));
            // return date("d-m-Y h:i A", $datetimestamp);
            return date("h:i A", $datetimestamp);
        }
    }

    function timespend_in($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $day_diff = $diff->format('%a');

        if ((int)($day_diff) <= 2) {
            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;

            $string = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
            
            foreach ($string as $k => &$v) {
                if ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }
            if (!$full) $string = array_slice($string, 0, 1);
            $string  =  $string ? 'In '.implode(', ', $string) : 'just now';
            // print_r($string);
            // die;
            return ($string == "In 1 day") ? "In a day" : $string;
        }  else {
            $datetimestamp = substr($datetime, 1, strlen($datetime));
            // return date("d-m-Y h:i A", $datetimestamp);
            return date("h:i A", $datetimestamp);
        }
    }

    function generateRandomString($length = 10, $length1 = 10) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $charactersLength = strlen($characters);
        $numbersLength = strlen($numbers);
        $randomString = '';


        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        // $randomString .= "-";
        for ($i = 0; $i < $length1; $i++) {
            $randomString .= $numbers[rand(0, $numbersLength - 1)];
        }

        return $randomString;
    }

    public function get_master_user_details($data){
        $from_date = date("Y-m-d",strtotime("today"));
        $to_date = date("Y-m-d",strtotime("today"));
        $from_date_today =  strtotime(date($from_date."00:00:00"));
        $to_date_today =  strtotime(date($to_date."23:59:59"));

        $result["master_user_id"] = intval($data["master_user_id"]);
        $result["name"] = $data["name"];
        $result["name_small"] = strtoupper(substr($data["name"], 0, 1));
        $result["email_address"] = $data["email_address"];
        $result["contact_no"] = $data["contact_no"];
        $result["is_active"] = intval($data["is_active"]);
        $result["type"] = $data["type"] ? $data["type"] : "";
        $result["role_type"] = $data["role_type"] ? $data["role_type"] : "";
        $result["parent_id"] = intval($data["parent_id"]);
        $result["created_at"] = date("d M, Y", $data["created_at"]);
        $result["updated_at"] = $data["updated_at"] ? date("d M, Y", $data["updated_at"]) : "";
        $result["profile_image"] = $data["profile_image"] ? $data["profile_image"] : "";
        $result["profile_image_full"] = $data["profile_image"] ? IMAGETOOL.BASE_PATH."users/profile/".$data["profile_image"] : "";

        return $result;
    }

    function master_user_details_row ($logged_in_master_user_id="", $user_name="") {
        $m_u_details = array();
        $and_condition = "";
        if ($logged_in_master_user_id) {
            $and_condition .= " and master_user_id = ".$logged_in_master_user_id;
        } else if ($user_name) {
            $and_condition .= " and user_name = '".$user_name."'";
        }
        $m_u_details = $this->db->query("select * from master_users where 1=1 ".$and_condition." and is_deleted = 0")->row_array();
        return $m_u_details;
    }

    function create_user ($user_data) {
        $user_detail = $this->db->query("select id from users where contact_no = '".$user_data["contact_no"]."' and is_deleted = 0 and master_user_id = ".$user_data["master_user_id"])->row_array();
        $user_id = $user_detail["id"];
        if(empty($user_detail["id"])){
            $data["name"] = $user_data["name"];
            $data["contact_no"] = $user_data["contact_no"];
            $data["email_address"] = $user_data["email_address"] ? $user_data["email_address"] : null;
            $data["created_at"] = $user_data["time"];
            $data["master_user_id"] = $user_data["master_user_id"];

            $save = $this->db->insert("users", $data);
            $user_id = $this->db->insert_id();
        }
        return $user_id;
    }

    public function export_all_inquiry_sheet($data, $lead_type = ""){
        if($data){
            $styleArray = array(
                'font'  => array(
                    'bold'  => true,
                    'color' => array('rgb' => '000000'),
                    'size'  => 10.5,
                    'name'  => 'Verdana'
                )
            );
            $spreadsheet = new Spreadsheet();
            foreach (range('B', 'Z') as $letra) {
                $spreadsheet->getActiveSheet()->getColumnDimension($letra)->setWidth(25);
            }

            foreach (range('A', 'Z') as $letra_m) {
                foreach (range('A', 'Z') as $letra) {
                    $spreadsheet->getActiveSheet()->getColumnDimension($letra_m.$letra)->setWidth(25);
                }
            }
            
            $spreadsheet->setActiveSheetIndex(0)->getStyle(1)->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(1,1,'ID');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(2,1,'Date');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(3,1,'Sales Name');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(4,1,'Name');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(5,1,'Contact No.');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(6,1,'Email');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(7,1,'Order Date');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(8,1,'Status');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(9,1,'Is Big Buyer');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(10,1,'Company Name');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(11,1,'Designation');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(12,1,'State');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(13,1,'City');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(14,1,'Village');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(15,1,'Priority');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(16,1,'Company Website');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(17,1,'Unit');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(18,1,'Quantity');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(19,1,'Nos Of Column');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(20,1,'Nos Of Panel');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(21,1,'Height');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(22,1,'Lead Value');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(23,1,'Quotation Number');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(24,1,'Source');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(25,1,'Other Source');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(26,1,'From Which Plant');
            $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(27,1,'Remarks');
            /*$spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(28,1,'Attachment');*/
           
            $x = 2;
            $i = 0;

            foreach($data as $value){
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(1,$x, $value['id']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(2,$x, date("d M, Y", $value['created_at'])." \n".date("h:i A", $value["created_at"]));
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(3,$x, $value['assign_master_user_name'] ? ucwords($value['assign_master_user_name']) : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(4,$x, ucwords($value['name']));
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(5,$x, $value['contact_no']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(6,$x, $value['email_address'] ? $value['email_address'] : "--");
                if ($value['status'] == "Customer") {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(7,$x, date("d M, Y", $value['last_follow_up_updated_date'])." \n".date("h:i A", $value["last_follow_up_updated_date"]));
                } else {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(7,$x, "--");
                }
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(8,$x, $value['status']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(9,$x, $value['is_big_buyer'] == 1 ? "Yes" : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(10,$x, $value['company_name'] ? $value['company_name'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(11,$x, $value['designation'] ? $value['designation'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(12,$x, $value['state_name'] ? $value['state_name'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(13,$x, $value['city_name'] ? $value['city_name'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(14,$x, $value['village'] ? $value['village'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(15,$x, $value['priority'] ? ucwords($value['priority']) : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(16,$x, $value['company_website'] ? $value['company_website'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(17,$x, $value['unit'] ? $value['unit'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(18,$x, $value['quantity'] ? $value['quantity'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(19,$x, $value['nos_of_column'] ? $value['nos_of_column'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(20,$x, $value['nos_of_panel'] ? $value['nos_of_panel'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(21,$x, $value['height'] ? $value['height'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(22,$x, $value['lead_value'] ? $value['lead_value'] : 0);
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(23,$x, $value['quotation_number'] ? $value['quotation_number'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(24,$x, $value['source'] ? $value['source'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(25,$x, $value['other_source'] ? $value['other_source'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(26,$x, $value['plant_city_name'] ? $value['plant_city_name'] : "--");
                $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(27,$x, $value['remarks'] ? $value['remarks'] : "--");
                /*if (!empty($value['attachment'])) {
                    foreach (json_decode($value["attachment"], true) as $value_attachment) {
                        $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(28,$x, $value['attachment'] ? "Click Here" : "--");
                        if ($value['attachment']) {
                            $spreadsheet->setActiveSheetIndex(0)->getCellByColumnAndRow(28, $x)
                                ->getHyperlink()
                                ->setUrl(BASE_PATH.'inquiry/attachment/'.$value['attachment']);
                        }
                    }
                } else {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValueByColumnAndRow(28,$x, "--");
                }*/
                $x++;
                $i++;
            }

            if(!file_exists(UPLOAD_URL."export/inquiries")){
                mkdir(UPLOAD_URL."export/inquiries");
            }
            $file_path = UPLOAD_URL."export/inquiries/";
            if (!empty($lead_type)) {
                $file_name = $lead_type."_".date("d_m_Y_h_i_a")."_".rand(1,100).".xls";
            } else {
                $file_name = "inquiries_".date("d_m_Y_h_i_a")."_".rand(1,100).".xls";
            }
               
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save($file_path.$file_name);
        }
        return $response["file_path"] = BASE_PATH."export/inquiries/".$file_name;
    }

    public function textarea_text_put ($text) {
        $text = str_replace("\r", "", $text);
        $explode = explode("\n", $text);
        $text = "";
        foreach ($explode as $key => $value) {
            if (empty($value)) {
                $text .= "<br/>";
            } else {
                if (count($explode) > $key) {
                    $text .= $value."<br/>";
                } else {
                    $text .= $value;
                }
            }
        }
        /*$text = rtrim($text, "<br/>");*/
        return $text;
    }

    public function textarea_text_get ($text) {
        $text = str_replace("<br/>","\n", $text);
        return $text;
    }

    function convertToIndianNumberingSystem_2 ($value) {
        if ($value >= 100) {
            return preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value);
        } else {
            return number_format($value, 2);
        }
    }

    function convertToIndianNumberingSystem ($value) {
        if ($value >= 100000000000000000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Padma';
        } else if ($value >= 100000000000000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Neel';
        } else if ($value >= 100000000000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Kharab';
        } else if ($value >= 1000000000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Arab';
        } else if ($value >= 10000000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Cr';
        } else if ($value >= 100000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Lakh';
        } else if ($value >= 1000) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Thousand';
        } else if ($value >= 100) {
            return '₹'.preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $value).' Hundred';
        } else {
            return '₹' . number_format($value, 2);
        }

        /*if ($value >= 100000000000000000) {
            return '₹'.number_format($value, 2).' Padma';
        } else if ($value >= 100000000000000) {
            return '₹'.number_format($value, 2).' Neel';
        } else if ($value >= 100000000000) {
            return '₹'.number_format($value, 2).' Kharab';
        } else if ($value >= 1000000000) {
            return '₹'.number_format($value, 2).' Arab';
        } else if ($value >= 10000000) {
            return '₹'.number_format($value, 2).' Cr';
        } else if ($value >= 100000) {
            return '₹'.number_format($value, 2).' Lakh';
        } else if ($value >= 1000) {
            return '₹'.number_format($value, 2).' Thousand';
        } else if ($value >= 100) {
            return '₹'.number_format($value, 2).' Hundred';
        } else {
            return '₹' . number_format($value, 2);
        }*/

        /*if ($value >= 100000000000000000) {
            return '₹' . ($value) . ' Padma';
        } else if ($value >= 100000000000000) {
            return '₹' . ($value) . ' Neel';
        } else if ($value >= 100000000000) {
            return '₹' . ($value) . ' Kharab';
        } else if ($value >= 1000000000) {
            return '₹' . ($value) . ' Arab';
        } else if ($value >= 10000000) {
            return '₹' . ($value) . ' Cr';
        } else if ($value >= 100000) {
            return '₹' . ($value) . ' Lakh';
        } else if ($value >= 1000) {
            return '₹' . ($value) . ' Thousand';
        } else if ($value >= 100) {
            return '₹' . ($value) . ' Hundred';
        } else {
            return '₹' . ($value);
        }*/
    }

    public function extractDateTimeFormat($str){
        if(strpos($str, '/') !== false){
            $str = str_replace("/","-",$str);
        }

        if(strpos($str, '-') !== false || strpos($str, '.') !== false){
            if(strpos($str, '-') !== false){
                $tempArray = explode("-",$str);
            } else if(strpos($str, '.') !== false){
                $tempArray = explode(".",$str);
            }
            
            if(strlen($tempArray[2]) == 2 || strlen($tempArray[2]) == 3){
                $year = '20'.$tempArray[2];
                if($year > date('Y')) {
                   $year = $year - 100;
                }
                $str = $tempArray[0]."-".$tempArray[1]."-".$year;
            } else {
                $str = $tempArray[1]."-".$tempArray[0]."-".$tempArray[2];
            }
        }
        $is_valid = $this->isValid($str);

        if($is_valid){
            $dateObject = new DateTime($str);
            $timestamp = $dateObject->format("U");
        } else {
            $timestamp = "";
        }
        return $timestamp;
    }
}