# BEAR.Resource

## Hypermedia framework for object as a service

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.Resource/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.Resource/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.Resource/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.Resource/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.Resource.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.Resource)


**BEAR.Resource** はオブジェクトがリソースの振る舞いを持つHypermediaフレームワークです。
クライアントーサーバー、統一インターフェイス、ステートレス、相互接続したリソース表現、レイヤードコンポーネント等の
RESTのWebサービスの特徴をオブジェクトに持たせます。

既存のドメインモデルやアプリケーションの持つ情報を柔軟で長期運用を可能にするために、 アプリケーションをRESTセントリックなものにしAPI駆動開発を可能にします。

### リソースオブジェクト

リソースとして振る舞うオブジェクトがリソースオブジェクトです。

 * １つのURIのリソースが1クラスにマップされ、リソースクライアントを使ってリクエストします。
 * 統一されたリソースリクエストに対応したメソッドを持ち名前付き引き数でリクエストします。
 * メソッドはリクエストに応じてリソース状態を変更して自身`$this`を返します。

```php
<?php
namespace MyVendor\Sandbox\Blog;

class Author extends ResourceObject
{
    public $code = 200;

    public $headers = [
        'Content-Type' => 'application/json'
    ];

    public $body = [
        'id' =>1,
        'name' => 'koriym'
    ];

    /**
     * @Link(rel="blog", href="app://self/blog/post?author_id={id}")
     */
    public function onGet(int $id) : ResourceObject
    {
        return $this;
    }

    public function onPost(string $name) : ResourceObject
    {
        $this->code = 201; // created
        // ...
        return $this;
    }

    public function onPut(int $id, string $name) : ResourceObject
    {
        $this->code = 203; // no content
        //...
        return $this;
    }

    public function onDelete($id) : ResourceObject
    {
        $this->code = 203; // no content
        //...
        return $this;
    }
}
```

### インスタンスの取得

ディペンデンシーインジェクターを使ってクライアントインスタンスを取得します。

```php
use BEAR\Resource\ResourceInterface;

$resource = (new Injector(new ResourceModule('FakeVendor/Sandbox')))->getInstance(ResourceInterface::class);
```

### リソースリクエスト

URIとクエリーを使ってリソースをリクエストします。

```php
$user = $resource->get('app://self/user', ['id' => 1]);
```

 * このリクエストは[PSR0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)に準拠した **MyVendor\Sandbox\Resource\App\User** クラスの **onGet($id)** メソッドに1を渡します。
 * 得られたリソースは **code**, **headers** それに **body**の３つのプロパティを持ちます。

```php
var_dump($user->body);
```

```php
Array
(
 [name] => Athos
 [age] => 15
 [blog_id] => 0
)
```

リソースリクエストには２種類の方法があります。即時実行(eager request)と遅延実行(lazy request)です。

### 即時実行

即時実行は通常のPHPのメソッドと同様にすぐに実行して結果を求めます。いくつかの書き方があります。

```php
$user = $resource
  ->get
  ->uri('app://self/user')
  ->withQuery(['id' => 1])
  ->eager
  ->request();
```

```php
$user = $resource->get->uri('app://self/user')(['id' => 1]);
```

```php
$user = $resource->uri('app://self/user')(['id' => 1]); // 'get' request method can be omitted
```

### 遅延実行
 
