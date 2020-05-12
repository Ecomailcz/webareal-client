# PHP wrapper pro práci s Webareal.cz API

# Instalace

```shell
composer require ecomailcz/webareal-client
```

# Použití

```php
$username = '<vaše přihlašovací jméno>';
$password = '<vaše přihlašovací heaslo>';
$apiKey = '<váš API klíč>';

$credentials = new \Ecomailcz\Webareal\Credentials($username, $password, $apiKey); 
$api = new \Ecomailcz\Webareal\Client($credentials);

$orders = $api->requestGet('orders', [
    'limit' => 10,
    'sortBy'=>'id',
    'sortDirection'=>'desc'
]);
```

API klíč naleznete v administraci vašeho účtu v sekci Další služby > API.

Dokumentace API je dostupná na adrese: https://webareal.docs.apiary.io/

# Další funkce
## Token cache
Wrapper si automaticky získá z přihlašovacích údajů API token potřebný pro přístup k datům. Kvůli úspoře requsetů
poskytuje balíček Token cache, která si po dobu 1 hodiny pamatuje poslední platný token.

Výchozí cache si ale ukládá Token pouze v paměti, která se po skončení běhu scriptu uvolní. Knihovna rovněž podporuje
ukládání cache do souboru, stačí jen cache předat knihovně při vytváření:

```php
$cache = new \Ecomailcz\Webareal\TokenCache\FileCache(__DIR__ . '/temp');
$api = new \Ecomailcz\Webareal\Client($credentials, $cache);
```
