<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class FakeInputDtoResource extends ResourceObject
{
    #[JsonSchema(params: 'input-dto.single.json')]
    public function onPost(#[Input] ArticleInput $input): static
    {
        $this->code = Code::NO_CONTENT;
        $this->body = [
            'slug' => $input->slug,
            'title' => $input->title,
        ];

        return $this;
    }

    #[JsonSchema(params: 'input-dto.mixed.json')]
    public function onPut(#[Input] ArticleInput $input, string $extra): static
    {
        $this->code = Code::NO_CONTENT;
        $this->body = [
            'extra' => $extra,
            'slug' => $input->slug,
            'title' => $input->title,
        ];

        return $this;
    }

    #[JsonSchema(params: 'input-dto.nested.json')]
    public function onPatch(#[Input] ArticleWithSeoInput $input): static
    {
        $this->code = Code::NO_CONTENT;
        $this->body = [
            'metaTitle' => $input->seo->metaTitle,
            'slug' => $input->slug,
            'title' => $input->title,
        ];

        return $this;
    }

    #[JsonSchema(params: 'input-dto.optional.json')]
    public function onDelete(
        #[Input] OptionalArticleInput $input = new OptionalArticleInput('Default Title', 'default-title'),
    ): static {
        $this->code = Code::NO_CONTENT;
        $this->body = [
            'slug' => $input->slug,
            'title' => $input->title,
        ];

        return $this;
    }
}
