<?php
/**	
 * Blibli Account Creator
 * 
 * @release 2020
 * 
 * @author eco.nxn
 */
date_default_timezone_set("Asia/Jakarta");
error_reporting(0);
class curl {
	private $ch, $result, $error;
	
	/**	
	 * HTTP request
	 * 
	 * @param string $method HTTP request method
	 * @param string $url API request URL
	 * @param array $param API request data
     * @param array $header API request header
	 */
	public function request ($method, $url, $param, $header) {
		curl:
        $this->ch = curl_init();
        switch ($method){
            case "GET":
                curl_setopt($this->ch, CURLOPT_POST, false);
                break;
            case "POST":               
                curl_setopt($this->ch, CURLOPT_POST, true);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
                break;
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0');
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);
        if(is_numeric(strpos($url, 'blibli.com'))) {
            curl_setopt($this->ch, CURLOPT_COOKIEJAR,'cookie.txt');
		    curl_setopt($this->ch, CURLOPT_COOKIEFILE,'cookie.txt');
        }
        $this->result = curl_exec($this->ch);
        $this->error = curl_error($this->ch);
        if($this->error) {
            echo "[!] ".date('H:i:s')." | Connection Timeout\n";
            sleep(2);
            goto curl;
        }
        curl_close($this->ch);
        return $this->result;
    }   
}

class blibli extends curl{

    function random_str($length)
    {
        $data = 'qwertyuioplkjhgfdsazxcvbnmMNBVCXZASDFGHJKLPOIUYTREWQ';
        $string = '';
        for($i = 0; $i < $length; $i++) {
            $pos = rand(0, strlen($data)-1);
            $string .= $data{$pos};
        }
        return $string;
    }

    /**
     * Get random name
     */
    function randomuser() {
        randomuser:
        echo "[i] ".date('H:i:s')." | Generating name...\n";
        $randomuser = file_get_contents('https://econxn.id/api/v1/randomUser/?quantity=20');
        if($randomuser) {
            $json = json_decode($randomuser);
            if($json->status->code == 200) {
                return $json->result;
            } else {
                echo "[!] ".date('H:i:s')." | Failure while generating name!\n";
                sleep(2);
                goto randomuser;
            }        
        } else {        
            sleep(2);
            goto randomuser;
        }
    }

    /**
     * Registrasi akun
     */
    function regis($email, $pass) { 

        unlink('cookie.txt');

        $method   = 'POST';
        $header = ['Content-Type: application/json;charset=utf-8', 'Origin: https://www.blibli.com'];

        $endpoint = 'https://www.blibli.com/backend/common/users';
        
        $param = '{"username":"'.$email.'","password":"'.$pass.'"}';
        
        $regis = $this->request ($method, $endpoint, $param, $header);
        
        $json = json_decode($regis);

        if(!isset($json->data->id)) { 
            return FALSE;
        } else {
            return $json;
        }         
    }

    /**
     * Generate new email
     */
    function new_email($username) {

        $method   = 'POST';
        $header   =  [
            'Content-Type: application/json;charset=utf-8'
        ];
        $endpoint = 'https://api.internal.temp-mail.io/api/v2/email/new';

        // $param = '{"name":"'.$username.'"}'; //Custome email 

        $domain= ['inscriptio.in', 'montokop.pw', 'smart-email.me'];
        $param = '{"name":"'.$username.'","domain":"'.$domain[rand(0,2)].'"}'; //full email

        $email = $this->request ($method, $endpoint, $param, $header);
   
        $json = json_decode($email);

        if(empty($json->email)) {
            return FALSE;
        } else {
            return $json->email;
        }
    }

