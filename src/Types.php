<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;

/**
 * Type definitions for BEAR.Resource
 *
 * @phpcs:disable SlevomatCodingStandard.Commenting.DocCommentSpacing
 *
 * Domain Types
 * @psalm-type ResourceUri = non-empty-string
 * @psalm-type ResourceMethod = non-empty-string
 * @psalm-type ViewName = non-empty-string
 * @psalm-type ResourceClassName = class-string<ResourceObject>
 * @psalm-type SchemeHostPort = non-empty-string
 *
 * Base Types
 * @psalm-type Query = array<string, mixed>
 * @psalm-type StringList = list<string>
 * @psalm-type Body = array<array-key, mixed>
 * @psalm-type ResourceLinks = array<string, mixed>
 * @psalm-type Embeds = array<string, mixed>
 * @psalm-type Schema = array<array-key, mixed>
 *
 * Options Method Types
 * @psalm-type ParameterMetadata = array{
 *     type?: string,
 *     description?: string,
 *     default?: string,
 *     in?: string
 * }
 * @psalm-type ParametersMap = array<string, ParameterMetadata>
 * @psalm-type RequiredParameters = list<string>
 * @psalm-type OptionsResponse = array{
 *     parameters?: ParametersMap,
 *     required?: RequiredParameters
 * }
 * @psalm-type OptionsDocumentation = array{
 *     summary?: string,
 *     description?: string
 * }
 * @psalm-type OptionsDocBlock = array{
 *     0: OptionsDocumentation,
 *     1: array<string, array{type: string, description?: string}>
 * }
 * @psalm-type EmbedList = non-empty-list<Embed>
 * @psalm-type LinkList = non-empty-list<Link>
 * @psalm-type SchemaArray = non-empty-array<array-key, mixed>
 * @psalm-type OptionsMethodsResponse = array{
 *     summary?: string,
 *     description?: string,
 *     request?: OptionsResponse,
 *     links?: LinkList,
 *     embed?: EmbedList,
 *     schema?: SchemaArray
 * }
 *
 * HTTP Request/Response Types
 * @psalm-type Headers = array<string, string>
 * @psalm-type HttpHeaders = array<string, string>
 * @psalm-type HttpBody = array<mixed>
 * @psalm-type HttpResponse = array{
 *     body: HttpBody,
 *     code: int,
 *     headers: HttpHeaders,
 *     view: string
 * }
 * @psalm-type RequestOptions = array<null>|array{
 *     body?: string,
 *     headers?: HttpHeaders
 * }
 *
 * HAL+JSON Types
 * @psalm-type HalLinkData = array{
 *     href: string,
 *     templated?: bool,
 *     type?: string,
 *     deprecation?: string,
 *     name?: string,
 *     profile?: string,
 *     title?: string,
 *     hreflang?: string
 * }
 * @psalm-type HalLinks = array<string, HalLinkData|list<HalLinkData>>
 * @psalm-type HalResource = array{
 *     _links?: HalLinks,
 *     _embedded?: array<string, mixed>
 * }
 *
 * Resource Metadata Types
 * @psalm-type PackageMetadata = array{
 *     vendor?: string,
 *     package?: string
 * }
 * @psalm-type ResourceObjectBody = array{
 *     0: ResourceObject,
 *     1: array<array-key, mixed>
 * }
 *
 * Link Relation Types
 * @psalm-type MethodUri = array{0: string, 1: string}
 *
 * Annotation Types
 * @psalm-type Annotations = array<class-string, object>
 * @psalm-type ClassAnnotations = array<class-string, Annotations>
 * @psalm-type MethodAnnotations = array<string, Annotations>
 * @psalm-type WebContextParam = class-string
 * @psalm-type WebContextName = 'cookie'|'env'|'formData'|'query'|'server'|'files'
 * @psalm-type WebContextMap = array<WebContextParam, WebContextName>
 * @psalm-type InsMap = array<string, string>
 *
 * Request Types
 * @psalm-type RequestInvoker = callable(ResourceObject, Request): ResourceObject
 * @psalm-type RequestQuery = array<string, mixed>
 *
 * Renderer Types
 * @psalm-type RenderView = array{
 *     status: string,
 *     headers: Headers,
 *     value: mixed,
 *     view: ViewName
 * }
 *
 * @phpcs:enable
 */
final class Types
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
