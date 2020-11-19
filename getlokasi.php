<?php
	function distance($lat1, $long1, $lat2, $long2){
		$hasil = sqrt(pow(($long1-$long2),2)+pow(($lat1-$lat2),2))*111.319*1000;
		return $hasil;
	}
	if(isset($_POST["titikawal"])){
		$titikawal = $_POST["titikawal"];
        $dir = 'sqlite:db.db';
        $dbh = new PDO($dir) or die("Cannot open the database");
        $query = "SELECT * FROM lokasiawal WHERE id=".$titikawal;
        
        $getLokasi = $dbh->prepare($query);
        $getLokasi->execute();
        $lokasi = $getLokasi->fetchAll();
        $lokasiawal = $lokasi[0];
        $output = array(
            'nama' => $lokasiawal['nama'],
            'lat' => $lokasiawal['lat'],
            'long' => $lokasiawal['long']
         );
		echo json_encode($output); 
	} else {
		header("location: index.php");
	}
?>