    /**
     * Check inbox
     */
    function inbox($email) {

        $method   = 'GET';

        $endpoint = 'https://api.internal.temp-mail.io/api/v2/email/'.str_replace('%40', '@', $email).'/messages';

        $inbox = $this->request ($method, $endpoint, $param=null, $header=null);

        $json = json_decode($inbox);

        foreach ($json as $json) {  
            
            if(isset($json->body_text)) { 
                if(is_numeric(strpos($json->from, 'no-reply@blibli.com'))) {    
                    $a = stripos($json->body_text, 'WELCOMEEMAILSERIES', 1000);
                    $b = strpos($json->body_text, 'Kalau kamu tidak'); 
                    $activation_link = substr($json->body_text, ($a+21), (strlen($json->body_text)-$b+4)*-1); 
                    return $activation_link;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }   
        }
    }

    /**
     * Activation
     */
    function activation($endpoint, $email) {

        $method   = 'GET'; 

        $activation = $this->request ($method, $endpoint, $param=null, $header=null); 

        if(is_numeric(strpos($activation, 'email-verification?code='))) {    
            $a = stripos($activation, 'code=');
            $b = strpos($activation, '&'); 
            $activation_code = substr($activation, $a+5, (strlen($activation)-$b)*-1); 

            $method_ = 'POST';
            $header_ = ['Content-Type: application/json;charset=utf-8', 'Origin: https://www.blibli.com'];

            $endpoint_ = 'https://www.blibli.com/backend/member/email-verification/_verify';

            $param_ = '{"logonId":"'.$email.'","token":"'.$activation_code.'"}';
        
            $activation_ = $this->request ($method_, $endpoint_, $param_, $header_); 
            
            $json = json_decode($activation_);

            if($json->code == 200) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Login akun
     */
    function login($email, $pass) { 

        unlink('cookie.txt');
        $this->request ('GET', 'https://www.blibli.com', $param=null, $header=null);

        $method   = 'POST';
        $header = ['Content-Type: application/x-www-form-urlencoded', 'Origin: https://account.blibli.com'];

        $endpoint = 'https://account.blibli.com/gdn-oauth/token';
        
        $param = 'grant_type=password&scope=write&username='.$email.'&password='.$pass.'&client_id=6354c4ea-9fdf-4480-8a0a-b792803a7f11&client_secret=XUQpvvcxxyjfb7KG';
        
        $login = $this->request ($method, $endpoint, $param, $header);
        
        $json = json_decode($login);

        if(!isset($json->access_token)) {
            return FALSE;
        } else {
            return $json->access_token;
        }         
    }

    /**
     * Send OTP
     */
    function send_otp($phone, $bearer) {

        $method   = 'GET';
        $header = [
            'Authorization: bearer '.$bearer
        ];
        $endpoint = 'https://www.blibli.com/backend/mobile/phone-number-verification/token?phoneNumber='.$phone;

        $phone = $this->request ($method, $endpoint, $param=NULL, $header);
   
        $json = json_decode($phone);

        if(strtoupper($json->result) == "FALSE") { 
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Input OTP
     */
    function verif_otp($otp, $bearer) {

        $method   = 'POST';
        $header = [
            'Authorization: bearer '.$bearer,
            'Content-Type: application/json; charset=utf-8'
        ];

        $endpoint = 'https://www.blibli.com/backend/mobile/phone-number-verification/verify-token';

        $param = '{"token": "'.$otp.'"}';

        $verif = $this->request ($method, $endpoint, $param, $header);
   
        $json = json_decode($verif);

        if(strtoupper($json->result) == "FALSE") {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}

/**
 * Running
 */
echo "Checking for Updates...";
$version = 'V1.3.1';
$json_ver = json_decode(file_get_contents('https://econxn.id/setset/blabla.json'));
echo "\r\r                       ";
if(isset($json_ver->version)) {
    if($version != $json_ver->version) {
        echo "\n".$json_ver->msg."\n\n";
        die();
    } else {
        echo "\n[?] Password :";
        $password = trim(fgets(STDIN));
        if($json_ver->hash != md5($password)) {
            die();
        }
    }
}

// style 
// style 
echo "\n";
echo " accounts creator\n";                  
echo " v1.3.1                     ____ ___   __ _  \n";               
echo " _      _  _  _      _  _  / __// _ \ /  ' \ \n"; 
echo "| |__  | |(_)| |__  | |(_) \__/ \___//_/_/_/ \n";
echo "| '_ \ | || || '_ \ | || |\n";
echo "| |_) || || || |_) || || |\n";
echo "|_.__/ |_||_||_.__/ |_||_|\n";
echo "               By @eco.nxn\n";
echo "\n";
echo "*Akun tersimpan di accounts.txt\n";
echo "*Cek inbox di https://temp-mail.io, paste email menu choose->name\n";
echo "*Email expired? Buat yg sama di https://temp-mail.io, menu choose\n\n"; 

$blibli = new blibli();
qty:
echo "[?] Jumlah akun :";
$qty = trim(fgets(STDIN));
if(strtolower($qty) == 'q') {
    die(); 
}
if(!is_numeric($qty)) {
    goto qty;
}
echo "\n";
$i=1;
while($i <= $qty) { 

    $randomuser = $blibli->randomuser();
    foreach ($randomuser as $value) {
        $exp_email  = explode("@", $value->Email);
        $username   = $exp_email[0];
        $pass       = ucwords($blibli->random_str(8)).rand(1,9); 
        
        new_email:
        echo "[i] ".date('H:i:s')." | Creating new email...\n";
        $email = $blibli->new_email($username);
        if($email == FALSE) {
            echo "[!] ".date('H:i:s')." | Failure while generating new email.\n";
            sleep(1);
            goto new_email;
        }
          
        $regis = $blibli->regis($email, $pass);
        if($regis == FALSE) {
            echo "[!] ".date('H:i:s')." | Registration Failed!\n\n";
            sleep(2);
            continue;
        }
        
        echo "[i] ".date('H:i:s')." | Registration Success [Email:".$email.";Pass:".$pass."]\n";           
        // cek inbox
        echo "[i] ".date('H:i:s')." | Checking email...\n";
        $ib=0;
        inbox:
        $inbox = $blibli->inbox($email);
        if($inbox == FALSE) { 
            sleep(3);
            $ib = $ib+1;
            if($ib<=15) {
                goto inbox;
            } else {
                echo "[!] ".date('H:i:s')." | Skip..Activation Link not found\n\n";
            }
        } else {          
            //aktivasi akun
            $ac=0;
            activation:
            $_activation = $blibli->activation($inbox, $email);
            if($_activation == TRUE) {
                echo "[".$i++."] ".date('H:i:s')." | Activation Success\n";
                // save
                $fh = fopen('accounts.txt', "a");
                fwrite($fh, $email.";".$pass."\n");
                fclose($fh);

                verif_phone:
                echo "[?] Verify Phone? [Y/N] ";
                $verif_phone = trim(fgets(STDIN));
                if(strtolower($verif_phone) == 'y') { 
                    $login = $blibli->login($email, $pass);
                    if($login == FALSE) {
                        echo "[!] ".date('H:i:s')." | Login failed!\n\n";
                    } else {
                        $bearer = $login;

                        input_phone:
                        echo "[?] Enter Phone :";
                        $phone = trim(fgets(STDIN));
                        if(strtolower($phone) == 'z') {
                            die(); 
                        }
                        if(!is_numeric($phone)) {
                            goto input_phone;
                        }
                        send_otp:
                        $send_otp = $blibli->send_otp($phone, $bearer);
                        if($send_otp == FALSE) {
                            echo "[!] ".date('H:i:s')." | Send OTP failed!\n";
                            goto input_phone;
                        } else {
                            $io=0;
                            input_otp:
                            echo "[?] Enter OTP [max.5x] :";
                            $otp = trim(fgets(STDIN));
                            if (strtolower($otp) == 'q') {
                                die(); 
                            }
                            if(!is_numeric($otp)) {
                                goto input_otp;
                            } 
                            $verif_otp = $blibli->verif_otp($otp, $bearer);
                            if($verif_otp == FALSE) {
                                echo "[!] ".date('H:i:s')." | Verif OTP Code failed!\n";
                                $io = $io+1;
                                if($io < 5) {
                                    goto input_otp;
                                } else {
                                    echo "\n";
                                }     
                            } else {
                                echo "[i] ".date('H:i:s')." | Verify Phone Success\n\n";
                            }
                        }
                    }
                } elseif (strtolower($verif_phone) == 'q') {
                    die(); 
                } else {
                    echo "\n";
                }

            } else {
                $ac = $ac+1;
                if($ac<=3) {
                    goto activation;
                } else {
                    echo "[i] ".date('H:i:s')." | Activation Failed!\n\n";
                }
            }
        } 
        if($i > $qty) {
            die();
        }      
    }   
}
?>