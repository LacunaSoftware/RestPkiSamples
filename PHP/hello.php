<?php

require 'RestPkiClient.php';

use Lacuna\RestPkiClient;

$client = new RestPkiClient('https://restpkibeta.azurewebsites.net/', '2vb81ErlUvA6skS_qdUw2Mw_WTCHawH5qYZKfGdGHR0u1yE9uBxIUeQ4FVwKSi1AODP9MpipM3L31RjHoveLB6gogVQnpBdD89q2AnJYrppCXLFoL2KJyf95S8AMYls8_vEJTZbvMtf4kS2KWheobWhdORzxfvZeEv0N-GI-wKB9lwHaj2m6gLB_7Al49DtXmFBw33HCGpDuk84D_ASKCLuAX947466vXvfaeUiCnIvAoQFmwZwrOQkAumPCrUpFh4-nmuL5eyre3hQHHqKW7t7FlTcCL4HnwExmEaRrdOddmfHuoUyBy96grXnwa0P4y22W9QV2Rso3FovM5k6AV6H9bfufts1BYUijUVYhtsc01D5BuclN-XGwjIXRq4ENzkseQQ2tPCvYBZDe8olpNJDHf1KYP2NT7uIdW6DboTar2dZh58EI3i6to9rekodG3LIVJX2T1FwxaoeNHt3XZovY3OoJHIPzeFJiu9KUFPqmj65S8hy7dIANMLCxyz8HOFN5AA');
$x = $client->getToken();
echo $x;

// require 'vendor/autoload.php';

// use GuzzleHttp\Client;

// $accessToken = '2vb81ErlUvA6skS_qdUw2Mw_WTCHawH5qYZKfGdGHR0u1yE9uBxIUeQ4FVwKSi1AODP9MpipM3L31RjHoveLB6gogVQnpBdD89q2AnJYrppCXLFoL2KJyf95S8AMYls8_vEJTZbvMtf4kS2KWheobWhdORzxfvZeEv0N-GI-wKB9lwHaj2m6gLB_7Al49DtXmFBw33HCGpDuk84D_ASKCLuAX947466vXvfaeUiCnIvAoQFmwZwrOQkAumPCrUpFh4-nmuL5eyre3hQHHqKW7t7FlTcCL4HnwExmEaRrdOddmfHuoUyBy96grXnwa0P4y22W9QV2Rso3FovM5k6AV6H9bfufts1BYUijUVYhtsc01D5BuclN-XGwjIXRq4ENzkseQQ2tPCvYBZDe8olpNJDHf1KYP2NT7uIdW6DboTar2dZh58EI3i6to9rekodG3LIVJX2T1FwxaoeNHt3XZovY3OoJHIPzeFJiu9KUFPqmj65S8hy7dIANMLCxyz8HOFN5AA';

// $client = new Client([
    // 'base_uri' => 'https://restpkibeta.azurewebsites.net/',
	// 'headers' => [
		// 'Authorization' => 'Bearer ' . $accessToken,
		// 'Accept' => 'application/json'
	// ]
// ]);

// $httpResponse = $client->post('Api/Authentications', [
	// 'json' => [
		// 'securityContextId' => '1b6cdb87-095e-4bb2-8ddb-a33646e6d56a'
	// ]
// ]);
// $response = json_decode($httpResponse->getBody());
// echo $response->token;
?>
