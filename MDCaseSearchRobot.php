<?php
/*
Curl MD Case Search Scraper
By Matthew Stubenberg
Copyright Maryland Volunteer Lawyers Service 2017
Created July 2016

Readme:
The purpose of this class is to scrape MD Judiciary Case Search by case number.
You first start by getting the JSESSIONID from Case Search. This session id allows you to bypass the checkbox.
The reason you first get the sessionid is so you can give the same sessionid each time. This cuts down the number of requests the
program has to make if you are making a lot of them. i.e. pulling all Balt City cases.

Suggested Usage:

$sessionid = MDCaseSearchRobot::getJSessionCookie(); //Get the session id

while(Number of cases){
	$robot = new MDCaseSearchRobot($sessionid);
	$returncode = $robot->getCase($casenumber);
	switch($returncode){
		case 200:
			//Success
			$html = $robot->getHTML();
			//Do something with the HTML
			break;
		case 300:
			//Case Not Found
			break;
		default:
			//Bigger Issues
			echo "Error: Return Code: " . $returncode;
			break;
	}
	unset($robot);
	
	//incrementCaseNumber
}

getCase($casenumber) return codes
100: Failed Case Search Response
200: Sucessfully Found a case
300: Case was not found (i.e. returned to search page)
400: Case data page is blank
500: Disclaimer page
600: Unknown error.

*/

class MDCaseSearchRobot{
	public $casenumber;
	public $JSession;
	public $HTTPCode;
	public $html;
	function __construct($session){
		$this->JSession = $session;
	}
	public function getCase($casenumber,$directory = false){
		if($directory == false){
			return $this->getLive($casenumber);
		} else{
			//Means directory is set so the case should be pulled from a file in that directory.
			return $this->getFromFile($casenumber,$directory);
		}
		
	}
	public function getLive($casenumber){
		//Actually pulls the case live from case search.
		$this->casenumber = $casenumber;
		$data = array('caseId'=>$this->casenumber,'action'=>'Get Case','locationCode'=>'DC');
		$ch = curl_init('http://casesearch.courts.state.md.us/casesearch/inquiryByCaseNum.jis');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: JSESSIONID=" . trim($this->JSession)));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		
		$this->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($this->httpcode != 200){
			//Something went wrong like case search down etc.
			return 100;
		} else{
			//At least a website was returned.
			$this->html = $result;
			$title = trim($this->getTitle($result));
			//echo "HTML Length: " . strlen($result);
			if($title == 'Case Information' || $title == 'Case Header Information'){
				return 200; //Case Found
			} else if($title == 'Maryland Judiciary Case Search Criteria'){
				return 300; //Case Not Found
			} else if($title == 'Maryland Judiciary Case Search Disclaimer'){
				return 500; //We are back at the disclaimer screen.
			}else if(strlen($result) < 5){
				return 400; //Case is blank data 010100197112010
			}else{
				return 600; //Unknown error
			}
		}
	}
	public function getFromFile($casenumber,$directory){
		if(file_exists($directory . $casenumber . ".html")){
			$returndata = file_get_contents($directory . $casenumber . ".html");
			if($returndata == null || empty($returndata)){
				$this->html = $returndata;
				return 400;
			}
			else{
				$this->html = $returndata;
				return 200;
			}
		}
		else{
			//File Doesn't exist
			$this->html = '';
			return 300;
		}
	}
	public function getHTML(){
		return $this->html;
	}
	public function getTitle($html){
		//Gets the title of the page to see if the case was found or not.
		 $res = preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches);
        if (!$res) 
            return null; 

        $title = preg_replace('/\s+/', ' ', $title_matches[1]);
        $title = trim($title);
		return $title;
	}
	public static function getJSessionCookie(){
		//The purpose of this is to get the JSESSIONID from case search. This way we only have to get 1 session id rather then getting a new one every time.
		$fields_string ='';
		$data = array("disclaimer"=>urlencode("Y"),"action"=>urlencode("Continue"));
		foreach($data as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string, '&');
		
		$ch = curl_init('http://casesearch.courts.state.md.us/casesearch/');
		curl_setopt($ch,CURLOPT_POST, count($data));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		$cookies = array();
		
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		curl_close($ch);
		if(isset($cookies['JSESSIONID'])) return $cookies['JSESSIONID'];
		else throw new Exception("SessionID Not Found");
		
	}
}