<?php

declare(strict_types=1);

    include_once __DIR__ . '/../libs/WebOAuthModule.php';

    class GardenaCloud extends WebOAuthModule
    {
        const SMART_SYSTEM_BASE_URL = 'https://oauth.symcon.cloud/proxy/gardena/v1';
        const AUTOMOVER_CONNECT_SYSTEM_BASE_URL = 'https://api.amc.husqvarna.dev/v1';
        const APIKEY = 'b42b22bf-5482-4f0b-b78a-9c5558ff5b4a';
        private const LOCATIONS = '/locations/';
        private const WEBSOCKET = '/websocket';

        //This one needs to be available on our OAuth client backend.
        //Please contact us to register for an identifier: https://www.symcon.de/kontakt/#OAuth
        private $oauthIdentifer = 'husqvarna';
        //private $oauthIdentifer = "test_staging";

        //You normally do not need to change this
        private $oauthServer = 'oauth.ipmagic.de';
        //private $oauthServer = "oauth.symcon.cloud";

        public function __construct($InstanceID)
        {
            parent::__construct($InstanceID, $this->oauthIdentifer);
        }

        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterAttributeString('Token', '');
            $this->RegisterAttributeString('LocationID', '');

            $this->RequireParent('{D68FD31F-0E90-7019-F16C-1949BD3079EF}');

            $this->RegisterTimer('PingWebsocket', (120 * 1000), 'GARDENA_Ping($_IPS[\'TARGET\']);');
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
        }

        /**
         * This function will be called by the register button on the property page!
         */
        public function Register()
        {

            //Return everything which will open the browser
            return 'https://' . $this->oauthServer . '/authorize/' . $this->oauthIdentifer . '?username=' . urlencode(IPS_GetLicensee());
        }

        public function ReceiveData($JSONString)
        {
            $data = json_decode($JSONString);
            $payload = $data->Buffer;
            $this->SendDebug('Receive Gardena Websocket Payload', $payload, 0);
            if ($payload != '[]') {
                $this->SendDataToChildren(json_encode(['DataID' => '{56245A2E-9937-C486-B7C0-DC30275EEDF6}', 'Buffer' => $payload]));
                $this->SendDataToChildren(json_encode(['DataID' => '{B5A046FC-74AB-FB16-202F-A8D388E7D5CC}', 'Buffer' => $payload]));
                $this->SendDataToChildren(json_encode(['DataID' => '{AE256F37-CF77-34BB-E45D-B51DB6CBF640}', 'Buffer' => $payload]));
                $this->SendDataToChildren(json_encode(['DataID' => '{6ECF55BE-818E-DC70-43C3-17C32F10E119}', 'Buffer' => $payload]));
                $this->SendDataToChildren(json_encode(['DataID' => '{6C8B381C-8A2B-1247-56AE-7E149E75FB9C}', 'Buffer' => $payload]));
            }
        }

        public function ForwardData($data)
        {
            $action = json_decode($data, true)['RequestData'];
            switch ($action) {
                case 'snapshot':
                    $snapshot = $this->GetData(self::SMART_SYSTEM_BASE_URL . self::LOCATIONS . $this->ReadAttributeString('LocationID'));
                    $this->SendDebug('Snapshot', json_encode($snapshot), 0);

                    return $snapshot;

                default:
                    return false;

            }
        }

        public function Ping()
        {
            $this->SendDataToParent(json_encode([
                'DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}',
                'Buffer' => utf8_encode('Ping'),
            ]));
        }

        public function RequestStatus()
        {
            echo $this->GetData('https://' . $this->oauthServer . '/forward');
        }

        public function FetchDevices()
        {
            $this->FetchLocation();
            $this->FetchWebSocketUrl();
        }

        /**
         * This function will be called by the OAuth control. Visibility should be protected!
         */
        protected function ProcessOAuthData()
        {

            //Lets assume requests via GET are for code exchange. This might not fit your needs!
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                if (!isset($_GET['code'])) {
                    die('Authorization Code expected');
                }

                $token = $this->FetchRefreshToken($_GET['code']);

                $this->SendDebug('ProcessOAuthData', "OK! Let's save the Refresh Token permanently", 0);

                $this->WriteAttributeString('Token', $token);
                $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($token, 0, 16) . '...');
            } else {

                //Just print raw post data!
                echo file_get_contents('php://input');
            }
        }

        private function FetchRefreshToken($code)
        {
            $this->SendDebug('FetchRefreshToken', 'Use Authentication Code to get our precious Refresh Token!', 0);

            //Exchange our Authentication Code for a permanent Refresh Token and a temporary Access Token
            $options = [
                'http' => [
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query(['code' => $code])
                ]
            ];
            $context = stream_context_create($options);
            $result = file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifer, false, $context);

            $data = json_decode($result);

            if (!isset($data->token_type) || $data->token_type != 'Bearer') {
                die('Bearer Token expected');
            }

            //Save temporary access token
            $this->FetchAccessToken($data->access_token, time() + $data->expires_in);

            //Return RefreshToken
            return $data->refresh_token;
        }

        private function FetchAccessToken($Token = '', $Expires = 0)
        {

            //Exchange our Refresh Token for a temporary Access Token
            if ($Token == '' && $Expires == 0) {

                //Check if we already have a valid Token in cache
                $data = $this->GetBuffer('AccessToken');
                if ($data != '') {
                    $data = json_decode($data);
                    if (time() < $data->Expires) {
                        $this->SendDebug('FetchAccessToken', 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $data->Expires), 0);
                        return $data->Token;
                    }
                }

                $this->SendDebug('FetchAccessToken', 'Use Refresh Token to get new Access Token!', 0);

                //If we slipped here we need to fetch the access token
                $options = [
                    'http' => [
                        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query(['refresh_token' => $this->ReadAttributeString('Token')])
                    ]
                ];
                $context = stream_context_create($options);
                $result = file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifer, false, $context);

                $data = json_decode($result);

                if (!isset($data->token_type) || $data->token_type != 'Bearer') {
                    die('Bearer Token expected');
                }

                //Update parameters to properly cache it in the next step
                $Token = $data->access_token;
                $Expires = time() + $data->expires_in;

                //Update Refresh Token if we received one! (This is optional)
                if (isset($data->refresh_token)) {
                    $this->SendDebug('FetchAccessToken', "NEW! Let's save the updated Refresh Token permanently", 0);

                    $this->WriteAttributeString('Token', $data->refresh_token);
                    $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($data->refresh_token, 0, 16) . '...');
                }
            }

            $this->SendDebug('FetchAccessToken', 'CACHE! New Access Token is valid until ' . date('d.m.y H:i:s', $Expires), 0);

            //Save current Token
            $this->SetBuffer('AccessToken', json_encode(['Token' => $Token, 'Expires' => $Expires]));

            //Return current Token
            return $Token;
        }

        private function GetData($url)
        {
            $opts = [
                'http'=> [
                    'method' => 'GET',
                    'header' => 'Authorization: Bearer ' . $this->FetchAccessToken() . "\r\n" .
                    "Authorization-Provider: husqvarna\r\nX-Api-Key: " . self::APIKEY . "\r\n",
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);

            $result = file_get_contents($url, false, $context);

            if ((strpos($http_response_header[0], '200') === false)) {
                echo $http_response_header[0] . PHP_EOL . $result;
                return false;
            }

            return $result;
        }

        private function PostData($url, $content)
        {
            $opts = [
                'http'=> [
                    'method' => 'POST',
                    'header' => 'Authorization: Bearer ' . $this->FetchAccessToken() . "\r\n" .
                    "Authorization-Provider: husqvarna\r\nX-Api-Key: " . self::APIKEY . "\r\n" .
                    'Content-Length: ' . strlen($content) . "\r\n" .
                    'Content-Type: application/vnd.api+json' . "\r\n",
                    'content'       => $content,
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);

            $result = file_get_contents($url, false, $context);

            if ((strpos($http_response_header[0], '201') === false)) {
                echo $http_response_header[0] . PHP_EOL . $result;
                return false;
            }

            return $result;
        }

        private function FetchLocation()
        {
            $location_data = json_decode($this->GetData(self::SMART_SYSTEM_BASE_URL . self::LOCATIONS), true);
            if ($location_data) {
                $this->SendDebug('location', json_encode($location_data), 0);
                $this->WriteAttributeString('LocationID', $location_data['data'][0]['id']);
            }
        }

        private function FetchWebSocketUrl()
        {
            $payload = json_encode(
                [
                    'data' => [
                        'id'        => 'does-not-matter',
                        'type'      => 'WEBSOCKET',
                        'attributes'=> [
                            'locationId'=> $this->ReadAttributeString('LocationID')
                        ]
                    ]
                ]);

            $response = json_decode($this->PostData(self::SMART_SYSTEM_BASE_URL . self::WEBSOCKET, $payload), true);
            if ($response) {
                $url = $response['data']['attributes']['url'];
                $this->SendDebug('websocket', $url, 0);
                $parent = $ParentID = @IPS_GetInstance($this->InstanceID)['ConnectionID'];
                IPS_SetProperty($parent, 'URL', $url);
                IPS_SetProperty($parent, 'Active', true);
                IPS_ApplyChanges($parent);
            }
        }
    }