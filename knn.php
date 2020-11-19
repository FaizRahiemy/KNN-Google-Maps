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
        
		$tabel = '<br><b>Jarak ke BCA dari Lokasi Awal</b><br><br>';  
		$tabel .= '<table style="width:100%">  
				<tr><th>BCA</th><th>Jarak</th></tr>'; 
        
        $alldata = array();
        foreach($dbh->query($querybca) as $bca){
            $data = [
                'name' => $bca['nama'],
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
        
        for($i = 0; $i < 5; $i++){
            $isidata = $alldata[$i];
            $tabel .= '  
                <tr>  
                     <td style="width:60%">'.$isidata['name'].'</td>  
                     <td style="width:40%">'.number_format($isidata['jarak'],2).' meter'.'</td>  
                </tr>  
           ';
        }
        
        $tabel .= '</table>';
		echo $tabel;
	} else {
		header("location: index.php");
	}
?>