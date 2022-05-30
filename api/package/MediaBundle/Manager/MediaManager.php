<?php

namespace Package\MediaBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Package\Library\Client;
use Package\MediaBundle\Compressor\Image;
use Package\MediaBundle\Entity\Media;
use Package\StorageBundle\Storage\Storage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Uid\Ulid;

class MediaManager
{
    public function __construct(
        private readonly Storage $storage,
        private readonly EntityManagerInterface $em,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Upload HTTP File Request.
     */
    public function uploadFile(Request $request, ?array $keys = null): array
    {
        $data = $keys ? array_intersect_key($request->files->all(), $keys) : $request->files->all();

        // Convert to Media Entity
        array_walk_recursive($data, function (&$item) {
            try {
                $item = $this->createMedia(
                    $item->getMimeType(),
                    $item->getExtension(),
                    $item->getContent(),
                    $item->getSize()
                );
            } catch (\Exception $exception) {
                $this->logger->error('HTTP File Upload Failed: '.$exception->getMessage());
            }
        });

        // Save
        $this->em->flush();

        return $data;
    }

    public function uploadBase64(Request $request, array $keys): array
    {
        $data = array_intersect_key($request->request->all(), $keys);

        // Convert to Media Entity
        array_walk_recursive($data, function (&$item) {
            try {
                $file = base64_decode($item);
                $mimeType = finfo_buffer(finfo_open(), $file, FILEINFO_MIME_TYPE);
                $extension = (new MimeTypes())->getExtensions($mimeType);

                $item = $this->createMedia($mimeType, $extension[0], $file, strlen($file));
            } catch (\Exception $exception) {
                $this->logger->error('Base64 File Upload Failed: '.$exception->getMessage());
            }
        });

        // Save
        $this->em->flush();

        return $data;
    }

    public function uploadLink(Request $request, array $keys): array
    {
        $data = array_intersect_key($request->request->all(), $keys);

        // Convert to Media Entity
        array_walk_recursive($data, function (&$item) {
            try {
                $file = Client::create($item)->get()->body;
                $mimeType = finfo_buffer(finfo_open(), $file, FILEINFO_MIME_TYPE);
                $extension = (new MimeTypes())->getExtensions($mimeType);

                $item = $this->createMedia($mimeType, $extension[0], $file, strlen($file));
            } catch (\Exception $exception) {
                $this->logger->error('Link File Upload Failed: '.$exception->getMessage());
            }
        });

        // Save
        $this->em->flush();

        return $data;
    }

    protected function createMedia(string $mimeType, string $extension, string $content, int $size): Media
    {
        // Create Media
        $media = (new Media())
            ->setMime($mimeType)
            ->setStorage($this->storage->getStorageKey())
            ->setSize($size)
            ->setPath($this->getPath(Ulid::generate(), $extension));
        $this->em->persist($media);

        // Write Storage
        $this->storage->write($media->getPath(), $content, $media->getMime());

        return $media;
    }

    protected function getPath(string $fileName, string $extension): string
    {
        return strtolower(date('Y/m/d').'/'.$fileName.'.'.$extension);
    }

    protected function compress(string $data, string $extension): bool|string|null
    {
        return Image::create($data)->output($extension);
    }
}
