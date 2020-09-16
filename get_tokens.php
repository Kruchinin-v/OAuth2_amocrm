<?php
/**
 * Скрипт для получения access и refresh токенов с через код авторизации.
 * Необходимо заполнить переменные ниже для своего примера.
 * Данные для заполнения следующих 4 переменных нужно брать из новой созданной Вами интеграции в amocrm
 * Достаточно запустить один раз.
 * Полученный Access токен действителен 24 часа, Refresh токен до первого использования или на 3 месяца.
 * Для дальнейшего обновления Access токена использовать скрипт get_access_token.php
 */

# ID интеграции
$clientId = '12345678-90ab-cdef-1234-567890abcdef';
# Секретный ключ
$clientSecret = 'slgjsdfgkjkld;asIDF8HJDF123ASDhjh23ASD213123kjsdlaklsidpwqeiasd1';
# Код авторизации
$code = 'def502007b8a6dcf2f8ecc724047686a7dd10736ab0e9d860938956de092673e86fd05af39f868fd77b5d754c49ce1574367d582bd9a6e40aa3347f4a83ae96516ff432930168e6726cb47acb40ad9d3efa8f42d0e007eece49fd1541112e07cb865d6e49d0f7dabb0c14642801c7cfd7a01475dd44a95b28fbe7d97b0acc8d4da58921779b0d42412613b8f8a0bbbe0bb3efd0fa1b67e3b406529a7e23e08ea955614d037dbd8347df4dd94b7051588f58fc5e74ad59b0370f28f1980dfae577fe067bf0ec76da7b29885a94314f1a1dc987369c4236387fba632595e6afa0f58558ad05d6e089d966ad2476ce928ec9c5c70c38115d8253c1a37515d77ac71bd53e9191d74e41d77745ef0a2962cd755093ded1e4aca3745303fb3954e9b1c552f6db109982ee34f015af903354c340c4e403744c1c31f6e195f9c31404f0d9f2d5ef5e115872f8ba759c276936c00ff428781ffeb1073a9857a2266ca38e026a9fd55ae93e2c8dc96bacf714b0ca8d786110b2aa59c6d4a1e4359954c02393eb9613a4c14ccb583f2d22b7716be4173fb59d6a84be19fa032580a07ba659cbf3054b58e58fbd4932db216cd71c2a253619b392d3300c6f9da8ab527ba5a453c0f27';
# ссылка для перенаправления
$redirectUri = 'https://example.ru';

# путь до директории, где будет будух хранится токены
$path_tokens = '/var/www/html/my_apps/tokens';

$subdomain = 'mysubdomain'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
	'client_id' => $clientId,
	'client_secret' => $clientSecret,
	'grant_type' => 'authorization_code',
	'code' => $code,
	'redirect_uri' => $redirectUri,
];


/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
	400 => 'Bad request',
	401 => 'Unauthorized',
	403 => 'Forbidden',
	404 => 'Not found',
	500 => 'Internal server error',
	502 => 'Bad gateway',
	503 => 'Service unavailable',
];
/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

try
{
	/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
	if ($code < 200 || $code > 204) {
        print(var_export($response,true));
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
        
	}
}
catch(\Exception $e)
{
	die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode() . "\n");
}


$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

# записать ответ в файл
# общий ответ, нужен в основном для дебага. можно удалить после полной настройки
$dateN = date(DATE_RFC822);
$file =  $path_tokens . '/tokens.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($response,true) . "\n";
file_put_contents($file, $current);

# сам токен, пишется в переменную $access_token. действительный 24 часа
# для использования необходимо подключить require('/var/www/html/my_apps/tokens/access_token.php');
$file = $path_tokens . '/access_token.php';
$current = "<?php \n\$access_token = '" . $access_token . "';\n";
file_put_contents($file, $current);

# токен, необходимый для обновления access token
$file =  $path_tokens . '/refresh_token.php';
$current = "<?php \n\$refresh_token = '" . $refresh_token . "';\n";
file_put_contents($file, $current);


# записать чистый токен в файл
# допустим для использования в bash скриптах p=`cat /var/www/html/my_apps/tokens/access_token`
$dateN = date(DATE_RFC822);
$file = $path_tokens . '/access_token';
$current = $access_token;
file_put_contents($file, $current);

