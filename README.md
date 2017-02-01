# MDCaseSearchRobot
Scrapes the HTML from Maryland Judiciary Case Search
http://casesearch.courts.state.md.us

##Usage
The purpose of this class is to scrape MD Judiciary Case Search by case number.
You first start by getting the JSESSIONID from Case Search. This session id allows you to bypass the checkbox.
The reason you first get the sessionid is so you can give the same sessionid each time. This cuts down the number of requests the
program has to make if you are making a lot of them. i.e. pulling all Balt City cases.
<pre>
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
</pre>
