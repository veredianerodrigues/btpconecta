<?php
/* ---------------------------------
@version Beta v0.1
@date 19.Jul.2019
@author Marcio Camargo
@email marcio@seepix.com.br

@author André Borali
@email andre@seepix.com.br

v1.0 - Verifica os dados de login do usuário (XML)
v1.1 - Consulta com resposta em REST

Esta página faz a checagem automática
do status do login do usuário com os
servidores da BTP em um certo intervalo 
de tempo.
--------------------------------- */
// $loginUrlSend = "https://169.57.178.238:40801/g5-senior-services/rubi_Synccom_btp_btpconecta";
$loginUrlSend = "https://platform.senior.com.br/t/senior.com.br/bridge/1.0/rest/platform/authentication/actions/login";

include('config.php');

$response = array();
$response['status'] 	= 0;
$response['message'] 	= '';
$response['data'] 		= '';

$date_limit = 240; // A cada 4 minutos

$status = 0;

$res_msg[1] = 'Login verificado com sucesso!';
$res_msg[0] = 'Ocorreu um erro desconhecido durante este processo';
$res_msg[-1] = 'O usuário não está conectado.';
$res_msg[-2] = 'Ocorreu um erro durante o acesso ao BD.';
$res_msg[-3] = 'A conexão do usuário está dentro do período de validade.';


if(!(isset($_COOKIE['btpUserName'])) || !(isset($_COOKIE['btpUserToken'])))
	{
		$status = -1;
	}

function loggin_check($user, $pass)
    {

        $user   = addslashes($user);
        $pass   = addslashes($pass);
        
        $user   = trim($user);
        $pass   = trim($pass);

        if($user=="" || $pass=="")
            {
                return false;    
            }

        if (!(strpos($user, '@'))) {
            $user .= '@btp.com.br';
        }
        
        $curlRequest = "{\n\t\"username\": \"".$user."\",\n\t\"password\": \"".$pass."\"\n}";

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://platform.senior.com.br/t/senior.com.br/bridge/1.0/rest/platform/authentication/actions/login",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 60,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $curlRequest,
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: platform.senior.com.br",
            "User-Agent: PostmanRuntime/7.15.2",
            "cache-control: no-cache"
          ),
        ));

        $jsonRes = curl_exec($curl);
        $jsonRes = json_decode($jsonRes);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($http_status=='500')
            {
                return false;
            }

        if($http_status=='404')
            {
                return false;
            }

        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
            }

        /* ---------------------------------
        Usuário Inválido || Senha Expirada
        --------------------------------- */
        if(isset($jsonRes->{'reason'}) || isset($jsonRes->{'errorCode'}))
        {
            return false;
        }


        return true;

    }

/* ---------------------------------
Checando os dados do local
--------------------------------- */
if($status==0)
	{
		$userName = htmlspecialchars($_COOKIE['btpUserName'], ENT_COMPAT,'ISO-8859-1', true);
        $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT,'ISO-8859-1', true);

        $mysqli = new mysqli(BTP_DB_HOST,BTP_DB_USER,BTP_DB_PASSWORD,BTP_DB_NAME);
        if($mysqli->connect_errno) 
            {
                $status = -2;
            } 

        $query = "Select *, UNIX_TIMESTAMP(date_update) as date_timestamp from btpconecta_tokens where token='$userToken' and user='$userName' and ativo='1'";
        if($result = $mysqli->query($query)) 
			{
			    $row = $result->fetch_assoc();
			    if(sizeof($row)>0)
				    {

				    	$timestamp = $row['date_timestamp'];
				    	$actualDate = time();

				    	if($timestamp<($actualDate-$date_limit))
					    	{
					    		$user = $row['user'];
						    	$pass_raw = $row['pass'];
						    	$pass = base64_decode($pass_raw);

						    	$res = loggin_check($user, $pass);

						    	if(!$res)
							    	{
							    		setcookie("btpUserName",'', 0,'/btpconecta');
										setcookie("btpUserToken",'', 0,'/btpconecta');
							    	} else {

							    		$query_update = "Update btpconecta_tokens set date_update=now() where user='$userName' and token='$userToken'";
							    		$mysqli->query($query_update);

							    	}

							    $status = 1;

					    	} else {
					    		$status = -3;
					    	}
				    } else {
				    	setcookie("btpUserName",'', 0,'/');
						setcookie("btpUserToken",'', 0,'/');
						$status = -1;
				    }
			}
     }

$response['status'] 	= $status;
$response['message'] 	= $res_msg[$status];

header('Content-type: application/json');
header('Charset: iso-8859-1');
echo json_encode($response);
?>