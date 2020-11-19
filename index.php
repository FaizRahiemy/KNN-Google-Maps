<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
        <meta charset="utf-8">
        <title>Tugas Besar Basis Data Spatial</title>
        <link href="style.css" type="text/css" rel="stylesheet">
        <script src="js/jquery-3.1.1.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/d3/3.5.5/d3.min.js"></script>
    </head>
    <body>
        <?php
            // konek database
            $dir = 'sqlite:db.db'; //lokasi db
            $dbh = new PDO($dir) or die("Cannot open the database"); //konek
            $querybca = "SELECT * FROM bca"; //data yang dicari (bca)
        ?>
        <script>
            var map; //variabel map
            var markers = []; //variabel titik
            var lokasiAwal;
            var lokasiTujuan;
            var path = [];
            var lokasiAwalArray = [];
            var latAwal = null;
            var lngAwal = null;
            function searchBCA(){ //fungsi search (dipanggil pas pencet search)
                if (lokasiAwalArray.length == 0){
                    alert("Tentukan lokasi awal terlebih dahulu!");
                }else{
                    if (markers) {
                        for (i=0; i < markers.length; i++) {
                            delmarker = new google.maps.Marker({
                                position: markers[i].position
                            })
                            delmarker.setMap(null)
                        }
                        markers.length = 0;
                    }
                    $.ajax({
                        url:'knn.php',
                        data : {latAwal:latAwal,
                               lngAwal:lngAwal},
                        type : 'POST',
                        success : function(data){
                            if (!data.error){
                                $('#tabel').show();
                                $('#tabel').html(data);
                            }
                        }
                    })
                    map = new google.maps.Map(document.getElementById('map'), {
                        center: {lat: -6.916946, lng: 107.600543},
                        zoom: 14
                    });
                    var iconAwal = 'images/car.png';
                    var marker = new google.maps.Marker({
                        position : new google.maps.LatLng(latAwal,lngAwal),
                        icon : iconAwal
                    });
                    marker.setMap(map);
                    markers.push(marker);
                    lokasiAwal = marker.position;
                    path.push(lokasiAwal);
                    $.ajax({
                        url:'getbca.php',
                        type : 'POST',
                        data : {latAwal:latAwal,
                               lngAwal:lngAwal},
                        dataType : 'json',
                        success : function (result) {
                            for (i = 0; i < result['bca'].length; i++) {
                                var iconBca = 'images/flag.png';
                                var marker = new google.maps.Marker({
                                    position : new google.maps.LatLng(result['bca'][i]['lat'],result['bca'][i]['long']),
                                    label : result['bca'][i]['name'],
                                    icon : iconBca
                                });
                                markers.push(marker);
                                marker.setMap(map);
                                if (i == 0){
                                    lokasiTujuan = marker.position;
                                    path.push(lokasiTujuan);
                                }
                            }

                            var bca = [
                                <?php
                                foreach ($dbh->query($querybca) as $lokasi){
                                ?>
                                {
                                    position: new google.maps.LatLng(<?php echo $lokasi['lat'].",".$lokasi['long'] ?>),
                                    label : "<?php echo $lokasi['nama'] ?>"
                                },
                                <?php
                                }
                                ?>
                            ];
    //                        for (i = 0; i < path.length; i++){
    //                            alert(path[i]);
    //                        }
                            lokasiAwal = new google.maps.LatLng(latAwal,lngAwal);
                            var directionsService = new google.maps.DirectionsService;
                            var directionsDisplay = new google.maps.DirectionsRenderer;
                            directionsService.route({
                              origin: lokasiAwal,
                              destination: lokasiTujuan,
                              optimizeWaypoints: true,
                              travelMode: 'DRIVING'
                            }, function(response, status) {
                              if (status === 'OK') {
                                directionsDisplay.setDirections(response);
                                var route = response.routes[0];
                              } else {
                                alert('Directions request failed due to ' + status);
                              }
                            });
                            directionsDisplay.setMap(map);

                            var overlay = new google.maps.OverlayView(); //OverLayオブジェクトの作成

                            //オーバレイ追加
                            overlay.onAdd = function () {

                                var layer = d3.select(this.getPanes().overlayLayer).append("div").attr("class", "SvgOverlay");
                                var svg = layer.append("svg");
                                var svgoverlay = svg.append("g").attr("class", "AdminDivisions");

                                //再描画時に呼ばれるコールバック    
                                overlay.draw = function () {
                                    var markerOverlay = this;
                                    var overlayProjection = markerOverlay.getProjection();

                                    //Google Mapの投影法設定
                                    var googleMapProjection = function (coordinates) {
                                        var googleCoordinates = coordinates.position;
                                        var pixelCoordinates = overlayProjection.fromLatLngToDivPixel(googleCoordinates);
                                        return [pixelCoordinates.x + 4000, pixelCoordinates.y + 4000];
                                    }

                                    //母点位置情報

                                    //ピクセルポジション情報
                                    var positions = [];

                                    bca.forEach(function(d) {		
                                        positions.push(googleMapProjection(d)); //位置情報→ピクセル
                                    });

                                    //ボロノイ変換関数
                                    var polygons = d3.geom.voronoi(positions);

                                    var pathAttr ={
                                        "d":function(d, i) { return "M" + polygons[i].join("L") + "Z"},
                                        stroke:"#ff75d7",
                                        fill:"none"			
                                    };

                                    //境界表示
                                    svgoverlay.selectAll("path")
                                        .data(positions)
                                        .attr(pathAttr)
                                        .enter()
                                        .append("svg:path")
                                        .attr("class", "cell")
                                        .attr(pathAttr)

                                    var circleAttr = {
                                            "cx":function(d, i) { return positions[i][0]; },
                                            "cy":function(d, i) { return positions[i][1]; },
                                            "r":2,
                                            fill:"red"			
                                    }

                                    //母点表示
                                    svgoverlay.selectAll("circle")
                                        .data(positions)
                                        .attr(circleAttr)
                                        .enter()
                                        .append("svg:circle")
                                        .attr(circleAttr)

                                };

                            };

                            //作成したSVGを地図にオーバーレイする
                            overlay.setMap(map);
                        },
                        error : function () {
                           alert("error");
                        }
                    })
                }
            };
        </script>
        <div id="map">
        </div>
        <div id="menu">
            <div id="title">Tugas Besar<br>Basis Data Spatial</div>
            <b>Lokasi awal:</b>
            <div id="lokasiawal">
                Latitude : -<br>
                Longitude : -
            </div><br>
            <button type="button" onClick="searchBCA();">Cari BCA Terdekat</button><button type="button" onClick="initMap();">Reset</button><br>
            <div id="tabel">
            </div>
        </div> 
        <script>
            var myStyle = [{
                 featureType: "poi",
                 elementType: "labels",
                 stylers: [
                   { visibility: "off" }
                 ]
               }
            ];
            function initMap() {
                lokasiAwalArray.length = 0;
                document.getElementById("lokasiawal").innerHTML = "Latitude : -<br>Longitude : -<br>Silahkan klik di map untuk menentukan lokasi awal!";
                document.getElementById("tabel").innerHTML = "";
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {lat: -6.916946, lng: 107.600543},
                    zoom: 14
                });

                map.set('styles', myStyle);

                var bca = [
                    <?php
                    foreach ($dbh->query($querybca) as $lokasi){
                    ?>
                    {
                        position: new google.maps.LatLng(<?php echo $lokasi['lat'].",".$lokasi['long'] ?>),
                        label : "<?php echo $lokasi['nama'] ?>"
                    },
                    <?php
                    }
                    ?>
                ];

                bca.forEach(function(feature){
                    var iconBca = 'images/flag.png';
                    var marker = new google.maps.Marker({
                        position : feature.position,
                        icon : iconBca,
                        label : feature.label,
                        map : map
                    });
                    markers.push(marker);
                });

                var overlay = new google.maps.OverlayView(); //OverLayオブジェクトの作成

                //オーバレイ追加
                overlay.onAdd = function () {

                    var layer = d3.select(this.getPanes().overlayLayer).append("div").attr("class", "SvgOverlay");
                    var svg = layer.append("svg");
                    var svgoverlay = svg.append("g").attr("class", "AdminDivisions");

                    //再描画時に呼ばれるコールバック    
                    overlay.draw = function () {
                        var markerOverlay = this;
                        var overlayProjection = markerOverlay.getProjection();

                        //Google Mapの投影法設定
                        var googleMapProjection = function (coordinates) {
                            var googleCoordinates = coordinates.position;
                            var pixelCoordinates = overlayProjection.fromLatLngToDivPixel(googleCoordinates);
                            return [pixelCoordinates.x + 4000, pixelCoordinates.y + 4000];
                        }

                        //母点位置情報

                        //ピクセルポジション情報
                        var positions = [];

                        bca.forEach(function(d) {		
                            positions.push(googleMapProjection(d)); //位置情報→ピクセル
                        });

                        //ボロノイ変換関数
                        var polygons = d3.geom.voronoi(positions);

                        var pathAttr ={
                            "d":function(d, i) { return "M" + polygons[i].join("L") + "Z"},
                            stroke:"#ff75d7",
                            fill:"none"			
                        };

                        //境界表示
                        svgoverlay.selectAll("path")
                            .data(positions)
                            .attr(pathAttr)
                            .enter()
                            .append("svg:path")
                            .attr("class", "cell")
                            .attr(pathAttr)

                    };

                };

                //作成したSVGを地図にオーバーレイする
                overlay.setMap(map);

                google.maps.event.addListener(map, 'click', function(event) {
                    if (lokasiAwalArray.length == 0) {
                        var iconAwal = 'images/car.png';
                        var position = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());
                        var marker = new google.maps.Marker({
                            position : position,
                            icon : iconAwal,
                            map : map
                        });
                        latAwal = event.latLng.lat();
                        lngAwal = event.latLng.lng()
                        lokasiAwalArray.push(marker);
                        markers.push(marker);
                        document.getElementById("lokasiawal").innerHTML = "Latitude : " + event.latLng.lat() + "<br>Longitude : " + event.latLng.lng();
                    }
                });
            }
        </script>
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCDGk5zgrME8MeUvmTgxCX5oFtDNBcpejs&callback=initMap">
        </script>
    </body>
</html>