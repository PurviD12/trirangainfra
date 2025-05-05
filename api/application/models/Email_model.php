<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class email_model extends CI_Model {
	function master_template($content){
		$html = "";
		$html .= '<div style="padding-bottom: 0px;">';
		$html .= '<div style="text-align: center; background: transparent; width: 600px; border: solid 1px #ccc; margin: 0 auto; padding: 15px 15px 10px 15px; border-bottom: 0;">';
		$html .= '<a href="'.BASE_URL.'" style="display: block;">';
		$html .= '<img style="padding: 0px 0px; width:220px;" src="'.LOGO_URL.'">';
		$html .= '</a>';
		$html .= '</div>';
		$html .= '<div style="max-width: 600px; border: solid 1px #ccc; background-color: #fff; margin: 0 auto;padding:15px;">';
		$html .= $content;
		$html .= '</div>';
		$html .= '</div>';
		return $html;
   	}

	public function inquiry_email($data, $subject, $bcc, $email_to) {
		$email_body = "";
		$email_body .= '<div style="font-size: 22px; font-weight: 900;">
							<p style="font-weight: 100;color:#000000;font-size:18px;margin:0;">Dear Admin,</p>
							<p style="margin-top:20px;font-weight: 100;color:#000000;font-size:18px;margin:0;">New inquiry sent by <b>'.ucwords($data["name"]).'.</b><br/>Please find the more details below.</p>
							<table style="width:100%;margin-top:20px;">
								<tr>
									<td style="padding:6px 0;width:150px;font-weight: 100;font-size:16px;color:#666;">Name</td>
									<td style="padding:6px 0;width:20px;text-align:center;font-weight: 100;font-size:16px;">:</td>
									<td style="padding:6px 0;font-weight: 100;font-size:16px;"><b>'.ucwords($data["name"]).'</b></td>
								</tr>
								<tr>
									<td style="padding:6px 0;width:150px;font-weight: 100;font-size:16px;color:#666;">Email</td>
									<td style="padding:6px 0;width:20px;text-align:center;font-weight: 100;font-size:16px;">:</td>
									<td style="padding:6px 0;font-weight: 100;font-size:16px;"><b>'.$data["email_address"].'</b></td>
								</tr>
								<tr>
									<td style="padding:6px 0;width:150px;font-weight: 100;font-size:16px;color:#666;">Mobile No.</td>
									<td style="padding:6px 0;width:20px;text-align:center;font-weight: 100;font-size:16px;">:</td>
									<td style="padding:6px 0;font-weight: 100;font-size:16px;"><b>+91 '.$data["contact_no"].'</b></td>
								</tr>
								<tr>
									<td style="padding:6px 0;width:150px;font-weight: 100;font-size:16px;color:#666;">Subject</td>
									<td style="padding:6px 0;width:20px;text-align:center;font-weight: 100;font-size:16px;">:</td>
									<td style="padding:6px 0;font-weight: 100;font-size:16px;"><b>'.ucwords($data["subject"]).'</b></td>
								</tr>';
								if ($data["comments"] != "") {
									$email_body .= '<tr>
										<td style="padding:6px 0;width:150px;font-weight: 100;font-size:16px;vertical-align:top;color:#666;">Comment</td>
										<td style="padding:6px 0;width:20px;text-align:center;font-weight: 100;font-size:16px;">:</td>
										<td style="padding:6px 0;font-weight: 100;font-size:16px;vertical-align:top;"><b>'.$data["comments"].'</b></td>
									</tr>';
								}

							$email_body .= '</table>
						</div>';
		$email_subject = SYSTEM_NAME." : ".$subject;
		$htmlTemplate = $email_body;
		if (!empty($data["attachment"])) {
			$attachments = array($data["attachment"]);
		}
		$email_body = $this->master_template($email_body);
		$bcc = $bcc;
		$to = $email_to;
		$from = "circuitnoderajkot@gmail.com";
		$this->do_email($email_body, $email_subject, $to, $from, $bcc, $attachments);
	}
	
	public function do_email($msg=NULL, $sub=NULL, $to=NULL, $from=NULL, $bcc=NULL, $attachments=null) {
	    $ci = get_instance();
	    $ci->load->library('email');

	    if ($attachments){
			$attachments = array_unique($attachments);
		}

	    $config['protocol'] = "smtp";
	    $config['smtp_host'] = "ssl://smtp.gmail.com";
	    $config['smtp_port'] = 465;
	    // $config['smtp_user'] = "purvid@saptez.com";
	    // $config['smtp_pass'] = "mrdcfygyclfnaeki";

	    if (ISINPRODUCTION == "false") {
	    	// client
	    	$config['smtp_user'] = "circuitnoderajkot@gmail.com";
	    	$config['smtp_pass'] = "kevkywqblaaqdnej";
	    } else {
	    	$config['smtp_user'] = "purvid@saptez.com";
	    	$config['smtp_pass'] = "mrdcfygyclfnaeki";
	    }

	    $config['smtp_debug'] = 4;

	    $config['charset'] = "utf-8";
	    $config['mailtype'] = "html";
	    $config['newline'] = "\r\n";
	    $config['crlf'] = "\r\n";

	    $ci->email->initialize($config);

	    $system_name   =  SYSTEM_NAME;

	    $ci->email->from($from, ucfirst($system_name));
	    $ci->email->to($to);
	    if (!empty($bcc)) {
		    $ci->email->bcc($bcc);
	    }
	    $ci->email->subject($sub);
	    $ci->email->message($msg);

	    foreach ($attachments as $attachment) {
	        if ($attachment) {
	            $this->email->attach($attachment);
	        }
	    }

	    if (ISINPRODUCTION == "false") {
	    	$IsSendMail = $ci->email->send();
	    }

	    /*echo ($this->email->print_debugger());
	    die;*/

	    if (!$IsSendMail) {
	        return $returnvalue = 1;
	    }
	    else {
	        return $returnvalue = 1;
	    }
   	}
}