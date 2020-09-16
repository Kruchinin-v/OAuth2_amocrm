#AmoCRM oAuth 2.0

**Description:**  
Новый метод авторизации в AmoCRM использует токены. Скрипты выполняют запросы и получают
в ответ токены, сохраняют их в файл. После чего можно использовать полученные токены для
обращений к API AmoCRM.  

**Основые понятия:**
* Access token - это токен, по которому происходит авторизация обращений к API.
*(действует сутки, запрос так же возвращает время действия )*
* Refresh token - это токен для обновления Access token'а. 
*(Это одноразовый токен . При новом запросе вернется следующий токен.
Если не использовать его 3 месяца, он так же станет не действительный)*
* Authorization code - он же "Код авторизации". Нужен для получения первой пары токенов. 
*(этот код действует 20 минут, но может обновится раньше)*
 
**Действия:**
1. Для авторизации необходимо создать интеграцию. Дать ей имя, описание, права и ссылку.
Ссылка нужна скорее всего для полноценной работы виджета, но если вы не планируете писать 
сложную логику, просто укажите адрес вашего сайта `https://example.ru`.  
2. После создания интеграции Необходимо зайти в нее. Там будет 3 ключа:
- ID интеграции
- Секретный ключ
- Код авторизации  

  Их необходимо вписать в php скрипт `get_tokens.php`. В комментариях указано куда какое значение вставлять.
Так же указать ту ссылку перенаправления, что была указана в интеграции.
Этот скрипт сделает POST запрос в АМО для получение 2х ключей - Access token и Refresh token.  
В данный момент скрипт get_tokens.php сохраняет токены в папку `/var/www/html/my_apps/tokens` Пути можно менять.  
*Если скрипт возвращает ошибку 400, то возможно нужно взять Authorization code по новой.*

3. Далее нужно добавить в cron запуск скрипта get_access_token. Этот скрипт берет refresh токен и с помощью него
запрашивает новую пару токенов.  
Пример строчки в cron для автоматичесого запуска:
```
00 3 * * * php /var/www/html/my_apps/functions/get_access_token.php >> /tmp/get_access_token.log 2>&1
```


Нужно убедится, что в строке "`require()`" указан верный путь до токена.  

Куда будет записываться токен указано в переменных `$file` в конце скриптов.  

Если Refresh token был утерян, то необходимо просто взять новый Authorization code в панели интеграции,
и выполнить первый запрос `get_tokens.php`.  

В папке functions есть пример использования запросов через OAuth2.0. `getContact.php`  
Скрипт ищет контакты для указаного лида. Возвращает id ответственного и id клиента.

### Обновление токена можно воспроизвести через curl
 запрос для получения токенов через Authorization code  
 заменить "subdomain".  
 вписать значения из интеграции
```shell script
curl https://subdomain.amocrm.ru/oauth2/access_token -d \
'{"client_id":"xxx-xxx-xxx-xxx-xxx","client_secret":"xxxxxx","grant_type":"authorization_code","code":"xxxxxxxx","redirect_uri":"https://test.test/"}' \
-H 'Content-Type:application/json' \
-X POST
```
Пример ответа:
```json
{
  "token_type": "Bearer",
  "expires_in": 86400,
  "access_token": "xxxxxx",
  "refresh_token": "xxxxx"
}
```
запрос для обновление токенов через Refresh token  
заменить "subdomain".  
вписать значения client_id client_secret из авторизации, и refresh_token из ответа предыдущего запроса.
```shell script
curl https://subdomain.amocrm.ru/oauth2/access_token -d \
'{"client_id":"xxx-xxx-xxx-xxx-xxx","client_secret":"xxxxxx","grant_type":"refresh_token","refresh_token":"xxxxxxxx","redirect_uri":"https://test.test/"}' \
-H 'Content-Type:application/json' \
-X POST
```
Пример ответа:
```json
{
  "token_type": "Bearer",
  "expires_in": 86400,
  "access_token": "xxxxxx",
  "refresh_token": "xxxxx"
}
```
Для использование access_token   
заменить "subdomain".  
вставить access_token
```shell script
curl --location --request GET 'https://subdomain.amocrm.ru/api/v4/leads/' \
--header 'Authorization: Bearer <access_token>' \
--header 'Cookie: user_lang=ru'
```