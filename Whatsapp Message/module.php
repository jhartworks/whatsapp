<?
// Klassendefinition
class DiscordMessage extends IPSModule {
    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();
        
        $this->RegisterPropertyString("WbToken","");
        $this->RegisterPropertyString("WbAppId","");
        $this->RegisterPropertyString("WbTemplate","stoerung");
        $this->RegisterPropertyString("WbLang","de");
        $this->RegisterPropertyString("Parameterlist","");
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
    public function SendMessage($topic,$recip, array $paramvals) {
        
            $token = $this->ReadPropertyString("WbToken");
            $appId = $this->ReadPropertyString("WbAppId");
            $template = $this->ReadPropertyString("WbTemplate");
            $lang = $this->ReadPropertyString("WbLang");

            $arrString = $this->ReadPropertyString("Parameterlist");
            $paramarr= json_decode($arrString, true);

            $parameters = [];

            for ($i = 0; $i < count($paramarr); $i++) {
                $paramName = $paramarr[$i]['ParName'];
                $paramValue = isset($paramvals[$i]) ? $paramvals[$i] : '';
        
                $parameters[] = [
                    "type" => "text",
                    "parameter_name" => $paramName,
                    "text" => $paramValue
                ];
            }

            $data = [
                "messaging_product" => "whatsapp",
                "to" => $recip,
                "type" => "template",
                "template" => [
                    "name" => $template,
                    "language" => [
                        "code" => lang
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => $parameters
                        ]
                    ]
                ]
            ];
            
            echo "JSON:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
            
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
            
            echo "HTTP-Code: $httpcode\nAntwort: $response\n";

    }
}
?>