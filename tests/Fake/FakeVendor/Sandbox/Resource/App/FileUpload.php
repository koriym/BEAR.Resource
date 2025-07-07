<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Resource\ResourceObject;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload as KoriyumFileUpload;
use Ray\InputQuery\Attribute\InputFile;

final class FileUpload extends ResourceObject
{
    /**
     * @var array<string, mixed>
     */
    public $body;

    /**
     * Single file upload with validation (supports SVG)
     */
    public function onPost(
        #[InputFile(
            maxSize: 1024 * 1024, // 1MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
            allowedExtensions: ['jpg', 'jpeg', 'png', 'svg']
        )]
        KoriyumFileUpload|ErrorFileUpload $image,
        string $title = 'Default Title'
    ): static {
        if ($image instanceof ErrorFileUpload) {
            $this->code = 400;
            $this->body = [
                'error' => true,
                'message' => $image->message,
                'title' => $title,
            ];

            return $this;
        }

        $this->body = [
            'success' => true,
            'filename' => $image->name,
            'size' => $image->size,
            'type' => $image->type,
            'title' => $title,
        ];

        return $this;
    }

    /**
     * Multiple file upload - uses FileUploadFactory::createMultiple()
     *
     * @param array<KoriyumFileUpload|ErrorFileUpload> $files
     */
    public function onPut(
        #[InputFile(
            maxSize: 2 * 1024 * 1024, // 2MB
            allowedTypes: ['image/svg+xml', 'image/png', 'image/jpeg']
        )]
        array $files
    ): static {
        $results = [];
        $hasError = false;

        foreach ($files as $file) {
            if ($file instanceof ErrorFileUpload) {
                $hasError = true;
                $results[] = [
                    'error' => true,
                    'message' => $file->message,
                ];
                continue;
            }

            $results[] = [
                'success' => true,
                'filename' => $file->name,
                'size' => $file->size,
                'type' => $file->type,
            ];
        }

        $this->code = $hasError ? 400 : 200;
        $this->body = [
            'files' => $results,
            'total' => count($files),
        ];

        return $this;
    }

    /**
     * Optional file upload (can be null)
     */
    public function onPatch(
        #[InputFile(
            maxSize: 1024 * 1024, // 1MB
            allowedTypes: ['image/svg+xml', 'image/png', 'image/jpeg'],
            required: false
        )] KoriyumFileUpload|ErrorFileUpload|null $avatar = null,
        string $username = 'anonymous'
    ): static {
        if ($avatar === null) {
            $this->body = [
                'username' => $username,
                'avatar' => null,
            ];

            return $this;
        }

        if ($avatar instanceof ErrorFileUpload) {
            $this->code = 400;
            $this->body = [
                'error' => true,
                'message' => $avatar->message,
                'username' => $username,
            ];

            return $this;
        }

        $this->body = [
            'username' => $username,
            'avatar' => [
                'filename' => $avatar->name,
                'size' => $avatar->size,
                'type' => $avatar->type,
            ],
        ];

        return $this;
    }

    /**
     * Gallery upload - multiple images with strict validation
     * This method specifically tests createMultiple() with validation
     *
     * @param array<KoriyumFileUpload|ErrorFileUpload> $images
     */
    public function onDelete(
        #[InputFile(
            maxSize: 512 * 1024, // 512KB - smaller limit for testing
            allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
            allowedExtensions: ['jpg', 'jpeg', 'png', 'svg']
        )]
        array $images,
        string $galleryName = 'default'
    ): static {
        $validImages = [];
        $errorImages = [];

        foreach ($images as $index => $image) {
            if ($image instanceof ErrorFileUpload) {
                $errorImages[] = [
                    'index' => $index,
                    'error' => $image->message,
                ];
                continue;
            }

            $validImages[] = [
                'index' => $index,
                'filename' => $image->name,
                'size' => $image->size,
                'type' => $image->type,
                'isImage' => $image->isImage(),
            ];
        }

        $this->code = empty($errorImages) ? 200 : 207; // 207 Multi-Status
        $this->body = [
            'galleryName' => $galleryName,
            'totalImages' => count($images),
            'validImages' => $validImages,
            'errorImages' => $errorImages,
            'hasErrors' => !empty($errorImages),
        ];

        return $this;
    }
}
