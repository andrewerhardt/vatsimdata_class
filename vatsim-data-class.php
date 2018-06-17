<?php
class vatsimData{
	private static $_Instance = null;

	private static $_Servers;
	private static $_File;
	private static $_Data;
	private static $_DataDir;

	public function __construct(){
		self::$_DataDir = (isset($vsd['DataDir']) === false) ? dirname(__FILE__) : $vsd['DataDir'];

		$Now            = time();
		$ServerFileTime = (file_exists(self::$_DataDir . "/servers.json")) ? filemtime(self::$_DataDir . "/servers.json") : '0';
		$ServerFileAge  = $Now - $ServerFileTime;
		$DataFileTime   = (file_exists(self::$_DataDir . "/vatsimdata.txt")) ? filemtime(self::$_DataDir . "/servers.json") : '0';
		$DataFileAge    = $Now - $DataFileTime;

		if(file_exists(self::$_DataDir . "/servers.json") === false || $ServerFileAge > "2592000"){
			self::DataServerList();
		}

		if(file_exists(self::$_DataDir . "/vatsimdata.txt") === false || $DataFileAge > 120){
			$Data = self::DownloadData();
			if(!self::WriteDataFile($Data)){
				throw new Exception('Unable to write the Data File!');
			}
		}

	}

	public static function get(){
		if(is_null(self::$_Instance)){
			self::$_Instance = new vatsimData();
		}
		return self::$_Instance;
	}

	private static function DataServerList(){
		
		$ServerFile = fopen(self::$_DataDir . "/servers.json", "w+");
		$Data       = file_get_contents("http://status.vatsim.net/status.txt");
		$Find       = "url0=";
		$LastPos    = 0;
		$Positions  = array();
		$Urls       = array();

		while(($LastPos  = strpos($Data, $Find, $LastPos)) !== false){
			$Positions[] = $LastPos;
			$LastPos     = $LastPos + strlen($Find);
		}

		foreach($Positions as $Value){
			$End    = strpos($Data, "\n", $Value);
			$Length = $End - $Value;

			$Url = substr($Data, $Value, $Length);
			$Url = explode("=", $Url);
			$Url = trim(preg_replace('/\s\s+/', ' ', $Url[1]));

			$Urls[] = $Url;
		}

		$Urls = json_encode($Urls, JSON_UNESCAPED_SLASHES);

		file_put_contents(self::$_DataDir . "/servers.json", $Urls);
		
	}

	private static function DownloadData(){
		self::$_Servers = json_decode(file_get_contents(self::$_DataDir . "/servers.json"), true);

		$Server = self::$_Servers[array_rand(self::$_Servers)];
		$Data = file_get_contents($Server);

		return $Data;
	}

	private static function WriteDataFile($Data){
		self::$_File = fopen(self::$_DataDir . "/vatsimdata.txt", "w+");

		return fwrite(self::$_File, $Data);
	}

	private static function ReadDataFile(){
		$Data = file_get_contents(self::$_DataDir . "/vatsimdata.txt");

		return $Data;
	}

	private static function PointInPoly($poly, $lat, $lng){
	   $c = false;
	   $npol = count($poly);
	   for ($i = 0, $j = $npol-1; $i < $npol; $j = $i++) {
	     if (((($poly[$i][0]<=$lat) && ($lat<$poly[$j][0])) ||
	                   (($poly[$j][0]<=$lat) && ($lat<$poly[$i][0]))) &&
	                   ($lng < ($poly[$j][1] - $poly[$i][1]) *
	                   ($lat - $poly[$i][0]) / ($poly[$j][0] - $poly[$i][0]) +
	                   $poly[$i][1]))
	        $c = !$c;
	    }
	   return $c;
	}

