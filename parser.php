<?php

$config = parse_ini_file('config.ini');

$parser = new Parser($config['jenkins_source'], $config['jenkinsUser'], $config['jenkinsApiToken']);
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
	protected $jenkinsSource;
	
	protected $jenkinsUser;
	
	protected $jenkinsApiToken;
	
	/**
	 * Timeout in seconds
	 * 
	 * @var int
	 */
	const TIMEOUT = 10;
	
	/**
	 * @param array $jenkinsSource
	 */
	public function __construct($jenkinsSource, $jenkinsUser, $jenkinsApiToken)
	{
		$this->jenkinsSource = $jenkinsSource;
		$this->jenkinsUser = $jenkinsUser;
		$this->jenkinsApiToken = $jenkinsApiToken;
		
		$this->jobs = $this->fetchJobs();
			
		if (empty($this->jobs))
		{
			$this->turnOnYellow();
			exit('No jobs found');
		}
	}
	
	/**
	 * 
	 */
	protected function fetchJobs()
	{
		$content = json_decode($this->getJenkinsContent($this->jenkinsSource));
		
		if (!empty($content->jobs))
		{
			return $content->jobs;
		}
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
		foreach($this->jobs as $job)
		{
			if ($job->color != 'green' && $job->color != 'blue' && $job->color != 'blue_anime')
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
		return json_decode($content)->result == 'SUCCESS'; 
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
		curl_setopt($ch, CURLOPT_USERPWD, $this->jenkinsUser . ':' . $this->jenkinsApiToken);
	
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
		passthru("sudo ./lights/yellow/on");
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