<?php
	function distance($lat1, $long1, $lat2, $long2){
		$hasil = sqrt(pow(($long1-$long2),2)+pow(($lat1-$lat2),2))*111.319*1000;
		return $hasil;
	}
	if(isset($_POST["latAwal"]) && isset($_POST["lngAwal"])){
		$latAwal = $_POST["latAwal"];
        $lngAwal = $_POST["lngAwal"];
        $dir = 'sqlite:db.db';
        $dbh = new PDO($dir) or die("Cannot open the database");
        $querybca = "SELECT * FROM bca";
        
        $alldata = array();
        foreach($dbh->query($querybca) as $bca){
            $data = [
                'name' => $bca['nama'],
                'lat' => $bca['lat'], 
                'long' => $bca['long'],
                'jarak' => distance($latAwal, $lngAwal, $bca['lat'], $bca['long'])
            ];
            $alldata[] = $data;
        }
        
		$value = array();
		foreach ($alldata as $key => $row)
		{
	    	$value[$key] = $row['jarak'];
		}
		array_multisort($value, SORT_ASC, $alldata);
        
        $kirimdata = array();
        for($i = 0; $i < 5; $i++){
            $kirimdata[] = $alldata[$i];
        }
        
        $output = array(
            'bca' => $kirimdata
         );
		echo json_encode($output); 
	} else {
		header("location: index.php");
	}
?>