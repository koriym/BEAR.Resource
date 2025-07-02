# BEAR.Resource Psalm Type定義の提案

## 現状の課題

`OptionsMethodRequest`クラスなどで複雑なarray型定義が使用されています：

```php
@return array{parameters?: array<string, array{type?: string, description?: string, default?: string}>, required?: array<int, string>}
```

この型定義は以下の問題があります：
- 可読性が低い
- 再利用性がない
- IDEの補完が効きにくい
- 型の意味が不明瞭

## 提案するドメイン型定義

### 1. psalm-types.phpファイルの作成

プロジェクトルートに`psalm-types.php`を作成し、ドメインに特化した型定義を集約します。

### 2. OptionsMethod関連の型定義

```php
/**
 * @psalm-type ParameterMetadata = array{
 *     type?: string,
 *     description?: string,
 *     default?: string
 * }
 *
 * @psalm-type ParametersMap = array<string, ParameterMetadata>
 *
 * @psalm-type RequiredParameters = array<int, string>
 *
 * @psalm-type OptionsResponse = array{
 *     parameters?: ParametersMap,
 *     required?: RequiredParameters
 * }
 */
```

### 3. 使用例

#### Before:
```php
/**
 * @return array{parameters?: array<string, array{type?: string, description?: string, default?: string}>, required?: array<int, string>}
 */
public function __invoke(ReflectionMethod $method, array $paramDoc, array $ins): array
```

#### After:
```php
/**
 * @psalm-import-type OptionsResponse from \BEAR\Resource\psalm-types
 * 
 * @return OptionsResponse
 */
public function __invoke(ReflectionMethod $method, array $paramDoc, array $ins): array
```

## その他の型定義

### HAL+JSON関連
```php
/**
 * @psalm-type HalLink = array{
 *     href: string,
 *     templated?: bool,
 *     type?: string,
 *     // ... その他のプロパティ
 * }
 *
 * @psalm-type HalLinks = array<string, HalLink|array<int, HalLink>>
 *
 * @psalm-type HalResource = array{
 *     _links?: HalLinks,
 *     _embedded?: array<string, mixed>
 * }
 */
```

### HTTP関連
```php
/**
 * @psalm-type HttpHeaders = array<string, string>
 * @psalm-type HttpBody = array<mixed>
 * @psalm-type HttpResponse = array{
 *     body: HttpBody,
 *     code: int,
 *     headers: HttpHeaders,
 *     view: string
 * }
 */
```

## 実装手順

1. `psalm-types.php`ファイルの作成
2. 各クラスでの`@psalm-import-type`による型のインポート
3. 複雑な配列型定義をドメイン型に置き換え
4. Psalmによる型チェックの実行確認

## メリット

1. **可読性の向上**: 型の意味が明確になる
2. **再利用性**: 同じ型定義を複数箇所で使える
3. **保守性**: 型定義の変更が一箇所で済む
4. **IDE補完**: より良い開発体験
5. **ドキュメント性**: 型名自体がドキュメントとして機能

## 注意点

- Psalm 4.x以降で`@psalm-import-type`がサポートされています
- 型定義ファイルは実行時には読み込まれません（静的解析のみ）
- 既存のコードとの互換性を保ちながら段階的に移行可能