[遅延評価](https://ja.wikipedia.org/wiki/%E9%81%85%E5%BB%B6%E8%A9%95%E4%BE%A1)として Request to resource until the point at which it is needed. One common use case is that assign to template `the resource request object` not the instance.

```php
// get `ResourceRequest` objcet 
$user = $resource->get->uri('app://self/user')->withQuery(['id' => 1]);
// assign to the template
echo "User resource body is {$user}"; // same in the template enigne template
```

It is callable object, you can invoke with other parameters;

```php
// invoke
$user1 = $user(); // $id = 1
$user2 = $user(['id' => 2);
```

## ハイパーメディア

リソースは関連するリソースの [ハイパーリンク](http://en.wikipedia.org/wiki/Hyperlink)を持つ事ができます
**@Link**アノテーションをメソッドにアノテートしてハイパーリンクを表します。

```php

use BEAR\Resource\Annotation\Link;

/**
 * @Link(rel="blog", href="app://self/blog?author_id={id}")
 */
```

**rel** でリレーション名を **href** (hyper reference)でリンク先URIを指定します。
URIは [URIテンプレート](http://code.google.com/p/uri-templates/)([rfc6570](http://tools.ietf.org/html/rfc6570))を用いて現在のリソースの値をアサインすることができます。

リンクには **self**, **new**, **crawl** といくつか種類があり効果的にリソースグラフを作成することができます。

### selfリンク

`linkSelf`はリンク先のリソースを取得します。

```php
$blog = $resource
    ->get
    ->uri('app://self/user')
    ->withQuery(['id' => 0])
    ->linkSelf('blog')
    ->eager
    ->request();
```
**app://self/user** リソースをリクエストした結果で **blog** リンクを辿り **app://self/blog**リソースを取得します。
Webページでリンクをクリックしたように次のリソースに入れ替わります。

### newリンク

`linkNew` はリンク先のリソースも追加取得します。

```php
$user = $resource
    ->get
    ->uri('app://self/user')
    ->withQuery(['id' => 0])
    ->linkNew('blog')
    ->eager
    ->request();
    
$blog = $user['blog'];
```

Webページで「新しいウインドウでリンクを表示」を行うように現在のリソースは保持したまま次のリソースを取得します。

### クロール

クロールはリスト（配列）になっているリソースを順番にリンクを辿り、複雑なリソースグラフを構成することができます。
クローラーがwebページをクロールするように、リソースクライアントはハイパーリンクをクロールしソースグラフを生成します。

author, post, meta, tag, tag/name がそれぞれ関連づけられてあるリソースグラフを考えてみます。
それぞれのリソースはハイパーリンクを持ちます。
このリソースグラフに **post-tree** という名前を付け、それぞれのリソースの@Linkアノテーションでハイパーリファレンス **href** を指定します。

authorリソースにはpostリソースへのハイパーリンクがあります。1:nの関係です。
```php
/**
 * @Link(crawl="post-tree", rel="post", href="app://self/post?author_id={id}")
 */
public function onGet($id = null)
```

postリソースにはmetaリソースとtagリソースのハイパーリンクがあります。1:nの関係です。
```php
/**
 * @Link(crawl="post-tree", rel="meta", href="app://self/meta?post_id={id}")
 * @Link(crawl="post-tree", rel="tag",  href="app://self/tag?post_id={id}")
 */
public function onGet($author_id)
{
```

tagリソースはIDだけでそのIDに対応するtag/nameリソースへのハイパーリンクがあります。1:1の関係です。

```php
/**
 * @Link(crawl="post-tree", rel="tag_name",  href="app://self/tag/name?tag_id={tag_id}")
 */
public function onGet($post_id)
```

クロール名を指定してリクエストします。

```php
$graph = $resource
  ->get
  ->uri('app://self/marshal/author')
  ->linkCrawl('post-tree')
  ->eager
  ->request();
```

リソースクライアントは@Linkアノテーションに指定されたクロール名を発見するとその **rel** 名でリソースを接続してリソースグラフを作成します。

```
var_export($graph->body);

array (
    0 =>
    array (
        'name' => 'Athos',
        'post' =>
        array (
            0 =>
            array (
                'author_id' => '1',
                'body' => 'Anna post #1',
                'meta' =>
                array (
                    0 =>
                    array (
                        'data' => 'meta 1',
                    ),
                ),
                'tag' =>
                array (
                    0 =>
                    array (
                        'tag_name' =>
                        array (
                            0 =>
                            array (
                                'name' => 'zim',
                            ),
                        ),
                    ),
 ...
```

### HATEOAS アプリケーション状態のエンジンとしてのハイパーメディア

リソースはクライアントの次の動作をハイパーリンクにして、クライアントはそのリンクを辿りアプリケーションの状態を変更します。
例えば注文リソースに **POST** して注文を作成、その注文の状態から支払リソースに **PUT**して支払を行います。

Order リソース
```php
/**
 * @Link(rel="payment", href="app://self/payment{?order_id, credit_card_number, expires, name, amount}", method="put")
 */
public function onPost($drink)
```

クライアントコード
```php
    $order = $resource
        ->post
        ->uri('app://self/order')
        ->withQuery(['drink' => 'latte'])
        ->eager
        ->request();

    $payment = [
        'credit_card_number' => '123456789',
        'expires' => '07/07',
        'name' => 'Koriym',
        'amount' => '4.00'
    ];

    // then use hyper link to pay
    $response = $resource->href('payment', $payment);
    
    echo $response->code; // 201
```

支払の方法は注文リソースがハイパーリンクと提供しています。
支払と注文の関係が変更されてもクライアントコードに変更はありません。
HATEOAS について詳しくは[How to GET a Cup of Coffee](http://www.infoq.com/articles/webber-rest-workflow)をご覧ください。

### リソース表現

リソースはそれぞれ表現のためのリソースレンダラーを持っています。
文字列評価されるとリソースはインジェクトされたリソースレンダラーを使ってリソース表現になります。

```php
echo $user;

// {
//     "name": "Aramis",
//     "age": 16,
//     "blog_id": 1
// }
```
このときの`$user`はレンダラーが内蔵された`ResourceObject`リソースオブジェクトです。
配列やオブジェクトとしても取り扱うことができます。

```php

echo $user['name'];

// Aramis

echo $user->onGet(2);

// {
//     "name": "Yumi",
//     "age": 15,
//     "blog_id": 2
// }
```

### 遅延評価

```php
$user = $resource
  ->get
  ->uri('app://self/user')
  ->withQuery(['id' => 1])
  ->request();

$templateEngine->assign('user', $user);
```

`eager`のない`request()`ではリソースリクエストの結果ではなく、リクエストオブジェクトが取得できます。
テンプレートエンジンにアサインするとテンプレートにリソースリクエスト`{$user}`が現れたタイミングで`リソースリクエスト`と`リソースレンダリング`を行い文字列表現になります。
リソース表現はAPI用の他にも、テンプレートエンジンを用いてHTMLにする事もできます。

## 埋め込みリソース

`@Embed`アノテーションを使って他のリソースを自身のリソースに埋め込む事が出来ます。`HTML`の`<img src="image_url">`や`<iframe src="content_url">`と同じ様に`src`で埋め込むリソースを指定します。

```php
class News extends ResourceObject
{
    /**
     * @Embed(rel="weather",src="app://self/weather/today")
     */
    public function onGet()
    {
        $this['headline'] = "...";
        $this['sports'] = "...";
        
        return $this;
    }
}
```

このNewsリソースでは`headline`と`sports`と同様に`weather`というリソースのリクエストを埋め込みます。

### HAL (Hypertext Application Language)

HAL Moduleを使うとリソース表現が[HAL](http://stateless.co/hal_specification.html)になります。リソースに埋め込まれたリクエストはHALでも埋め込みリソースとして評価されます。

```php
    // create resource client with HalModule
    $resource = Injector::create([new ResourceModule('MyVendor\MyApp'), new HalModule])->getInstance('BEAR\Resource\ResourceInterface');
    // request
    $news = $resource
        ->get
        ->uri('app://self/news')
        ->withQuery(['date' => 'today'])
        ->request();
    // output
    echo $news . PHP_EOL;

```

結果

```javascript
{
    "headline": "40th anniversary of Rubik's Cube invention.",
    "sports": "Pieter Weening wins Giro d'Italia.",
    "_links": {
        "self": {
            "href": "/api/news?date=today"
        }
    },
    "_embedded": {
        "weather": [
            {
                "today": "the weather of today is sunny",
                "_links": {
                    "self": {
                        "href": "/api/weather?date=today"
                    },
                    "tomorrow": {
                        "href": "/api/weather/tomorrow"
                    }
                }
            }
        ]
    }
}

```
## リソース表現

`ResourceObject` を文字列評価するとリソース表現（リプレゼンテーション）が取得できます。

```php
$userView = (string) $resource->get('app://self/user?id=1');
echo $userView; // get JSON
```

レンダラーを変えて、リソースを他のメディアタイプで表現する事ができます。通常はDIでレンダラーを依存として注入します。

```php
class User extends ResourceObject
{
    public function __construct()
    {
        $this->setRenderer(new class implements RenderInterface{
            public function render(ResourceObject $ro)
            {
                $ro->headers['content-type'] = 'application/json';
                $ro->view = json_encode($ro->body);

                return $ro->view;
            }
        });
    }
}
```

## 転送

REST は representational state "transfer" の略です。 `ResourceObject`の`transfer()` メソッドでリソースをクライアントに出力します。

```php
$user = $resource->get('app://self/user?id=1');
$user->transfer(new class implements TransferInterface {
	public function __invoke(ResourceObject $ro, array $server)
	{
	    foreach ($ro->headers as $label => $value) {
	        header("{$label}: {$value}", false);
	    }
	    http_response_code($ro->code);
	    echo $ro->view;
	}
);
```

## インストール

```javascript
composer require bear/resource ^1.10
```

## A Resource Oriented Framework

__BEAR.Sunday__ はリソース指向のフレームワークです。BEAR.Resourceに Webでの振る舞いやアプリケーションスタックの機能を、
Google GuiceスタイルのDI/AOPシステムの[Ray](https://github.com/koriym/Ray.Di)で追加してフルスタックのWebアプリケーションフレームワークとして機能します。
[BEAR.Sunday GitHub](https://github.com/koriym/BEAR.Sunday)をご覧下さい。

## See Also

 * [BEAR.QueryRepository](https://github.com/bearsunday/BEAR.QueryRepository) - 読み込みと書き込みのレポジトリを分離します。
 * [Ray.WebParamModule](https://github.com/ray-di/Ray.WebParamModule) - Webコンテキストをパラメーターにバインドします。
 
## Testing BEAR.Resource

以下はインストールしてテスト実行するための手順です。

```
composer create-project bear/resource BEAR.Resource
cd BEAR.Resource
./vendor/bin/phpunit
php demo/run.php
```
