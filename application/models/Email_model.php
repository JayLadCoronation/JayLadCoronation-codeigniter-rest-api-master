<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Email_model extends CI_Model {

	/**
     * This model use to send email to users
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct() {
        parent::__construct();
    }

	public function masterTemplate($content) {
		$html = "";
		$html .= '<div style="padding-bottom: 0px;">';
		$html .= '<div style="text-align: center; background: linear-gradient(to right, #7691de, #5155d1); max-width: 600px; border: solid 1px #ccc; margin: 0 auto; padding: 25px 15px; border-bottom: 0;">';
		$html .= '<a href="https://riyatravels.saltpixels.in/">';
		$html .= '<img style="padding: 0px 0px; width:230px;" src="https://riyatravels.saltpixels.in/assets/images/white-logo.png">';
		$html .= '</a>';
		$html .= '</div>';
		$html .= '<div style="max-width: 600px; border: solid 1px #ccc; background-color: #fff; margin: 0 auto;padding:15px;">';
		$html .= $content;
		$html .= '</div>';
		$html .= '</div>';
		return $html;
   	}
	
	public function sendOtpToMail($data = NULL, $template_name = NULL) {
		//echo "select description from email_templates where slug = '{$template_name}'";
		
		$register_email_body = $this->db->query("select description from email_templates where slug = '{$template_name}'")->row_array();
   		//print_r($register_email_body);
        $mail = $register_email_body['description'];

		$mail = str_replace("##NAME##", $data->first_name." ".$data->last_name, $mail);
		$mail = str_replace("##OTP##", $data->otp, $mail);
		$mail = str_replace("##SYSTEM_NAME##",SYSTEM_NAME, $mail);

		$htmlTemplate = $this->masterTemplate($mail);
        //echo $htmlTemplate;
        $subject = SYSTEM_NAME." | Recover your password";
        
        $to = $data->email;
        $bcc = "jayesh@coronation.in";
        $from = "jeet@coronation.in";
        return $this->doEmail($htmlTemplate, $subject, $to, $from);
	}

	public function doEmail($msg=NULL, $sub=NULL, $to=NULL, $from=NULL, $bcc=NULL, $attachments=null) {
		$ci = get_instance();
		$ci->load->library('email');

		$config['protocol'] = "smtp";
		$config['smtp_host'] = "ssl://smtp.gmail.com";
		$config['smtp_port'] = 465;
		$config['smtp_user'] = "mayur@coronation.in";
		$config['smtp_pass'] = "Admin2015$";
		$config['smtp_debug'] = 4;

		$config['charset'] = "utf-8";
		$config['mailtype'] = "html";
		$config['newline'] = "\r\n";
		$config['crlf'] = "\r\n";

		$ci->email->initialize($config);

		$system_name   =  SYSTEM_NAME;
		if($from == NULL) {
			$from    =  "mayur@coronation.in";
		}

		$ci->email->from($from, ucfirst($system_name));
		$ci->email->to($to);
		if ($bcc) {
			$ci->email->bcc($bcc);
		}
		$ci->email->subject($sub);
		$ci->email->message($msg);

		if (!empty($attachments))
		{
			foreach ($attachments as $attachment) {
			   if ($attachment) {
				  $this->email->attach($attachment);
			   }
			}
		}

		// $IsSendMail = 1;
		$IsSendMail = $ci->email->send();
		if (!$IsSendMail) {
		   return $returnvalue = 1;
		}
		else {
		   return $returnvalue = 1;
		}

	 }
}



