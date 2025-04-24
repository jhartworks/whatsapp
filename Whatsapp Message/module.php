<?
// Klassendefinition
class WhatsappMessage extends IPSModule {
    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();
        
        $this->RegisterPropertyString("WbToken","");
        $this->RegisterPropertyString("WbAppId","");
        $this->RegisterPropertyString("WbTemplate","stoerung");
        $this->RegisterPropertyString("WbLang","de");
        $this->RegisterPropertyString("Numberlist","");
    }

    // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();
    }
    /**
    * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
    *
    * DWM_SendMessage($id);
    *
    */


    private function normalizePhoneNumber($number) {
        // 1. Entferne führendes + oder 0
        $number = ltrim($number, '+');
        if (strpos($number, '0') === 0) {
            $number = substr($number, 1);
        }
    
        // 2. Wenn keine 49 am Anfang steht, füge sie hinzu
        if (strpos($number, '49') !== 0) {
            $number = '49' . $number;
        }
    
        return $number;
    }

    public function SendMessageEx(string $recip, array $paramvals) {
        
            $token = $this->ReadPropertyString("WbToken");
            $appId = $this->ReadPropertyString("WbAppId");
            $template = $this->ReadPropertyString("WbTemplate");
            $lang = $this->ReadPropertyString("WbLang");

            $arrString = $this->ReadPropertyString("Parameterlist");
            $paramarr= json_decode($arrString, true);

            $parameters = [];
            
            foreach ($paramvals as $paramName => $paramValue) {
                $parameters[] = [
                    "type" => "text",
                    "parameter_name" => $paramName,
                    "text" => $paramValue
                ];
            }

            //print_r($parameters);
            
            $recip = $this->normalizePhoneNumber($recip);

            $data = [
                "messaging_product" => "whatsapp",
                "to" => $recip,
                "type" => "template",
                "template" => [
                    "name" => $template,
                    "language" => [
                        "code" => $lang
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => $parameters
                        ]
                    ]
                ]
            ];
            
            //echo "JSON:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
            
            $ch = curl_init('https://graph.facebook.com/v22.0/'.$appId.'/messages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return isMessageAccepted($response);
           // echo "HTTP-Code: $httpcode\nAntwort: $response\n";

    }
    public function SendMessage(array $paramvals) {
        
        $arrnumbers = $this->ReadPropertyString("Numberlist");   
        $entries = json_decode($arrnumbers, true);

        foreach ($entries as $entry){
            $number = $entry['Number'];
            SendMessageEx($number, $paramvals);
        }

    }
    private function isMessageAccepted($jsonResponse) {
        $data = json_decode($jsonResponse, true);
    
        // message_status MUSS vorhanden und accepted sein
        if (isset($data['messages'][0]['message_status']) && $data['messages'][0]['message_status'] === 'accepted') {
            return true;
        }
    
        // Wenn message_status fehlt oder nicht "accepted" ist → false
        return false;
    }
}
?>