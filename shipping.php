<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://webservices.directfreight.com.au/Dispatch/api/GetConsignmentPrice",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>"{\"SuburbFrom\":\"MELBOURNE\",\"PostcodeFrom\":\"3000\",\"SuburbTo\":\"SYDNEY\",\"PostcodeTo\":\"2000\",\"ConsignmentLineItems\":[{\"RateType\":\"ITEM\",\"Items\":4,\"Kgs\":1,\"Length\":12,\"Width\":25,\"Height\":20,\"Cubic\":0},{\"RateType\":\"PALLET\",\"Items\":1,\"Kgs\":320,\"Length\":0,\"Width\":0,\"Height\":0,\"Cubic\":2.16}]}",
  CURLOPT_HTTPHEADER => array(
    "Authorisation: 977998B6-48FB-4AB0-8D4D-AEB641906C0E",
    "AccountNumber: 21483",
    "ContentType: application/json",
    "Accept: application/json",
    "Timeout: 120000",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
?>