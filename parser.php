<?php

$config = parse_ini_file('config.ini');

$parser = new Parser($config['jobs'], $config['jenkinsUser'], $config['jenkinsApiToken']);
$parser->run();

/**
 * @author Pavel_Dzemidziuk
 *
 */
class Parser
{
	/**
	 * @var string
	 */
	const URL_PATTERN = "http://hudson.mtvi.com/hudson/job/%s/lastCompletedBuild/testReport/api/json";
	
	/**
	 * Timeout in seconds
	 * 
	 * @var int
	 */
	const TIMEOUT = 10;
	
	/**
	 * Login.
	 *
	 * @var string
	 */
	protected $jenkinsUser;
	
	/**
	 * This on is specific for each user.
	 *
	 * @var string
	 */
	protected $jenkinsApiToken;
	
	/**
	 * @var array
	 */
	protected $jobs = array();
	
	/**
	 * @param array $jenkinsJobNames
	 */
	public function __construct(array $jenkinsJobNames, $jenkinsUser, $jenkinsApiToken)
	{
		$this->jobs = $jenkinsJobNames;
		$this->jenkinsUser = $jenkinsUser;
		$this->jenkinsApiToken = $jenkinsApiToken;
	}
	
	/**
	 * Run TL update  
	 */
	public function run()
	{
		$this->fetchJobStatuses();
	}
	
	/**
	 * Fetches Jenkin Jobs Statuses. 
	 */
	protected function fetchJobStatuses()
	{
		$path = true;
		foreach($this->jobs as $jobName)
		{
			$result = $this->getJenkinsContent(sprintf(static::URL_PATTERN, $jobName));
			
			if (empty($result))
			{
				// connetcion problems or stuff like that
				$this->turnOnYellow();
				return;
			}
			
			if (!$this->isSuccessBuild($result))
			{
				// one of the builds is failed
				$this->turnOnRed();
				return;
			}
		}
		// everything is fine
		$this->turnOnGreen();
	}
	
	/**
	 * Build successfull only if there are no fails in tests.
	 * 
	 * @param string $content
	 * @return boolean
	 */
	protected function isSuccessBuild($content)
	{
		$content = json_decode($content);
		if (isset($content->failCount) && $content->failCount == 0)
		{
			return true;
		}
		
		return false;		
	}
	
	/**
	 * @param string $url
	 * @return string $response
	 */
	protected function getJenkinsContent($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/jenkins_cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/jenkins_cookie.txt");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->jenkinsUser . ':' . static::$this->jenkinsApiToken);
	
		$response = curl_exec($ch);
	
		if (curl_error($ch))
		{
			// something goes wrong
			print_r(curl_error($ch));
			return false;
		}
	
		curl_close($ch);
		return $response;
	}
	
	/**
	 * Turns on green light on.
	 */
	protected function turnOnGreen()
	{
		echo "\r\n	Green is on \r\n";
		
		passthru("sudo ./lights/green/on");
	}
	
	/**
	 * Turns yellow light on.
	 */
	protected function turnOnYellow()
	{
		echo "\r\n	Yellow is on \r\n";
		
		// this is not mistake, we turning off yellow when we need it shine.
		// it's feature of connetcing yellow
		passthru("sudo ./lights/yellow/off");
	}
	
	/**
	 * Turns red light on.
	 */
	protected function turnOnRed()
	{
		echo "\r\n	Red is on \r\n";
		
		passthru("sudo ./lights/red/on");
	}
}