	private function ClientList(){
		$Data         = self::ReadDataFile();
		$Length 	  = strlen("!CLIENTS:");
		$ClientsStart = strpos($Data, "!CLIENTS:");
		$ClientsEnd   = strpos($Data, ";\n", $ClientsStart);
		$StringLength = $ClientsEnd - $ClientsStart;

		$Clients      = substr($Data, $ClientsStart, $StringLength);
		$Clients      = str_replace("!CLIENTS:", "", $Clients);
		$Clients      = explode("\n", $Clients);
		$Clients      = array_filter($Clients); 

		foreach($Clients as $Client){
			$ClientData = explode(":", $Client);

			$ClientList[] = array(
				'callsign'                => $ClientData[0],
				'cid'                     => $ClientData[1],
				'realname'                => $ClientData[2],
				'clienttype'              => $ClientData[3],
				'frequency'               => $ClientData[4],
				'latitude'                => $ClientData[5],
				'longitude'               => $ClientData[6],
				'altitude'                => $ClientData[7],
				'groundspeed'             => $ClientData[8],
				'planned_aircraft'        => $ClientData[9],
				'planned_tascruise'       => $ClientData[10],
				'planned_depairport'      => $ClientData[11],
				'planned_altitude'        => $ClientData[12],
				'planned_destairport'     => $ClientData[13],
				'server'                  => $ClientData[14],
				'protrevision'            => $ClientData[15],
				'rating'                  => $ClientData[16],
				'transponder'             => $ClientData[17],
				'facilitytype'            => $ClientData[18],
				'visualrange'             => $ClientData[19],
				'planned_revision'        => $ClientData[20],
				'planned_flighttype'      => $ClientData[21],
				'planned_deptime'         => $ClientData[22],
				'planned_actdeptime'      => $ClientData[23],
				'planned_hrsenroute'      => $ClientData[24],
				'planned_minenroute'      => $ClientData[25],
				'planned_hrsfuel'         => $ClientData[26],
				'planned_minfuel'         => $ClientData[27],
				'planned_altairport'      => $ClientData[28],
				'planned_remarks'         => $ClientData[29],
				'planned_route'           => $ClientData[30],
				'planned_depairport_lat'  => $ClientData[31],
				'planned_depairprot_lon'  => $ClientData[32],
				'planned_destairport_lat' => $ClientData[33],
				'planned_destairport_lon' => $ClientData[34],
				'atis_message'            => $ClientData[35],
				'time_last_atis_received' => $ClientData[36],
				'time_logon'              => $ClientData[37],
				'heading'                 => $ClientData[38],
				'QNH_iHg'                 => $ClientData[39],
				'QNH_Mb'                  => $ClientData[40]
				);
		}

	return $ClientList;
	}

	public function OnlinePilots(){
		$Clients = self::ClientList();
		foreach($Clients as $Client){
			if($Client['clienttype'] == "PILOT"){
				$OnlinePilots[] = $Client;
			}
		}
		return $OnlinePilots;
	}

	public function OnlineControllers(){
		$Clients = self::ClientList();

		foreach($Clients as $Client){
			if($Client['clienttype'] == "ATC"){
				$OnlineControllers[] = $Client;
			}
		}
		return $OnlineControllers;
	}

	public function AirspaceOperations($boundries = ""){
		$Pilots = self::OnlinePilots();
		
		foreach($Pilots as $Pilot){
			if(self::PointInPoly($boundries, $Pilot['latitude'], $Pilot['longitude'])){
				$AirspaceOperations[] = $Pilot;
			}
		}

		return (!empty($AirspaceOperations)) ? $AirspaceOperations : false;
	}

	public function FindClientCid($cid){
		$Clients = self::ClientList();

		foreach($Clients as $Client){
			if($Client['cid'] == $cid){
				return $Client;
			}
		}
		return false;
	}

	public function FindClientCallsign($callsign){
		$Clients = self::ClientList();

		foreach($Clients as $Client){
			if($Client['callsign'] == $callsign){
				return $Client;
			}
		}
		return false;
	}

	public function FindClientsAirline($AirlineICAO){
		$Pilots = self::ClientList();

		foreach($Pilots as $Pilot){
			preg_match("/[a-zA-Z{1,3}", $Pilot['callsign'], $PilotAirline);

			if($PilotAirline['0'] == $AirlineICAO){
				$PilotList[] = $Pilot;
			}
		}

		return (empty($PilotList) === false) ? $PilotList : false;
	}

	public function AirportDepartures($AirportICAO){
		$Pilots = self::OnlinePilots();

		foreach($Pilots as $Pilot){
			if($Pilot['planned_depairport'] == $AirportICAO){
				$PilotList[] = $Pilot;
			}
		}

		return (empty($PilotList) === false) ? $PilotList : false;
	}

	public function AirportArrivals($AirportICAO){
		$Pilots = self::OnlinePilots();

		foreach($Pilots as $Pilot){
			if($Pilot['planned_destairport'] == $AirportICAO){
				$ClientList[] = $Pilot;
			}
		}

		return (empty($PilotList) === false) ? $PilotList : false;
	}
}
?>