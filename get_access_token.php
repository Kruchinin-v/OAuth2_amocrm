<?php
/**
 * Скрипт для получения access и refresh токенов через refresh token.
 * Refresh токен можно получить используя скрипт get_tokens.php
 * Необходимо заполнить переменные ниже для своего примера.
 * Данные для заполнения следующих 3х переменных нужно брать из новой созданной Вами интеграции в amocrm
 * Полученный Access токен действителен 24 часа, Refresh токен до первого использования или на 3 месяца.
 * Данный скрипт необходимо запускать ежедневно. Рекомендую ночью, чтобы не было заминок.
 * Пример для cron
 * 00 3 * * * php /var/www/html/my_apps/functions/get_access_token.php >> /tmp/get_access_token.log 2>&1
 */


# ID интеграции
$clientId = '12345678-90ab-cdef-1234-567890abcdef';
# Секретный ключ
$clientSecret = 'slgjsdfgkjkld;asIDF8HJDF123ASDhjh23ASD213123kjsdlaklsidpwqeiasd1';
# ссылка для перенаправления
$redirectUri = 'https://example.ru';

# путь до директории, где будет будух хранится токены
$path_tokens = '/var/www/html/my_apps/tokens';

# здесь необходимо указать путь до refresh токена
require($path_tokens . "/refresh_token.php");

$subdomain = 'mysubdomain'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
	'client_id' => $clientId,
	'client_secret' => $clientSecret,
	'grant_type' => 'refresh_token',
	'refresh_token' => $refresh_token,
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