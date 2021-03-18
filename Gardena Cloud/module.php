<?php

declare(strict_types=1);

    include_once __DIR__ . '/../libs/WebOAuthModule.php';

    class GardenaCloud extends WebOAuthModule
    {
        const SMART_SYSTEM_BASE_URL = 'https://oauth.symcon.cloud/proxy/gardena/v1/';

        private $oauthIdentifer = 'husqvarna';

        private $oauthServer = 'oauth.ipmagic.de';

        public function __construct($InstanceID)
        {
            parent::__construct($InstanceID, $this->oauthIdentifer);
        }

        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterAttributeString('Token', '');

            $this->RequireParent('{D68FD31F-0E90-7019-F16C-1949BD3079EF}');

            $this->RegisterTimer('PingWebSocket', (120 * 1000), 'GARDENA_Ping($_IPS[\'TARGET\']);');

            $this->RegisterMessage(IPS_GetInstance($this->InstanceID)['ConnectionID'], IM_CHANGESTATUS);
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            if (!$this->ReadAttributeString('Token')) {
                $this->SetStatus(IS_INACTIVE);
                return;
            }
            $this->SetStatus(IS_ACTIVE);
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
            $this->SendDataToChildren(json_encode(['DataID' => '{56245A2E-9937-C486-B7C0-DC30275EEDF6}', 'Buffer' => $payload]));
        }

        public function ForwardData($Data)
        {
            $endpoint = json_decode($Data, true)['Endpoint'];
            $result = $this->GetData($endpoint);
            $this->SendDebug($endpoint, json_encode($result), 0);
            return $result;
        }

        public function MessageSink($Timestamp, $SenderID, $MessageID, $Data)
        {
            $parentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            if ($SenderID == $parentID) {
                switch ($MessageID) {
                    case IM_CHANGESTATUS:
                        //Update websocket if faulty and the url matches the api
                        if ($Data[0] >= IS_EBASE && strpos(IPS_GetProperty($parentID, 'URL'), 'husqvarnagroup.net')) {
                            $this->UpdateWebSocket();
                        }
                        break;
                }
            }
        }

        public function Ping()
        {
            $this->SendDataToParent(json_encode([
                'DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}',
                'Buffer' => utf8_encode('Ping'),
            ]));
        }

        public function UpdateWebSocket()
        {
            //Only update if user is already registerd
            if ($this->GetStatus() != IS_ACTIVE) {
                return;
            }
            $locationID = json_decode($this->GetData('locations'), true)['data'][0]['id'];
            $payload = json_encode(
                [
                    'data' => [
                        'id'        => 'does-not-matter',
                        'type'      => 'WEBSOCKET',
                        'attributes'=> [
                            'locationId'=> $locationID
                        ]
                    ]
                ]
            );

            $response = json_decode($this->PostData('websocket', $payload), true);
            $url = $response['data']['attributes']['url'];
            $this->SendDebug('websocket', $url, 0);
            $parent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            if (!IPS_GetProperty($parent, 'Active')) {
                echo $this->Translate('IO instance is not active. Please activate the instance in order for the module to word');
                return;
            }
            IPS_SetProperty($parent, 'URL', $url);
            IPS_ApplyChanges($parent);
        }

        public function GetConfigurationForParent()
        {
            $parent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            $url = IPS_GetProperty($parent, 'URL');
            return json_encode([
                'URL'               => $url ? $url : 'wss://echo.websocket.org',
                'VerifyCertificate' => true
            ]);
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
                $this->SetStatus(IS_ACTIVE);
                $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($token, 0, 16) . '...');
                if ($this->HasActiveParent()) {
                    $this->UpdateWebSocket();
                }
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
                    $this->SetStatus(IS_ACTIVE);
                    $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($data->refresh_token, 0, 16) . '...');
                }
            }

            $this->SendDebug('FetchAccessToken', 'CACHE! New Access Token is valid until ' . date('d.m.y H:i:s', $Expires), 0);

            //Save current Token
            $this->SetBuffer('AccessToken', json_encode(['Token' => $Token, 'Expires' => $Expires]));

            //Return current Token
            return $Token;
        }

        private function GetData($endpoint)
        {
            $opts = [
                'http'=> [
                    'method'        => 'GET',
                    'header'        => 'Authorization: Bearer ' . $this->FetchAccessToken() . "\r\n",
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);

            $result = file_get_contents(self::SMART_SYSTEM_BASE_URL . $endpoint, false, $context);

            if ((strpos($http_response_header[0], '200') === false)) {
                echo $http_response_header[0] . PHP_EOL . $result;
                return false;
            }

            return $result;
        }

        private function PostData($endpoint, $content)
        {
            $opts = [
                'http'=> [
                    'method' => 'POST',
                    'header' => 'Authorization: Bearer ' . $this->FetchAccessToken() . "\r\n" .
                    'Content-Length: ' . strlen($content) . "\r\n" .
                    'Content-Type: application/vnd.api+json' . "\r\n",
                    'content'       => $content,
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);

            $result = file_get_contents(self::SMART_SYSTEM_BASE_URL . $endpoint, false, $context);

            if ((strpos($http_response_header[0], '201') === false)) {
                echo $http_response_header[0] . PHP_EOL . $result;
                return false;
            }

            return $result;
        }
    }